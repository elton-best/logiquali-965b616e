# 🧪 Tests - BestQHSE

**Date de création :** 15 janvier 2026  
**Version :** 1.1.0  
**Framework de tests :** PHPUnit 11.5.46

---

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Tests Unitaires](#tests-unitaires)
3. [Tests Fonctionnels](#tests-fonctionnels)
4. [Scripts de Test](#scripts-de-test)
5. [Exécution des Tests](#exécution-des-tests)
6. [Couverture de Code](#couverture-de-code)
7. [Bonnes Pratiques](#bonnes-pratiques)

---

## 🎯 Vue d'ensemble

Le projet BestQHSE utilise PHPUnit pour les tests automatisés. Les tests sont organisés en deux catégories principales :

- **Tests Unitaires** : Testent les models et traits isolément
- **Tests Fonctionnels** : Testent les controllers et l'API complète

### Statistiques

| Catégorie          | Nombre | Couverture           |
| ------------------ | ------ | -------------------- |
| Tests Unitaires    | 5      | ~14% des models      |
| Tests Fonctionnels | 7      | ~21% des controllers |
| **Total Tests**    | **12** | **~15%**             |
| Scripts Bash       | 2      | -                    |

**Objectif de couverture :** 80%

---

## 🧪 Tests Unitaires

Les tests unitaires se trouvent dans `backend/tests/Unit/`

### 1. UserTest

**Fichier :** `tests/Unit/UserTest.php`  
**Nombre de tests :** 6

**Tests inclus :**

- ✅ `it_has_fillable_attributes` - Vérifie les champs fillables
- ✅ `it_casts_email_verified_at_to_datetime` - Vérifie le casting
- ✅ `it_hides_password_in_arrays` - Vérifie que password est caché
- ✅ `it_belongs_to_responsibility` - Teste la relation belongsTo
- ✅ `it_uses_auto_reference_trait` - Vérifie l'utilisation du trait
- ✅ `it_soft_deletes` - Teste le soft delete

**Exemple d'exécution :**

```bash
php artisan test --filter UserTest
```

### 2. EntiteTest

**Fichier :** `tests/Unit/EntiteTest.php`  
**Nombre de tests :** 4

**Tests inclus :**

- ✅ `it_generates_reference_on_creation` - Référence auto-générée
- ✅ `it_increments_reference_number` - Incrémentation correcte
- ✅ `it_has_fillable_attributes` - Champs fillables
- ✅ `it_soft_deletes` - Soft delete

**Format de référence testé :** `ENT-2026-001`, `ENT-2026-002`, etc.

### 3. ResponsibilityTest

**Fichier :** `tests/Unit/ResponsibilityTest.php`  
**Nombre de tests :** 3

**Tests inclus :**

- ✅ `it_has_correct_fillable_attributes` - Champs fillables
- ✅ `it_can_have_users` - Relation hasMany avec users
- ✅ `it_soft_deletes` - Test soft delete et withTrashed

### 4. AutoReferenceTraitTest

**Fichier :** `tests/Unit/AutoReferenceTraitTest.php`  
**Nombre de tests :** 3

**Tests inclus :**

- ✅ `it_generates_unique_reference_for_processus` - Références uniques
- ✅ `it_uses_correct_year_in_reference` - Année correcte
- ✅ `it_pads_numbers_with_zeros` - Format avec padding (001, 002)

---

## 🌐 Tests Fonctionnels

Les tests fonctionnels se trouvent dans `backend/tests/Feature/`

### 1. UserControllerTest

**Fichier :** `tests/Feature/UserControllerTest.php`  
**Nombre de tests :** 8

**Tests CRUD :**

- ✅ `it_can_list_users_with_pagination` - Liste avec pagination (20/page)
- ✅ `it_can_create_a_user` - Création d'utilisateur
- ✅ `it_can_show_a_user` - Affichage d'un utilisateur
- ✅ `it_can_update_a_user` - Mise à jour
- ✅ `it_can_soft_delete_a_user` - Suppression douce

**Tests de sécurité :**

- ✅ `it_requires_authentication` - Authentification requise
- ✅ `it_validates_required_fields_on_create` - Validation champs requis
- ✅ `it_validates_email_uniqueness` - Unicité de l'email

### 2. ResponsibilityControllerTest

**Fichier :** `tests/Feature/ResponsibilityControllerTest.php`  
**Nombre de tests :** 5

**Tests :**

- ✅ CRUD complet (list, create, show, update, delete)
- ✅ Pagination (20 items par page)
- ✅ Format JSON:API

### 3. EntiteControllerTest

**Fichier :** `tests/Feature/EntiteControllerTest.php`  
**Nombre de tests :** 4

**Tests :**

- ✅ `it_generates_reference_automatically` - Référence auto
- ✅ `it_can_list_entites` - Liste des entités
- ✅ `it_can_update_an_entite` - Mise à jour
- ✅ `it_tracks_created_by_user` - Tracking utilisateur

### 4. ProcessusControllerTest

**Fichier :** `tests/Feature/ProcessusControllerTest.php`  
**Nombre de tests :** 3

**Tests :**

- ✅ Création avec auto-référence (`PRO-2026-001`)
- ✅ Liste avec relations
- ✅ Validation entite_id existe

### 5. DocumentControllerTest

**Fichier :** `tests/Feature/DocumentControllerTest.php`  
**Nombre de tests :** 4

**Tests :**

- ✅ `it_can_upload_a_document` - Upload de fichier
- ✅ `it_generates_document_reference_automatically` - Référence auto
- ✅ `it_can_filter_documents_by_status` - Filtrage par statut
- ✅ `it_validates_document_type` - Validation du type

**Types de documents testés :** manuel, procedure, formulaire, enregistrement

### 6. NonConformiteControllerTest

**Fichier :** `tests/Feature/NonConformiteControllerTest.php`  
**Nombre de tests :** 4

**Tests :**

- ✅ Création avec référence (`NC-2026-001`)
- ✅ Mise à jour du statut
- ✅ Validation des valeurs de sévérité
- ✅ Filtrage par sévérité (critique, majeure, mineure)

### 7. ActionCorrectiveControllerTest

**Fichier :** `tests/Feature/ActionCorrectiveControllerTest.php`  
**Nombre de tests :** 3

**Tests :**

- ✅ Création action corrective (`AC-2026-001`)
- ✅ Validation du type (corrective, preventive, improvement)
- ✅ Complétion d'une action

---

## 🔧 Scripts de Test

### 1. run_tests.sh

**Emplacement :** `local_tests/run_tests.sh`  
**Description :** Script principal pour exécuter tous les tests

**Fonctionnalités :**

- ✅ Vérification de l'environnement
- ✅ Installation des dépendances
- ✅ Migration fresh avec seed
- ✅ Exécution des tests en parallèle
- ✅ Rapport de synthèse
- ✅ Codes de sortie appropriés

**Utilisation :**

```bash
./local_tests/run_tests.sh
```

**Sortie attendue :**

```
========================================
   BestQHSE - TESTS SUITE
========================================

Step 1: Checking environment...
Environment OK

Step 2: Installing dependencies...
Dependencies installed

Step 3: Running database migrations (fresh)...
Database ready

Step 4: Running PHPUnit tests...
========================================
  PASS  Tests\Unit\UserTest
  PASS  Tests\Feature\UserControllerTest
  ...
========================================

All tests passed!
```

### 2. test_api_endpoints.sh

**Emplacement :** `local_tests/test_api_endpoints.sh`  
**Description :** Script de test des endpoints API avec cURL

**Fonctionnalités :**

- ✅ Authentification automatique
- ✅ Tests de tous les endpoints principaux
- ✅ Vérification des réponses JSON:API
- ✅ Support des headers Accept/Content-Type

**Utilisation :**

```bash
./local_tests/test_api_endpoints.sh
```

**Endpoints testés :**

- GET /api/users
- GET /api/responsibilities
- GET /api/entites
- GET /api/processus
- GET /api/documents
- GET /api/non-conformites

---

## 🚀 Exécution des Tests

### Commandes de base

```bash
# Tous les tests
php artisan test

# Tests en parallèle (plus rapide)
php artisan test --parallel

# Tests spécifiques
php artisan test --filter UserTest
php artisan test --filter UserControllerTest

# Tests d'un dossier
php artisan test tests/Unit
php artisan test tests/Feature

# Mode compact
php artisan test --compact

# Mode verbose
php artisan test --verbose
```

### Avec le script bash

```bash
# Tout automatiquement
./local_tests/run_tests.sh

# Test API endpoints
./local_tests/test_api_endpoints.sh
```

### Configuration de test

**Fichier :** `phpunit.xml`

```xml
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

## 📊 Couverture de Code

### Générer un rapport de couverture

```bash
# Couverture basique
php artisan test --coverage

# Couverture minimale requise
php artisan test --coverage --min=80

# Rapport HTML (avec Xdebug)
php artisan test --coverage-html coverage/
```

### État actuel

| Type        | Couverture | Objectif |
| ----------- | ---------- | -------- |
| Models      | ~14%       | 80%      |
| Controllers | ~21%       | 80%      |
| **Global**  | **~15%**   | **80%**  |

### Prochaines priorités

**Tests à ajouter (par priorité) :**

1. **Controllers prioritaires (27 restants)**
   - ProcessController
   - AuditController
   - RiskController
   - OpportunityController
   - ObjectiveController
   - ...

2. **Models prioritaires (30 restants)**
   - Enterprise
   - Site
   - Processus
   - Document
   - Audit
   - ...

3. **Tests de validation**
   - FormRequests
   - Custom rules
   - Validation messages

4. **Tests de permissions**
   - Middleware
   - Policy
   - Gates

---

## ✅ Bonnes Pratiques

### 1. Nommage des tests

```php
// ✅ Bon - Descriptif et clair
public function it_can_create_a_user()
public function it_validates_email_uniqueness()
public function it_requires_authentication()

// ❌ Mauvais
public function testUser()
public function test1()
```

### 2. Structure des tests

```php
public function it_can_create_a_user()
{
    // Arrange - Préparer les données
    $data = [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ];

    // Act - Exécuter l'action
    $response = $this->postJson('/api/users', $data);

    // Assert - Vérifier les résultats
    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
}
```

### 3. Utilisation des factories

```php
// ✅ Bon - Utiliser les factories
$user = User::factory()->create();
$users = User::factory()->count(10)->create();

// ❌ Mauvais - Créer manuellement
$user = new User();
$user->name = 'Test';
$user->save();
```

### 4. RefreshDatabase

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase; // Important pour isolation
}
```

### 5. Assertions JSON:API

```php
$response->assertStatus(200)
    ->assertJsonStructure([
        'data' => [
            '*' => ['id', 'type', 'attributes']
        ],
        'links',
        'meta'
    ])
    ->assertJsonPath('meta.per_page', 20);
```

---

## 📚 Ressources

### Documentation officielle

- [Laravel Testing](https://laravel.com/docs/testing)
- [PHPUnit](https://phpunit.de/documentation.html)
- [JSON:API Specification](https://jsonapi.org/format/)

### Commandes utiles

```bash
# Liste tous les tests
php artisan test --list-tests

# Tests avec stop on failure
php artisan test --stop-on-failure

# Tests avec testsuite spécifique
php artisan test --testsuite=Feature

# Debug un test
php artisan test --filter=testName --verbose
```

---

## 🎯 Objectifs Futurs

### Court terme (Cette semaine)

- [ ] Atteindre 30% de couverture
- [ ] Ajouter 20+ tests controllers
- [ ] Ajouter 15+ tests models
- [ ] Tests de permissions

### Moyen terme (Ce mois)

- [ ] Atteindre 60% de couverture
- [ ] Tests d'intégration complets
- [ ] Tests de performance
- [ ] CI/CD avec GitHub Actions

### Long terme

- [ ] Atteindre 80%+ de couverture
- [ ] Tests end-to-end
- [ ] Tests de charge
- [ ] Tests de sécurité automatisés

---

**Dernière mise à jour :** 15 janvier 2026 à 16:30 UTC  
**Version :** 1.1.0  
**Équipe :** BestQHSE - Des Professionnels Rigoureux 💼
