# Rapport des 53 Tests Échoués - Analyse Détaillée

**Date**: 2 mai 2026  
**Tests passants**: 394/447 (88.1%)  
**Tests échouants**: 53/447 (11.9%)

---

## Catégorie 1: Colonne `type` encore présente dans DocumentFactory (3 tests)

**Erreur**: `SQLSTATE[42703]: Undefined column: 7 ERROR: column "type" of relation "documents" does not exist`

### Tests affectés:
1. `Tests\Feature\DocumentCodePoolWorkflowTest > it releases code when submitter confirms release`
2. `Tests\Feature\DocumentCodePoolWorkflowTest > it does not release code when submitter keeps it`
3. `Tests\Feature\DocumentCodePoolWorkflowTest > it marks code as used when document approved`
4. `Tests\Feature\DocumentSourceTraceabilityTest > update persists source context`

**Cause**: DocumentFactory essaie toujours d'insérer la colonne `type` dans la base de données.

**Fichier**: `database/factories/DocumentFactory.php`

**Solution**: Vérifier que le factory n'utilise plus `'type'` dans sa définition.

---

## Catégorie 2: Table `document_nomenclatures` supprimée (8 tests)

**Erreur**: `SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "document_nomenclatures" does not exist`

### Tests affectés:
1. `Tests\Feature\DocumentPreviewCodeTest > preview code uses nomenclature format`
2. `Tests\Feature\NomenclatureGlobalScopeTest > store allows nomenclature with global scope`
3. `Tests\Feature\NomenclatureGlobalScopeTest > index returns only visible nomenclatures`
4. `Tests\Feature\NomenclatureGlobalScopeTest > store accepts format with placeholders`
5. `Tests\Feature\NomenclatureGlobalScopeTest > store accepts parts array`
6. `Tests\Feature\NomenclatureGlobalScopeTest > update allows changing scope`
7. `Tests\Feature\NomenclatureGlobalScopeTest > update rejects invalid scope`

**Cause**: Ces tests utilisent encore l'ancienne table `document_nomenclatures` qui a été supprimée lors du nettoyage.

**Fichiers concernés**:
- `tests/Feature/DocumentPreviewCodeTest.php`
- `tests/Feature/NomenclatureGlobalScopeTest.php`

**Solution**: 
- Option A: Supprimer ces tests (ancien système)
- Option B: Réécrire ces tests pour utiliser `DocumentTypeConfiguration`

---

## Catégorie 3: Middleware ErrorException - 500 au lieu de statut attendu (28 tests)

**Erreur**: `Expected response status code [XXX] but received 500` avec `ErrorException`

### Tests affectés:

#### DocumentImportControllerTest (14 tests):
1. `it uploads file successfully` - attend 200, reçoit 500
2. `it rejects invalid file format` - attend 422, reçoit 500
3. `it rejects file too large` - attend 422, reçoit 500
4. `it validates import successfully` - attend 200, reçoit 500 (fopen error)
5. `it prevents validation by unauthorized user` - attend 403, reçoit 500
6. `it executes import successfully` - attend 200, reçoit 422
7. `it prevents execution of non validated import` - attend 400, reçoit 422
8. `it shows import details` - attend 200, reçoit 500
9. `it lists user imports` - attend 200, reçoit 500
10. `it filters imports by status` - attend 200, reçoit 500
11. `it downloads template` - attend 200, reçoit 500
12. `it rollbacks import` - attend 200, reçoit 500
13. `it prevents rollback of executed import` - attend 400, reçoit 500
14. `it deletes import` - attend 200, reçoit 500
15. `it prevents deletion by unauthorized user` - attend 403, reçoit 500

#### DocumentCodeRecyclingTest (6 tests):
1. `check availability returns true for available code` - attend 200, reçoit 500
2. `check availability returns false for used code` - attend 200, reçoit 500
3. `release code creates recyclable entry` - attend 201, reçoit 500
4. `available codes returns recyclable codes` - attend 200, reçoit 500
5. `code history returns release records` - attend 200, reçoit 500
6. `released code becomes unavailable` - attend 200, reçoit 500

#### ProcessReviewWorkflowTest (4 tests):
1. `current review can be updated` - attend 200, reçoit 500
2. `identification update is forbidden without permission` - attend 403, reçoit 500
3. `read only user gets capabilities without update right` - attend 200, reçoit 500
4. `linked data endpoint is accessible` - attend 200, reçoit 500
5. `can create linked action from duerp` - attend 200, reçoit 500
6. `can create linked action from aes` - attend 200, reçoit 500
7. `can create linked action from audit` - attend 200, reçoit 500

#### NomenclatureTemplateTest (2 tests):
1. `validate template requires name` - attend 422, reçoit 500
2. `validate template accepts valid structure` - attend 200, reçoit 500

