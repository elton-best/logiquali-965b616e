#!/bin/bash

# Script pour exécuter tous les tests de la Phase 6 - Nomenclature Flexible
# Usage: ./run-phase6-tests.sh

cd /mnt/projets/Projets/Best_Experts_Group/LOGIQUALI/backend

echo "=========================================="
echo "🧪 TESTS PHASE 6 - NOMENCLATURE FLEXIBLE"
echo "=========================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Compteurs
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

echo "📋 Tests Unitaires - Services"
echo "----------------------------------------"

# Test 1: DocumentTypeConfigurationService
echo -n "1️⃣  DocumentTypeConfigurationService... "
if php artisan test tests/Unit/Services/DocumentTypeConfigurationServiceTest.php --quiet 2>&1 | grep -q "PASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED_TESTS++))
else
    echo -e "${RED}✗ FAIL${NC}"
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# Test 2: CodeGenerationService
echo -n "2️⃣  CodeGenerationService... "
if php artisan test tests/Unit/Services/CodeGenerationServiceTest.php --quiet 2>&1 | grep -q "PASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED_TESTS++))
else
    echo -e "${RED}✗ FAIL${NC}"
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# Test 3: DocumentCodeWorkflowService
echo -n "3️⃣  DocumentCodeWorkflowService... "
if php artisan test tests/Unit/Services/DocumentCodeWorkflowServiceTest.php --quiet 2>&1 | grep -q "PASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED_TESTS++))
else
    echo -e "${RED}✗ FAIL${NC}"
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# Test 4: DocumentImportService (déjà existant)
echo -n "4️⃣  DocumentImportService... "
if php artisan test tests/Unit/Services/DocumentImportServiceTest.php --quiet 2>&1 | grep -q "PASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED_TESTS++))
else
    echo -e "${RED}✗ FAIL${NC}"
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

# Test 5: DocumentTypeConfigurationVisibilityService
echo -n "5️⃣  DocumentTypeConfigurationVisibilityService... "
if php artisan test tests/Unit/Services/DocumentTypeConfigurationVisibilityServiceTest.php --quiet 2>&1 | grep -q "PASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED_TESTS++))
else
    echo -e "${RED}✗ FAIL${NC}"
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

echo ""
echo "📋 Tests d'Intégration - API"
echo "----------------------------------------"

# Test 6: DocumentTypeConfiguration API
echo -n "6️⃣  DocumentTypeConfiguration API... "
if php artisan test tests/Feature/Api/DocumentTypeConfigurationApiTest.php --quiet 2>&1 | grep -q "PASS"; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED_TESTS++))
else
    echo -e "${RED}✗ FAIL${NC}"
    ((FAILED_TESTS++))
fi
((TOTAL_TESTS++))

echo ""
echo "=========================================="
echo "📊 RÉSULTATS"
echo "=========================================="
echo "Total: $TOTAL_TESTS tests"
echo -e "${GREEN}Réussis: $PASSED_TESTS${NC}"
echo -e "${RED}Échoués: $FAILED_TESTS${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ TOUS LES TESTS SONT PASSÉS !${NC}"
    echo ""
    exit 0
else
    echo ""
    echo -e "${RED}❌ CERTAINS TESTS ONT ÉCHOUÉ${NC}"
    echo ""
    echo "Pour voir les détails des erreurs, exécutez:"
    echo "php artisan test tests/Unit/Services/ --verbose"
    echo ""
    exit 1
fi
