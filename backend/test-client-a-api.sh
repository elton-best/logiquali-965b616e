#!/bin/bash

# Script de test des API pour Client A
# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

API_URL="http://localhost:8000/api/v1"
TOKEN=""

echo "========================================="
echo "Test des API Client A - BestQHSE"
echo "========================================="

# Test 1: Login pour obtenir un token
echo -e "\n${YELLOW}1. Test Login${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "jipisemyde@mailinator.com",
    "password": "Pa$$w0rd!"
  }')

echo "$LOGIN_RESPONSE" | jq '.'

# Extraire le token (adapter selon la structure de réponse)
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token // .token // empty')

if [ -z "$TOKEN" ]; then
  echo -e "${RED}❌ Erreur: Impossible d'obtenir le token${NC}"
  echo "Essayez de créer un utilisateur de test d'abord"
  exit 1
fi

echo -e "${GREEN}✓ Token obtenu: ${TOKEN:0:30}...${NC}"

# Test 2: Dashboard Stats
echo -e "\n${YELLOW}2. Test Dashboard Stats (GET /api/v1/dashboard/stats)${NC}"
curl -s -X GET "$API_URL/dashboard/stats" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

# Test 3: Dashboard KPIs
echo -e "\n${YELLOW}3. Test Dashboard KPIs (GET /api/v1/dashboard/kpis)${NC}"
curl -s -X GET "$API_URL/dashboard/kpis" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

# Test 4: Dashboard Charts - Non-Conformities
echo -e "\n${YELLOW}4. Test Dashboard Charts - Non-Conformities (GET /api/v1/dashboard/charts/non-conformities)${NC}"
curl -s -X GET "$API_URL/dashboard/charts/non-conformities" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

# Test 5: Liste des Sites
echo -e "\n${YELLOW}5. Test Liste Sites (GET /api/v1/sites)${NC}"
SITES_RESPONSE=$(curl -s -X GET "$API_URL/sites" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$SITES_RESPONSE" | jq '.'

# Extraire un ID de site pour les tests suivants
SITE_ID=$(echo "$SITES_RESPONSE" | jq -r '.data[0].id // empty')

if [ ! -z "$SITE_ID" ]; then
  # Test 6: Détails d'un site
  echo -e "\n${YELLOW}6. Test Détails Site (GET /api/v1/sites/$SITE_ID)${NC}"
  curl -s -X GET "$API_URL/sites/$SITE_ID" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" | jq '.'

  # Test 7: Toggle Active Site
  echo -e "\n${YELLOW}7. Test Toggle Active Site (POST /api/v1/sites/$SITE_ID/toggle-active)${NC}"
  curl -s -X POST "$API_URL/sites/$SITE_ID/toggle-active" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" | jq '.'
fi

# Test 8: Liste des Users
echo -e "\n${YELLOW}8. Test Liste Users (GET /api/v1/users)${NC}"
USERS_RESPONSE=$(curl -s -X GET "$API_URL/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "$USERS_RESPONSE" | jq '.'

# Test 9: Get Roles
echo -e "\n${YELLOW}9. Test Get Roles (GET /api/v1/roles)${NC}"
curl -s -X GET "$API_URL/roles" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

# Test 10: Update Profile
echo -e "\n${YELLOW}10. Test Update Profile (PUT /api/v1/auth/profile)${NC}"
curl -s -X PUT "$API_URL/auth/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "+33 6 12 34 56 78"
  }' | jq '.'

echo -e "\n${GREEN}=========================================${NC}"
echo -e "${GREEN}Tests terminés !${NC}"
echo -e "${GREEN}=========================================${NC}"
