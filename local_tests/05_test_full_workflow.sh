#!/bin/bash

#######################################
# Script de test du workflow complet
# Teste: migrations + seeders + factories + API
# Auteur: Équipe BestQHSE
# Date: 2026-01-15
#######################################

set -e

echo "========================================="
echo "🎯 TEST DU WORKFLOW COMPLET"
echo "========================================="
echo ""

# Étape 1: Migrations
echo "📋 ÉTAPE 1/4: Test des migrations..."
./local_tests/01_test_migrations.sh

echo ""
echo "⏳ Pause de 2 secondes..."
sleep 2

# Étape 2: Seeders
echo ""
echo "📋 ÉTAPE 2/4: Test des seeders..."
./local_tests/02_test_seeders.sh

echo ""
echo "⏳ Pause de 2 secondes..."
sleep 2

# Étape 3: Factories
echo ""
echo "📋 ÉTAPE 3/4: Test des factories..."
./local_tests/03_test_factories.sh

echo ""
echo "⏳ Pause de 2 secondes..."
sleep 2

# Étape 4: API
echo ""
echo "📋 ÉTAPE 4/4: Démarrage du serveur et test API..."
cd backend
php artisan serve > /dev/null 2>&1 &
SERVER_PID=$!

echo "⏳ Attente du démarrage du serveur (5 secondes)..."
sleep 5

cd ..
./local_tests/04_test_api_endpoints.sh

# Arrêter le serveur
kill $SERVER_PID

echo ""
echo "========================================="
echo "✅ WORKFLOW COMPLET TERMINÉ AVEC SUCCÈS!"
echo "========================================="
