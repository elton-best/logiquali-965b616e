# Analyse des Parcours Problématiques - Tests Échoués

**Date**: 2 mai 2026  
**Contexte**: 53 tests échouent après le nettoyage complet du système de nomenclature documentaire

---

## 🎯 Vue d'Ensemble des Parcours

Les tests échoués révèlent **3 parcours utilisateur majeurs** qui sont actuellement cassés:

### Parcours 1: Import Wizard de Documents (15 tests)
### Parcours 2: Recyclage de Codes Documents (6 tests)  
### Parcours 3: Revue de Processus avec Données Liées (7 tests)
### Parcours 4: Gestion de Nomenclature (Obsolète - 8 tests)
### Parcours 5: Workflow Documents (4 tests)
### Parcours 6: Configuration Types Documents (7 tests)
### Parcours 7: Divers (6 tests)

---

## 📋 PARCOURS 1: Import Wizard de Documents

### Description du Parcours Attendu

**Objectif**: Permettre l'import massif de documents existants via fichier Excel/CSV

**Étapes du parcours**:
1. **Upload** → Utilisateur télécharge un fichier Excel/CSV avec liste de documents
2. **Validation** → Système analyse le fichier et détecte erreurs/warnings
3. **Prévisualisation** → Utilisateur voit ce qui sera importé
4. **Exécution** → Import effectif des documents dans la base
5. **Rollback** → Possibilité d'annuler l'import si erreur
6. **Historique** → Consultation des imports passés

### État Actuel du Parcours

**Controller**: `DocumentImportController` existe mais **routes manquantes**

**Routes attendues** (selon tests):
```
POST   /api/v1/document-imports/upload
POST   /api/v1/document-imports/{id}/validate
POST   /api/v1/document-imports/{id}/execute
POST   /api/v1/document-imports/{id}/rollback
GET    /api/v1/document-imports/template
GET    /api/v1/document-imports
GET    /api/v1/document-imports/{id}
DELETE /api/v1/document-imports/{id}
```

**Routes actuelles** (dans api.php):
```
POST   /api/v1/document-import/analyze
POST   /api/v1/document-import/preview
POST   /api/v1/document-import/execute
```

### Problème Identifié

**DÉCALAGE COMPLET** entre:
- Les **tests** qui testent un parcours "Import Wizard" complet avec gestion d'état (pending → validated → completed)
- Le **controller actuel** qui implémente un parcours "Migration" pour documents existants (analyze → preview → execute)

### Fonctionnalités Implémentées vs Attendues

| Fonctionnalité | Implémenté | Attendu par Tests |
|----------------|------------|-------------------|
| Upload fichier | ❌ | ✅ |
| Validation avec état | ❌ | ✅ |
| Exécution avec état | ❌ | ✅ |
| Rollback | ❌ | ✅ |
| Historique imports | ❌ | ✅ |
| Template téléchargement | ❌ | ✅ |
| Analyse documents existants | ✅ | ❌ |
| Prévisualisation mapping | ✅ | ❌ |

### Impact Utilisateur

**Parcours cassé**: L'utilisateur ne peut PAS importer des documents via fichier Excel/CSV car:
1. Les routes n'existent pas
2. Le modèle `DocumentImport` avec états (pending/validated/completed) n'est pas utilisé
3. Pas de gestion de fichiers uploadés
4. Pas d'historique des imports

