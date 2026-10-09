#!/bin/bash

# Script de test pour des endpoints spécifiques de l'API
# Utilise curl pour tester les endpoints

BASE_URL="http://localhost:8000/api"
TOKEN=""

echo "========================================"
echo "   API ENDPOINT TESTS"
echo "========================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Function to test endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo -e "${BLUE}Testing: ${description}${NC}"
    echo "Endpoint: ${method} ${endpoint}"
    
    if [ -z "$data" ]; then
        response=$(curl -s -X ${method} \
            -H "Accept: application/vnd.api+json" \
            -H "Authorization: Bearer ${TOKEN}" \
            "${BASE_URL}${endpoint}")
    else
        response=$(curl -s -X ${method} \
            -H "Content-Type: application/vnd.api+json" \
            -H "Accept: application/vnd.api+json" \
            -H "Authorization: Bearer ${TOKEN}" \
            -d "${data}" \
            "${BASE_URL}${endpoint}")
    fi
    
    echo "Response: ${response}" | jq '.' 2>/dev/null || echo "${response}"
    echo ""
}

# Login to get token
echo -e "${BLUE}Logging in...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"admin@BestQHSE.com","password":"admin123"}' \
    "${BASE_URL}/login")

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.token' 2>/dev/null)

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo -e "${RED}Failed to authenticate${NC}"
    echo "Response: ${LOGIN_RESPONSE}"
    exit 1
fi

echo -e "${GREEN}Authenticated successfully${NC}"
echo ""

# Test endpoints
test_endpoint "GET" "/users" "" "List all users"
test_endpoint "GET" "/responsibilities" "" "List all responsibilities"
test_endpoint "GET" "/entites" "" "List all entites"
test_endpoint "GET" "/processus" "" "List all processus"
test_endpoint "GET" "/documents" "" "List all documents"
test_endpoint "GET" "/non-conformites" "" "List all non-conformities"

echo "========================================"
echo -e "${GREEN}API tests completed${NC}"
echo "========================================"
