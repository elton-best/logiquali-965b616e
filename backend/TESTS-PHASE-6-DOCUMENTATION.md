# 🧪 Tests Phase 6 - Nomenclature Flexible - Documentation

## 📋 Fichiers Créés

### Tests Unitaires (Services)

#### 1. DocumentTypeConfigurationServiceTest.php
**Chemin**: `tests/Unit/Services/DocumentTypeConfigurationServiceTest.php`

**Tests (6)**:
- ✅ `it_can_create_configuration` - Création d'une configuration
- ✅ `it_can_update_configuration` - Mise à jour d'une configuration
- ✅ `it_can_delete_configuration` - Suppression d'une configuration
- ✅ `it_validates_structure` - Validation de la structure
- ✅ `it_generates_preview_code` - Génération d'un aperçu de code
- ✅ `it_can_duplicate_configuration` - Duplication d'une configuration

**Commande**:
```bash
php artisan test tests/Unit/Services/DocumentTypeConfigurationServiceTest.php
```

---

#### 2. CodeGenerationServiceTest.php
**Chemin**: `tests/Unit/Services/CodeGenerationServiceTest.php`

**Tests (7)**:
- ✅ `it_generates_code_with_sequence` - Génération avec séquence
- ✅ `it_generates_code_with_year` - Génération avec année
- ✅ `it_recycles_released_codes` - Recyclage des codes libérés
- ✅ `it_checks_code_availability` - Vérification disponibilité
- ✅ `it_releases_code_for_recycling` - Libération pour recyclage
- ✅ `it_reserves_code` - Réservation de code
- ✅ `it_activates_reserved_code` - Activation code réservé

**Commande**:
```bash
php artisan test tests/Unit/Services/CodeGenerationServiceTest.php
```

---

#### 3. DocumentCodeWorkflowServiceTest.php
**Chemin**: `tests/Unit/Services/DocumentCodeWorkflowServiceTest.php`

**Tests (8)**:
- ✅ `it_verifies_document_code` - Vérification d'un code
- ✅ `it_activates_document_code` - Activation d'un code
- ✅ `it_releases_document_code` - Libération d'un code
- ✅ `it_gets_pending_verification_documents` - Documents en attente de vérification
- ✅ `it_gets_pending_approval_documents` - Documents en attente d'approbation
- ✅ `it_gets_workflow_statistics` - Statistiques du workflow
- ✅ `it_prevents_verification_without_permission` - Empêche vérification sans permission

**Commande**:
```bash
php artisan test tests/Unit/Services/DocumentCodeWorkflowServiceTest.php
```

---

#### 4. DocumentTypeConfigurationVisibilityServiceTest.php
**Chemin**: `tests/Unit/Services/DocumentTypeConfigurationVisibilityServiceTest.php`

**Tests (11)**:
- ✅ `it_gets_visible_configurations_for_user` - Configurations visibles
- ✅ `it_filters_configurations_by_site` - Filtrage par site
- ✅ `it_checks_if_user_can_view_configuration` - Vérification permission vue
- ✅ `it_prevents_viewing_other_enterprise_configuration` - Empêche vue autre entreprise
- ✅ `it_checks_if_user_can_edit_configuration` - Vérification permission édition
- ✅ `it_prevents_editing_without_permission` - Empêche édition sans permission
- ✅ `it_shares_configuration_with_sites` - Partage avec sites
- ✅ `it_gets_visibility_statistics` - Statistiques de visibilité
- ✅ `it_applies_advanced_filters` - Application filtres avancés
- ✅ `it_gets_available_sites_for_sharing` - Sites disponibles pour partage
- ✅ `super_admin_can_view_all_configurations` - Super admin voit tout

**Commande**:
```bash
php artisan test tests/Unit/Services/DocumentTypeConfigurationVisibilityServiceTest.php
```

---

### Tests d'Intégration (API)

#### 5. DocumentTypeConfigurationApiTest.php
**Chemin**: `tests/Feature/Api/DocumentTypeConfigurationApiTest.php`

**Tests (15)**:
- ✅ `it_lists_configurations` - Liste des configurations
- ✅ `it_creates_configuration` - Création via API
- ✅ `it_shows_configuration` - Affichage d'une configuration
- ✅ `it_updates_configuration` - Mise à jour via API
- ✅ `it_deletes_configuration` - Suppression via API
- ✅ `it_toggles_active_status` - Basculer statut actif
- ✅ `it_duplicates_configuration` - Duplication via API
- ✅ `it_gets_visibility_stats` - Statistiques de visibilité
- ✅ `it_applies_advanced_filters` - Filtres avancés
- ✅ `it_requires_authentication` - Requiert authentification
- ✅ `it_validates_required_fields_on_create` - Validation champs requis
- ✅ `it_shares_configuration_with_sites` - Partage avec sites
- ✅ `it_gets_available_sites_for_sharing` - Sites disponibles
- ✅ `it_filters_by_scope` - Filtrage par portée
- ✅ `it_filters_by_active_status` - Filtrage par statut

**Commande**:
```bash
php artisan test tests/Feature/Api/DocumentTypeConfigurationApiTest.php
```

---

### Factories Créées

#### 1. DocumentTypeConfigurationFactory.php
**Chemin**: `database/factories/DocumentTypeConfigurationFactory.php`

**Méthodes**:
- `definition()` - Définition par défaut
- `forSite(Site $site)` - Pour un site spécifique
- `forEnterprise(Enterprise $enterprise)` - Pour une entreprise
- `inactive()` - Configuration inactive
- `active()` - Configuration active