#### DocumentSourceTraceabilityTest (1 test):
1. `store persists source context` - attend 201, reçoit 500

#### TrainingPlanAndInternalEvaluationTest (1 test):
1. `training plan export xlsx works` - attend 200, reçoit 500

**Cause probable**: Middleware (MFA, Subscription, CompanySetup) ou erreurs non catchées dans les controllers.

**Solution**: Analyser les logs d'erreur pour identifier l'exception exacte.

---

## Catégorie 4: Spatie Permission Guard Mismatch (1 test)

**Erreur**: `GuardDoesNotMatch: The given role or permission should use guard 'web' instead of 'sanctum'`

### Test affecté:
1. `Tests\Unit\Services\DocumentTypeConfigurationVisibilityServiceTest > super admin can view all configurations`

**Cause**: Le test utilise `sanctum` guard mais Spatie Permission attend `web` guard.

**Fichier**: `tests/Unit/Services/DocumentTypeConfigurationVisibilityServiceTest.php`

**Solution**: Corriger le guard dans le test ou configurer Spatie pour accepter `sanctum`.

---

## Catégorie 5: Validation/Permission Issues (4 tests)

### Tests affectés:

#### DocumentTypeConfigurationApiTest (3 tests):
1. `it creates configuration` - attend 201, reçoit 422 (validation error)
2. `it toggles active status` - attend 200, reçoit 403 (forbidden)
3. `it shares configuration with sites` - attend 200, reçoit 403 (forbidden)

**Cause**: Problèmes de validation ou de permissions manquantes.

#### DocumentWorkflowEnhancedTest (1 test):
1. `it sends reminder for pending verification` - attend 200, reçoit 400

**Cause**: Validation ou logique métier incorrecte.

---

## Catégorie 6: Assertions Logiques Incorrectes (3 tests)

### Tests affectés:
1. `Tests\Unit\Services\DocumentTypeConfigurationVisibilityServiceTest > it checks if user can edit configuration`
   - **Erreur**: `Failed asserting that true is false`
   
2. `Tests\Unit\Services\DocumentTypeConfigurationVisibilityServiceTest > it prevents editing without permission`
   - **Erreur**: `Failed asserting that false is true`
   
3. `Tests\Unit\Services\DocumentTypeConfigurationVisibilityServiceTest > it shares configuration with sites`
   - **Erreur**: `Failed asserting that actual size 0 matches expected size 2`

**Cause**: Logique de permissions ou de visibilité incorrecte dans le service.

**Fichier**: `app/Services/DocumentTypeConfigurationVisibilityService.php`

---

## Catégorie 7: File System Error (1 test)

**Erreur**: `fopen(...): Failed to open stream: No such file or directory`

### Test affecté:
1. `Tests\Feature\DocumentImportControllerTest > it validates import successfully`

**Cause**: Le répertoire `storage/framework/testing/disks/local/imports/` n'existe pas.

**Solution**: Créer le répertoire dans le test ou utiliser Storage::fake().

---

## Catégorie 8: Nomenclature Template Archive (1 test)

**Erreur**: Assertion database failed

### Test affecté:
1. `Tests\Feature\NomenclatureTemplateTest > archive template changes status`

**Cause**: La logique d'archivage ne fonctionne pas correctement.

---

## Résumé par Priorité

### PRIORITÉ 1 - Corrections Rapides (4 tests):
- ✅ Supprimer `type` de DocumentFactory → 3 tests
- ✅ Créer répertoire imports/ → 1 test

### PRIORITÉ 2 - Décision Architecturale (8 tests):
- ❓ Supprimer ou réécrire tests nomenclature → 8 tests

### PRIORITÉ 3 - Debugging Middleware (28 tests):
- 🔍 Analyser ErrorException dans controllers → 28 tests

### PRIORITÉ 4 - Permissions & Validation (8 tests):
- 🔧 Corriger guard Spatie → 1 test
- 🔧 Corriger logique permissions → 3 tests
- 🔧 Corriger validation API → 3 tests
- 🔧 Corriger workflow reminder → 1 test

### PRIORITÉ 5 - Logique Métier (4 tests):
- 🔧 Corriger visibilité service → 3 tests
- 🔧 Corriger archivage template → 1 test

---

## Plan d'Action Recommandé

1. **Étape 1** (5 min): Corriger DocumentFactory - supprimer `type`
2. **Étape 2** (2 min): Créer répertoire imports/
3. **Étape 3** (10 min): Décider du sort des tests nomenclature (supprimer ou réécrire)
4. **Étape 4** (30 min): Analyser et corriger les ErrorException middleware
5. **Étape 5** (20 min): Corriger permissions et validation
6. **Étape 6** (15 min): Corriger logique métier

**Temps estimé total**: ~1h30
