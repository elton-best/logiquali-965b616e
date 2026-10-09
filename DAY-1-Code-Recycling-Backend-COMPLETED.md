# Day 1 - Code Recycling Backend ✅

**Date**: 2026-04-29  
**Status**: COMPLETED  
**Tests**: 6/6 passing (17 assertions)

---

## Implémentation

### 1. Migration - `document_code_releases` table

**Fichier**: `database/migrations/2026_04_29_103352_create_document_code_releases_table.php`

**Colonnes**:
- `id`: Primary key
- `entreprise_id`: FK vers `enterprises` (ON DELETE CASCADE)
- `code`: Code du document libéré (indexed)
- `original_document_id`: FK vers `documents` (nullable, ON DELETE SET NULL)
- `release_reason`: ENUM('rejected', 'deleted', 'manual')
- `released_by`: FK vers `users` (nullable, ON DELETE SET NULL)
- `released_at`: Timestamp de libération
- `reused_by_document_id`: FK vers `documents` (nullable, ON DELETE SET NULL)
- `reused_at`: Timestamp de réutilisation (nullable)
- `timestamps`: created_at, updated_at

**Index composite**: `(entreprise_id, code, reused_at)` pour optimiser les requêtes de disponibilité

---

### 2. Model - `DocumentCodeRelease`

**Fichier**: `app/Models/DocumentCodeRelease.php`

**Relations**:
- `entreprise()`: BelongsTo Enterprise
- `originalDocument()`: BelongsTo Document
- `releasedBy()`: BelongsTo User
- `reusedByDocument()`: BelongsTo Document

**Scopes**:
- `available($entrepriseId)`: Codes disponibles (non réutilisés) pour une entreprise
- `forCode($code)`: Filtre par code spécifique

**Casts**:
- `released_at`: datetime
- `reused_at`: datetime

---

### 3. Service - `DocumentCodeRecyclingService`

**Fichier**: `app/Services/DocumentCodeRecyclingService.php`

**Méthodes**:

#### `isCodeAvailable(string $code, int $entrepriseId): bool`
Vérifie si un code est disponible pour réutilisation:
1. Vérifie si le code est utilisé dans un document actif (via relation site->enterprise)
2. Vérifie si le code a été libéré puis réutilisé
3. Retourne `true` si disponible, `false` sinon

#### `releaseCode(...): DocumentCodeRelease`
Libère un code pour réutilisation future:
- Paramètres: code, entrepriseId, originalDocumentId (nullable), reason, releasedBy (nullable)
- Crée un enregistrement dans `document_code_releases`
- Timestamp automatique `released_at`

#### `markCodeAsReused(string $code, int $entrepriseId, int $documentId): void`
Marque un code comme réutilisé:
- Met à jour `reused_by_document_id` et `reused_at`
- Rend le code indisponible pour d'autres réutilisations

#### `getAvailableCodes(int $entrepriseId, int $limit = 50): array`
Liste les codes disponibles avec métadonnées:
- Limite par défaut: 50 codes
- Tri par `released_at` DESC
- Inclut: code, released_at, release_reason, original_document, released_by

#### `getCodeHistory(string $code, int $entrepriseId): array`
Historique complet d'un code:
- Toutes les libérations/réutilisations
- Tri chronologique inverse
- Inclut: released_at, release_reason, original_document, released_by, reused_at, reused_by_document

---

### 4. Controller - `DocumentCodeRecyclingController`

**Fichier**: `app/Http/Controllers/Api/DocumentCodeRecyclingController.php`

**Endpoints**:

#### `GET /api/v1/document-codes/check-availability?code={code}`
Vérifie la disponibilité d'un code.

**Request**:
```json
{
  "code": "DOC-001"
}
```

**Response 200**:
```json
{
  "available": true,
  "code": "DOC-001"
}
```

**Errors**: 500 (erreur serveur)

---

#### `POST /api/v1/document-codes/release`
Libère un code pour réutilisation.

**Request**:
```json
{
  "code": "DOC-001",
  "original_document_id": 123,
  "reason": "rejected"
}
```

**Validation**:
- `code`: required|string|max:255
- `original_document_id`: nullable|exists:documents,id
- `reason`: required|in:rejected,deleted,manual

**Response 201**:
```json
{
  "message": "Code libéré avec succès",
  "release": {
    "id": 1,
    "code": "DOC-001",
    "entreprise_id": 1,
    "release_reason": "rejected",
    "released_by": 5,
    "released_at": "2026-04-29T10:30:00.000000Z"
  }
}
```

**Errors**: 
- 403 (document n'appartient pas à l'entreprise)
- 500 (erreur serveur)

**Logging**: Info sur libération réussie, Error sur échec

---

#### `GET /api/v1/document-codes/available`
Liste les codes disponibles pour l'entreprise.

