#!/bin/bash

# Script pour tester la création d'utilisateurs via l'API

API_URL="http://localhost:8000/api"
TOKEN="${SANCTUM_TOKEN:-your-token-here}"

# Simuler une création d'utilisateur
echo "=== Test création utilisateur ==="
echo "URL: $API_URL/enterprises/me/users"
echo ""

curl -X POST "$API_URL/enterprises/me/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jean Dupont",
    "username": "jean.dupont",
    "email": "jean.dupont@test.com",
    "user_type": "collaborator",
    "site_id": null
  }' \
  -w "\nStatus: %{http_code}\n"

echo ""
echo "=== Test avec site_id ==="

curl -X POST "$API_URL/enterprises/me/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Marie Martin",
    "username": "marie.martin",
    "email": "marie.martin@test.com",
    "user_type": "site_admin",
    "site_id": 1
  }' \
  -w "\nStatus: %{http_code}\n"

echo ""
echo "=== Test validation (email invalide) ==="

curl -X POST "$API_URL/enterprises/me/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Paul Durand",
    "username": "paul.durand",
    "email": "invalid-email",
    "user_type": "enterprise_admin",
    "site_id": null
  }' \
  -w "\nStatus: %{http_code}\n"