---

#### 2. CodeSequenceFactory.php
**Chemin**: `database/factories/CodeSequenceFactory.php`

**Méthodes**:
- `definition()` - Définition par défaut
- `withAvailableNumbers(array $numbers)` - Avec numéros disponibles
- `withCurrentNumber(int $number)` - Avec numéro actuel

---

## 🚀 Commandes d'Exécution

### Exécuter TOUS les tests Phase 6

```bash
cd /mnt/projets/Projets/Best_Experts_Group/LOGIQUALI/backend

# Script automatique
./run-phase6-tests.sh

# OU manuellement
php artisan test tests/Unit/Services/DocumentTypeConfigurationServiceTest.php
php artisan test tests/Unit/Services/CodeGenerationServiceTest.php
php artisan test tests/Unit/Services/DocumentCodeWorkflowServiceTest.php
php artisan test tests/Unit/Services/DocumentTypeConfigurationVisibilityServiceTest.php
php artisan test tests/Feature/Api/DocumentTypeConfigurationApiTest.php
```

---

### Exécuter par catégorie

```bash
# Tests unitaires uniquement
php artisan test tests/Unit/Services/

# Tests d'intégration uniquement
php artisan test tests/Feature/Api/

# Avec détails
php artisan test tests/Unit/Services/ --verbose

# Avec couverture
php artisan test tests/Unit/Services/ --coverage
```

---

### Exécuter un test spécifique

```bash
# Un fichier de test
php artisan test tests/Unit/Services/CodeGenerationServiceTest.php

# Une méthode spécifique
php artisan test --filter=it_generates_code_with_sequence

# Arrêter au premier échec
php artisan test tests/Unit/Services/ --stop-on-failure
```

---

## 📊 Statistiques

### Couverture des Tests

| Service | Tests | Méthodes Testées |
|---------|-------|------------------|
| DocumentTypeConfigurationService | 6 | createConfiguration, updateConfiguration, deleteConfiguration, validateStructure, previewCode, duplicateConfiguration |
| CodeGenerationService | 7 | generateCode, recycleCode, isCodeAvailable, releaseCode, reserveCode, activateCode |
| DocumentCodeWorkflowService | 8 | verifyCode, activateCode, releaseCode, getPendingVerification, getPendingApproval, getWorkflowStats |
| DocumentTypeConfigurationVisibilityService | 11 | getVisibleConfigurations, canView, canEdit, shareWithSites, getVisibilityStats, applyAdvancedFilters, getAvailableSitesForSharing |
| **API Endpoints** | 15 | GET, POST, PUT, DELETE, PATCH sur /document-type-configurations |
| **TOTAL** | **47 tests** | **32 méthodes** |

---

## 🔧 Dépannage

### Erreur: "Class Factory not found"

```bash
# Régénérer l'autoload
composer dump-autoload

# Réessayer
php artisan test
```

---

### Erreur: "Database connection"

```bash
# Vérifier .env.testing
cat .env.testing | grep DB_

# Créer la base de test si nécessaire
createdb bestqhse_test

# Réessayer
php artisan test
```

---

### Erreur: "Permission not found"

```bash
# Exécuter les seeders
php artisan db:seed --class=UnifiedPermissionsSeeder

# Réessayer
php artisan test
```

---

### Voir les erreurs détaillées

```bash
# Mode verbose
php artisan test tests/Unit/Services/ --verbose

# Avec stack trace
php artisan test tests/Unit/Services/ --testdox

# Logs Laravel
tail -f storage/logs/laravel.log
```

---

## ✅ Checklist de Validation

### Tests Unitaires
- [ ] DocumentTypeConfigurationServiceTest (6 tests)
- [ ] CodeGenerationServiceTest (7 tests)
- [ ] DocumentCodeWorkflowServiceTest (8 tests)
- [ ] DocumentTypeConfigurationVisibilityServiceTest (11 tests)

### Tests d'Intégration
- [ ] DocumentTypeConfigurationApiTest (15 tests)

### Factories
- [ ] DocumentTypeConfigurationFactory
- [ ] CodeSequenceFactory

### Couverture
- [ ] Couverture > 80% sur les services
- [ ] Tous les endpoints API testés
- [ ] Tous les cas d'erreur couverts

---

## 📈 Rapport de Couverture

Pour générer un rapport de couverture HTML :

```bash
# Générer le rapport
php artisan test tests/Unit/Services/ --coverage-html=coverage

# Ouvrir le rapport
xdg-open coverage/index.html
```

---

## 🎯 Prochaines Étapes

1. ✅ **Créer les tests** - TERMINÉ
2. ⏳ **Exécuter les tests** - EN COURS
3. ⏳ **Corriger les erreurs** - À FAIRE
4. ⏳ **Atteindre 80% de couverture** - À FAIRE
5. ⏳ **Documenter les résultats** - À FAIRE

---

## 📝 Notes

- Les tests utilisent `RefreshDatabase` pour isoler chaque test
- Les factories permettent de créer rapidement des données de test
- Les permissions Spatie sont créées dans `setUp()`
- Sanctum est utilisé pour l'authentification dans les tests API
- Les tests sont indépendants et peuvent être exécutés dans n'importe quel ordre

---

**Date de création**: 2024  
**Phase**: 6 - Multi-vues & Visibilité  
**Statut**: Tests créés, prêts à exécuter  
**Total tests**: 47 tests couvrant 5 fichiers