**Response 200**:
```json
{
  "codes": [
    {
      "code": "DOC-001",
      "released_at": "2026-04-29T10:30:00.000000Z",
      "release_reason": "rejected",
      "original_document": "Manuel Qualité v2",
      "released_by": "Jean Dupont"
    }
  ],
  "count": 1
}
```

**Errors**: 500 (erreur serveur)

---

#### `GET /api/v1/document-codes/{code}/history`
Historique complet d'un code.

**Response 200**:
```json
{
  "code": "DOC-001",
  "history": [
    {
      "released_at": "2026-04-29T10:30:00.000000Z",
      "release_reason": "rejected",
      "original_document": "Manuel Qualité v2",
      "released_by": "Jean Dupont",
      "reused_at": "2026-04-30T14:20:00.000000Z",
      "reused_by_document": "Procédure Audit"
    }
  ]
}
```

**Errors**: 500 (erreur serveur)

---

### 5. Routes API

**Fichier**: `routes/api.php`

Ajout dans le groupe `auth:sanctum` + middlewares:

```php
Route::get('document-codes/check-availability', [DocumentCodeRecyclingController::class, 'checkAvailability']);
Route::post('document-codes/release', [DocumentCodeRecyclingController::class, 'releaseCode']);
Route::get('document-codes/available', [DocumentCodeRecyclingController::class, 'availableCodes']);
Route::get('document-codes/{code}/history', [DocumentCodeRecyclingController::class, 'codeHistory']);
```

---

### 6. Tests

**Fichier**: `tests/Feature/DocumentCodeRecyclingTest.php`

**6 tests - 17 assertions**:

1. ✅ `test_check_availability_returns_true_for_unused_code`
   - Vérifie qu'un code jamais utilisé est disponible

2. ✅ `test_check_availability_returns_false_for_used_code`
   - Vérifie qu'un code utilisé dans un document actif est indisponible

3. ✅ `test_release_code_creates_release_record`
   - Vérifie la création d'un enregistrement de libération
   - Vérifie la structure de la réponse JSON
   - Vérifie la présence en base de données

4. ✅ `test_available_codes_returns_released_codes`
   - Vérifie que les codes libérés apparaissent dans la liste
   - Vérifie la structure de la réponse (codes + count)

5. ✅ `test_code_history_returns_release_history`
   - Vérifie la récupération de l'historique d'un code
   - Vérifie la structure de la réponse (code + history)

6. ✅ `test_released_code_becomes_unavailable_when_reused`
   - Vérifie qu'un code libéré puis réutilisé devient indisponible
   - Teste le cycle complet: libération → réutilisation → indisponibilité

**Setup**:
- Enterprise (status: active, approval_status: approved)
- Site (lié à l'enterprise)
- EnterpriseSubscription (active, expiration future)
- User (lié à enterprise + site)
- `withoutMiddleware()` pour bypass les checks de subscription/MFA

---

## Corrections apportées

### 1. Nomenclature des tables
- ❌ `entreprises` → ✅ `enterprises`
- Migration corrigée pour référencer la bonne table

### 2. Nomenclature des colonnes User
- ❌ `user->entreprise_id` → ✅ `user->enterprise_id`
- Controller corrigé pour utiliser le bon nom de colonne

### 3. Relation Document-Enterprise
- Documents n'ont PAS de colonne `enterprise_id` directe
- Relation via `site_id` → `site->enterprise_id`
- Service corrigé pour utiliser `whereHas('site', ...)`

### 4. Validation nullable
- ❌ `if ($validated['original_document_id'])` → ✅ `if (isset($validated['original_document_id']))`
- Évite l'erreur "Undefined array key" quand le champ est absent

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Code Recycling Flow                       │
└─────────────────────────────────────────────────────────────┘

1. Document rejeté/supprimé
   ↓
2. releaseCode() → Enregistrement dans document_code_releases
   ↓
3. Code disponible dans getAvailableCodes()
   ↓
4. Utilisateur crée nouveau document avec ce code
   ↓
5. markCodeAsReused() → reused_at + reused_by_document_id
   ↓
6. Code indisponible (isCodeAvailable() = false)
```

---

## Prochaines étapes (Day 2)

**Import Wizard Frontend** qui utilisera ces endpoints:
- `check-availability` avant de créer un document
- `release` automatiquement lors du rejet
- `available` pour afficher les codes recyclables
- `history` pour traçabilité complète

---

## Commandes utiles

```bash
# Migration
php artisan migrate

# Tests
php artisan test --filter=DocumentCodeRecyclingTest

# Rollback
php artisan migrate:rollback --step=1
```

---

**Temps d'implémentation**: ~1h30  
**Complexité**: Moyenne (relations multiples, nomenclature mixte FR/EN)  
**Qualité**: Production-ready avec tests complets
