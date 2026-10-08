# 🚀 Commandes Rapides - Tests Phase 6

## ⚡ Exécution Rapide

```bash
cd /mnt/projets/Projets/Best_Experts_Group/LOGIQUALI/backend

# Option 1: Script automatique (RECOMMANDÉ)
./run-phase6-tests.sh

# Option 2: Tous les tests en une commande
php artisan test tests/Unit/Services/DocumentTypeConfigurationServiceTest.php tests/Unit/Services/CodeGenerationServiceTest.php tests/Unit/Services/DocumentCodeWorkflowServiceTest.php tests/Unit/Services/DocumentTypeConfigurationVisibilityServiceTest.php tests/Feature/Api/DocumentTypeConfigurationApiTest.php

# Option 3: Par dossier
php artisan test tests/Unit/Services/ tests/Feature/Api/DocumentTypeConfigurationApiTest.php
```

---

## 📋 Tests Individuels

```bash
# Test 1: DocumentTypeConfigurationService (6 tests)
php artisan test tests/Unit/Services/DocumentTypeConfigurationServiceTest.php

# Test 2: CodeGenerationService (7 tests)
php artisan test tests/Unit/Services/CodeGenerationServiceTest.php

# Test 3: DocumentCodeWorkflowService (8 tests)
php artisan test tests/Unit/Services/DocumentCodeWorkflowServiceTest.php

# Test 4: DocumentTypeConfigurationVisibilityService (11 tests)
php artisan test tests/Unit/Services/DocumentTypeConfigurationVisibilityServiceTest.php

# Test 5: API DocumentTypeConfiguration (15 tests)
php artisan test tests/Feature/Api/DocumentTypeConfigurationApiTest.php
```

---

## 🔍 Avec Options

```bash
# Avec détails
php artisan test tests/Unit/Services/ --verbose

# Arrêter au premier échec
php artisan test tests/Unit/Services/ --stop-on-failure

# Avec couverture
php artisan test tests/Unit/Services/ --coverage

# Avec couverture minimale 80%
php artisan test tests/Unit/Services/ --coverage --min=80

# Générer rapport HTML
php artisan test tests/Unit/Services/ --coverage-html=coverage

# Tests parallèles (plus rapide)
php artisan test tests/Unit/Services/ --parallel
```

---

## 🎯 Filtres

```bash
# Par nom de test
php artisan test --filter=it_generates_code_with_sequence

# Par classe
php artisan test --filter=CodeGenerationServiceTest

# Par groupe (si défini)
php artisan test --group=nomenclature
```

---

## 🔧 Dépannage

```bash
# Régénérer autoload
composer dump-autoload

# Vérifier configuration
php artisan config:clear
php artisan cache:clear

# Voir les logs
tail -f storage/logs/laravel.log

# Vérifier base de données
psql -U postgres -d bestqhse_test -c "SELECT 1"
```

---

## 📊 Rapport de Couverture

```bash
# Générer et ouvrir
php artisan test tests/Unit/Services/ --coverage-html=coverage
xdg-open coverage/index.html

# Rapport texte
php artisan test tests/Unit/Services/ --coverage-text

# Rapport Clover (CI/CD)
php artisan test tests/Unit/Services/ --coverage-clover=coverage.xml
```

---

## ✅ Validation Complète

```bash
# Tout exécuter avec couverture
php artisan test --coverage --min=80

# Si succès, générer le rapport
php artisan test --coverage-html=coverage

# Ouvrir le rapport
xdg-open coverage/index.html
```

---

## 🎊 Commande Finale

**Pour valider la Phase 6 complète** :

```bash
cd /mnt/projets/Projets/Best_Experts_Group/LOGIQUALI/backend && \
./run-phase6-tests.sh && \
php artisan test tests/Unit/Services/ --coverage --min=80 && \
echo "✅ Phase 6 - Tests validés avec succès !"
```

---

**Copier-coller cette commande dans votre terminal** ⬆️
