#!/bin/bash

#######################################
# Script de test des endpoints API
# Teste les routes avec cURL
# Auteur: Équipe BestQHSE
# Date: 2026-01-15
#######################################

set -e

BASE_URL="http://localhost:8000/api"
HEADERS="Content-Type: application/vnd.api+json"

echo "========================================="
echo "🚀 TEST DES ENDPOINTS API"
echo "========================================="
echo ""

echo "📋 Configuration:"
echo "Base URL: $BASE_URL"
echo ""

echo "========================================="
echo "1️⃣ Test GET /api/users"
echo "========================================="
curl -s -H "$HEADERS" "$BASE_URL/users" | jq '.meta'

echo ""
echo "========================================="
echo "2️⃣ Test GET /api/enterprises"
echo "========================================="
curl -s -H "$HEADERS" "$BASE_URL/enterprises" | jq '.meta'

echo ""
echo "========================================="
echo "3️⃣ Test GET /api/sites"
echo "========================================="
curl -s -H "$HEADERS" "$BASE_URL/sites" | jq '.meta'

echo ""
echo "========================================="
echo "4️⃣ Test GET /api/processes"
echo "========================================="
curl -s -H "$HEADERS" "$BASE_URL/processes" | jq '.meta'

echo ""
echo "========================================="
echo "5️⃣ Test GET /api/documents"
echo "========================================="
curl -s -H "$HEADERS" "$BASE_URL/documents" | jq '.meta'

echo ""
echo "========================================="
echo "6️⃣ Test GET /api/audits"
echo "========================================="
curl -s -H "$HEADERS" "$BASE_URL/audits" | jq '.meta'

echo ""
echo "========================================="
echo "7️⃣ Test GET /api/permissions"
echo "========================================="
curl -s -H "$HEADERS" "$BASE_URL/permissions" | jq '.meta'

echo ""
echo "========================================="
echo "✅ Test des endpoints terminé!"
echo "========================================="
