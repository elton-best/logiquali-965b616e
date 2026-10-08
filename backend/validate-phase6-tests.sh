#!/bin/bash

# Script de validation des tests Phase 6
# Ce script exécute uniquement les tests de Phase 6 pour valider qu'ils passent tous

echo "========================================="
echo "  VALIDATION TESTS PHASE 6"
echo "========================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Compteurs
TOTAL=0
PASSED=0
FAILED=0

# Fonction pour exécuter un test
run_test() {
    local test_file=$1
    local test_name=$2
    
    echo -n "Testing $test_name... "
    
    if php artisan test "$test_file" 2>&1 | grep -q "Tests:.*passed"; then
        echo -e "${GREEN}✓ PASSED${NC}"
        ((PASSED++))
    else
        echo -e "${RED}✗ FAILED${NC}"
        ((FAILED++))
    fi
    
    ((TOTAL++))
}

echo "Exécution des tests Phase 6..."
echo ""

# Tests Unit Services
run_test "tests/Unit/Services/CodeGenerationServiceTest.php" "CodeGenerationService"
run_test "tests/Unit/Services/DocumentCodeWorkflowServiceTest.php" "DocumentCodeWorkflowService"
run_test "tests/Unit/Services/DocumentTypeConfigurationServiceTest.php" "DocumentTypeConfigurationService"
run_test "tests/Unit/Services/DocumentTypeConfigurationVisibilityServiceTest.php" "DocumentTypeConfigurationVisibilityService"

# Tests Feature API
run_test "tests/Feature/Api/DocumentTypeConfigurationApiTest.php" "DocumentTypeConfigurationApi"

echo ""
echo "========================================="
echo "  RÉSULTATS"
echo "========================================="
echo -e "Total:  $TOTAL tests"
echo -e "${GREEN}Passés: $PASSED tests${NC}"
echo -e "${RED}Échoués: $FAILED tests${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ TOUS LES TESTS PHASE 6 PASSENT !${NC}"
    echo ""
    exit 0
else
    echo -e "${RED}✗ Certains tests ont échoué${NC}"
    echo ""
    exit 1
fi
