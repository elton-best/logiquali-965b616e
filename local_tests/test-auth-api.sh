#!/bin/bash

# Script de test de l'API d'authentification BestQHSE
# Utilise curl pour tester les endpoints

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8000/api"
TOKEN=""

echo -e "${YELLOW}=== Tests de l'API d'Authentification BestQHSE ===${NC}\n"

# Fonction pour afficher les résultats
print_result() {
    local status=$1
    local title=$2
    local response=$3
    
    if [ "$status" -eq 200 ] || [ "$status" -eq 201 ]; then
        echo -e "${GREEN}✓ $title (Status: $status)${NC}"
    else
        echo -e "${RED}✗ $title (Status: $status)${NC}"
    fi
    echo -e "Response: $response\n"
}

# Test 1: Inscription d'un client
echo -e "${YELLOW}Test 1: Inscription d'un client${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/register/client" \
    -H "Content-Type: application/json" \
    -d '{
        "username": "test_client_'$(date +%s)'",
        "email": "test'$(date +%s)'@example.com",
        "password": "SecurePass123!",
        "password_confirmation": "SecurePass123!",
        "phone": "+229 XX XX XX XX"
    }')

HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
BODY=$(echo "$RESPONSE" | sed '$d')
print_result "$HTTP_CODE" "Inscription Client" "$BODY"

# Extraire le token si disponible
if [ "$HTTP_CODE" -eq 201 ]; then
    TOKEN=$(echo "$BODY" | grep -o '"token":"[^"]*"' | sed 's/"token":"\(.*\)"/\1/')
fi

# Test 2: Connexion avec identifiants invalides
echo -e "${YELLOW}Test 2: Connexion avec identifiants invalides${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d '{
        "email": "invalid@example.com",
        "password": "wrongpassword"
    }')

HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
BODY=$(echo "$RESPONSE" | sed '$d')
print_result "$HTTP_CODE" "Login Invalide (devrait échouer)" "$BODY"

# Test 3: Informations utilisateur sans token
echo -e "${YELLOW}Test 3: Accès /auth/me sans token${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL/auth/me")

HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
BODY=$(echo "$RESPONSE" | sed '$d')
print_result "$HTTP_CODE" "Accès non authentifié (devrait échouer)" "$BODY"

# Test 4: Informations utilisateur avec token (si disponible)
if [ -n "$TOKEN" ]; then
    echo -e "${YELLOW}Test 4: Accès /auth/me avec token${NC}"
    RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL/auth/me" \
        -H "Authorization: Bearer $TOKEN")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    print_result "$HTTP_CODE" "Récupération infos utilisateur" "$BODY"
    
    # Test 5: Rafraîchir le token
    echo -e "${YELLOW}Test 5: Rafraîchir le token${NC}"
    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/refresh-token" \
        -H "Authorization: Bearer $TOKEN")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    print_result "$HTTP_CODE" "Rafraîchissement du token" "$BODY"
    
    # Mettre à jour le token
    if [ "$HTTP_CODE" -eq 200 ]; then
        TOKEN=$(echo "$BODY" | grep -o '"token":"[^"]*"' | sed 's/"token":"\(.*\)"/\1/')
    fi
    
    # Test 6: Déconnexion
    echo -e "${YELLOW}Test 6: Déconnexion${NC}"
    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/logout" \
        -H "Authorization: Bearer $TOKEN")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    print_result "$HTTP_CODE" "Déconnexion" "$BODY"
    
    # Test 7: Accès après déconnexion
    echo -e "${YELLOW}Test 7: Accès /auth/me après déconnexion${NC}"
    RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL/auth/me" \
        -H "Authorization: Bearer $TOKEN")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    print_result "$HTTP_CODE" "Accès avec token révoqué (devrait échouer)" "$BODY"
fi

# Test 8: Mot de passe oublié
echo -e "${YELLOW}Test 8: Mot de passe oublié${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/forgot-password" \
    -H "Content-Type: application/json" \
    -d '{
        "email": "test@example.com"
    }')

HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
BODY=$(echo "$RESPONSE" | sed '$d')
print_result "$HTTP_CODE" "Demande réinitialisation" "$BODY"

# Test 9: Inscription entreprise (validation uniquement)
echo -e "${YELLOW}Test 9: Validation inscription entreprise${NC}"
echo -e "${YELLOW}Note: Les champs requis incluent les documents (non testés en ligne de commande)${NC}"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/register/enterprise" \
    -H "Content-Type: application/json" \
    -d '{
        "enterprise_name": "Test SARL",
        "email": "entreprise'$(date +%s)'@example.com",
        "registration_number": "RC/TEST/'$(date +%s)'",
        "address": "123 Test Street",
        "username": "admin_test_'$(date +%s)'",
        "password": "SecurePass123!",
        "password_confirmation": "SecurePass123!",
        "phone": "+229 XX XX XX XX"
    }')

HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
BODY=$(echo "$RESPONSE" | sed '$d')
print_result "$HTTP_CODE" "Inscription Entreprise" "$BODY"

echo -e "\n${YELLOW}=== Tests terminés ===${NC}"