**Parcours fonctionnel**: L'utilisateur PEUT migrer des documents existants de l'ancien système vers le nouveau (mais ce n'est pas ce que les tests vérifient)

### Solution Requise

**Option A**: Implémenter le parcours "Import Wizard" complet
- Créer les routes manquantes
- Utiliser le modèle `DocumentImport` avec états
- Gérer l'upload de fichiers
- Implémenter validation/exécution/rollback

**Option B**: Supprimer les tests et documenter que seule la migration est supportée
- Supprimer les 15 tests
- Documenter que l'import se fait via migration uniquement

---

## 📋 PARCOURS 2: Recyclage de Codes Documents

### Description du Parcours Attendu

**Objectif**: Permettre la réutilisation de codes de documents rejetés/abandonnés

**Étapes du parcours**:
1. **Vérification disponibilité** → Utilisateur vérifie si un code est disponible
2. **Libération code** → Utilisateur libère un code (document rejeté)
3. **Consultation codes disponibles** → Liste des codes recyclables
4. **Historique code** → Traçabilité des libérations/réutilisations
5. **Réutilisation** → Code devient indisponible quand réutilisé

### État Actuel du Parcours

**Controller**: `DocumentCodeRecyclingController` existe

**Routes**: ✅ Toutes présentes
```
GET  /api/v1/document-codes/check-availability
POST /api/v1/document-codes/release
GET  /api/v1/document-codes/available
GET  /api/v1/document-codes/{code}/history
```

### Problème Identifié

**ErrorException 500** sur TOUTES les requêtes → Middleware ou erreur dans le controller

**Cause probable**:
1. Middleware `withoutMiddleware()` dans les tests ne fonctionne pas correctement
2. Middleware MFA/Subscription/CompanySetup bloque les requêtes
3. Erreur non catchée dans le controller (accès à propriété inexistante, etc.)

### Impact Utilisateur

**Parcours complètement cassé**: L'utilisateur ne peut PAS:
- Vérifier si un code est disponible
- Libérer un code pour recyclage
- Consulter les codes disponibles
- Voir l'historique d'un code

**Erreur**: 500 Internal Server Error au lieu de réponses JSON attendues

### Solution Requise

1. **Débugger le controller** pour identifier l'ErrorException
2. **Corriger les middlewares** dans les tests ou le controller
3. **Vérifier les dépendances** (Enterprise, Site, User) dans les requêtes

---

## 📋 PARCOURS 3: Revue de Processus avec Données Liées

### Description du Parcours Attendu

**Objectif**: Permettre la revue périodique d'un processus avec création d'actions liées aux risques/aspects environnementaux

**Étapes du parcours**:
1. **Consultation revue courante** → GET /processes/{id}/reviews/current
2. **Mise à jour sections** → PUT /processes/{id}/reviews/current
3. **Consultation données liées** → GET /processes/{id}/reviews/current/linked-data (DUERP, AES, Audits)
4. **Création actions liées** → POST /processes/{id}/reviews/current/linked-data/actions
5. **Clôture revue** → POST /processes/{id}/reviews/current/close

### État Actuel du Parcours

**Controller**: `ProcessReviewController` existe

**Routes**: ✅ Toutes présentes

### Problème Identifié

**ErrorException 500** sur TOUTES les requêtes

**Tests désactivent les middlewares**:
```php
$this->withoutMiddleware([
    CheckSubscriptionStatus::class,
    ForceCompanySetup::class,
]);
```

Mais **ErrorException** persiste → Erreur dans le controller lui-même

**Cause probable**:
1. Accès à une propriété/méthode inexistante
2. Relation Eloquent manquante
3. Typage strict qui échoue
4. Dépendance injectée qui échoue

### Impact Utilisateur

**Parcours complètement cassé**: L'utilisateur ne peut PAS:
- Consulter la revue courante d'un processus
- Mettre à jour les sections de la revue
- Voir les données liées (DUERP, AES, Audits)
- Créer des actions depuis la revue
- Clôturer une revue

**Erreur**: 500 Internal Server Error

### Solution Requise

1. **Analyser les logs Laravel** pour identifier l'ErrorException exacte
2. **Débugger le ProcessReviewController** ligne par ligne
3. **Vérifier les relations Eloquent** utilisées
4. **Corriger l'erreur** identifiée

---

## 📋 PARCOURS 4: Gestion de Nomenclature (OBSOLÈTE)

### Description du Parcours

**Objectif**: Gérer les nomenclatures documentaires (ancien système)

### État Actuel

**SYSTÈME SUPPRIMÉ** lors du nettoyage "GO CLEAN"

**Table supprimée**: `document_nomenclatures`

**Tests concernés**: 8 tests
- `DocumentPreviewCodeTest` (1 test)
- `NomenclatureGlobalScopeTest` (7 tests)

### Problème Identifié

**Tests testent un système qui n'existe plus**

### Impact Utilisateur

**Aucun impact** car le système a été remplacé par `DocumentTypeConfiguration`

### Solution Requise

**SUPPRIMER les 8 tests** car ils testent l'ancien système

---

## 📋 PARCOURS 5: Workflow Documents

### Description du Parcours Attendu

**Objectif**: Workflow de validation des documents (draft → pending_verification → pending_approval → approved)

### État Actuel du Parcours

**Tests concernés**:
- `DocumentCodePoolWorkflowTest` (3 tests) → Erreur: colonne `type` manquante
- `DocumentSourceTraceabilityTest` (1 test) → Erreur: colonne `type` manquante
- `DocumentWorkflowEnhancedTest` (1 test) → Erreur: 400 au lieu de 200

### Problème Identifié

**DocumentFactory essaie toujours d'insérer la colonne `type`** qui a été supprimée

**Fichier**: `database/factories/DocumentFactory.php`

### Impact Utilisateur

**Tests ne peuvent pas créer de documents** pour tester le workflow

### Solution Requise

**Corriger DocumentFactory** pour utiliser `document_type_configuration_id` au lieu de `type`

---

## 📋 PARCOURS 6: Configuration Types Documents

### Description du Parcours Attendu

**Objectif**: Configurer les types de documents avec nomenclature flexible

### État Actuel du Parcours

**Tests concernés**:
- `DocumentTypeConfigurationApiTest` (3 tests) → 422 validation, 403 forbidden
- `DocumentTypeConfigurationVisibilityServiceTest` (4 tests) → Guard mismatch, assertions incorrectes

### Problème Identifié

1. **Validation échoue** lors de la création
2. **Permissions manquantes** pour toggle/share
3. **Guard mismatch** (sanctum vs web)
4. **Logique de visibilité incorrecte**

### Impact Utilisateur

**Parcours partiellement cassé**: L'utilisateur peut consulter mais ne peut pas:
- Créer de nouvelles configurations (validation échoue)
- Activer/désactiver une configuration (403)
- Partager avec d'autres sites (403)

### Solution Requise

1. **Corriger la validation** lors de la création
2. **Ajouter les permissions** manquantes
3. **Corriger le guard** dans les tests
4. **Corriger la logique de visibilité**

---

## 📋 PARCOURS 7: Divers

### Tests Concernés

1. **NomenclatureTemplateTest** (2 tests) → 500 au lieu de 422/200
2. **TrainingPlanAndInternalEvaluationTest** (1 test) → 500 au lieu de 200

### Problème

ErrorException non identifiée dans les controllers

---

## 🎯 Synthèse des Parcours

| Parcours | État | Impact | Priorité |
|----------|------|--------|----------|
| Import Wizard Documents | ❌ Routes manquantes | Bloquant | 🔴 HAUTE |
| Recyclage Codes | ❌ ErrorException 500 | Bloquant | 🔴 HAUTE |
| Revue Processus | ❌ ErrorException 500 | Bloquant | 🔴 HAUTE |
| Nomenclature (ancien) | ❌ Système supprimé | Aucun | 🟢 BASSE |
| Workflow Documents | ❌ Factory incorrect | Bloquant | 🔴 HAUTE |
| Config Types Documents | ⚠️ Partiellement cassé | Moyen | 🟡 MOYENNE |
| Divers | ❌ ErrorException 500 | Faible | 🟡 MOYENNE |

---

## 🔧 Plan d'Action Recommandé

### Phase 1: Corrections Rapides (30 min)
1. ✅ Corriger DocumentFactory → 4 tests
2. ✅ Supprimer tests nomenclature obsolètes → 8 tests

### Phase 2: Décision Architecturale (15 min)
3. ❓ Import Wizard: Implémenter ou supprimer tests? → 15 tests

### Phase 3: Debugging ErrorException (1h)
4. 🔍 Analyser logs pour identifier ErrorException
5. 🔍 Débugger DocumentCodeRecyclingController → 6 tests
6. 🔍 Débugger ProcessReviewController → 7 tests
7. 🔍 Débugger NomenclatureTemplateController → 2 tests
8. 🔍 Débugger TrainingPlanController → 1 test

### Phase 4: Permissions & Validation (30 min)
9. 🔧 Corriger validation DocumentTypeConfiguration → 1 test
10. 🔧 Ajouter permissions toggle/share → 2 tests
11. 🔧 Corriger guard Spatie → 1 test
12. 🔧 Corriger logique visibilité → 3 tests
13. 🔧 Corriger workflow reminder → 1 test

**Temps total estimé**: 2h15

---

## 💡 Recommandations

### Recommandation 1: Import Wizard
**Décider rapidement** si cette fonctionnalité est nécessaire:
- **OUI** → Implémenter les routes et la logique complète (4h de dev)
- **NON** → Supprimer les tests et documenter que seule la migration est supportée (15 min)

### Recommandation 2: Logging
**Activer les logs détaillés** pour identifier les ErrorException:
```bash
tail -f storage/logs/laravel.log
```

### Recommandation 3: Tests Unitaires
**Privilégier les tests unitaires** pour débugger les services avant les tests feature

### Recommandation 4: Documentation
**Documenter les parcours utilisateur** validés pour éviter les décalages tests/implémentation
