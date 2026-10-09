#!/bin/bash

# Script de test pour BestQHSE API
# Execute tous les tests unitaires et fonctionnels

echo "========================================"
echo "   BestQHSE - TESTS SUITE"
echo "========================================"
echo ""

# Couleurs pour output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Change to backend directory
cd "$(dirname "$0")/../backend" || exit 1

echo -e "${YELLOW}Step 1: Checking environment...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${RED}Error: .env file not found${NC}"
    exit 1
fi
echo -e "${GREEN}Environment OK${NC}"
echo ""

echo -e "${YELLOW}Step 2: Installing dependencies...${NC}"
composer install --quiet
echo -e "${GREEN}Dependencies installed${NC}"
echo ""

echo -e "${YELLOW}Step 3: Running database migrations (fresh)...${NC}"
php artisan migrate:fresh --seed --force
echo -e "${GREEN}Database ready${NC}"
echo ""

echo -e "${YELLOW}Step 4: Running PHPUnit tests...${NC}"
echo "========================================"
php artisan test --parallel
TEST_EXIT_CODE=$?
echo "========================================"
echo ""

if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
else
    echo -e "${RED}Some tests failed. Exit code: $TEST_EXIT_CODE${NC}"
fi

echo ""
echo -e "${YELLOW}Step 5: Generating test coverage report (optional)...${NC}"
# Uncomment to generate coverage
# php artisan test --coverage --min=80

echo ""
echo "========================================"
echo "   TEST SUMMARY"
echo "========================================"
php artisan test --compact

exit $TEST_EXIT_CODE
