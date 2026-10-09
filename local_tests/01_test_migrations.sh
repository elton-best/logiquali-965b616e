#!/bin/bash

#######################################
# Script de test des migrations
# Teste la création de toutes les tables
# Auteur: Équipe BestQHSE
# Date: 2026-01-15
#######################################

set -e

echo "========================================="
echo "🔄 TEST DES MIGRATIONS"
echo "========================================="
echo ""

cd backend

echo "📋 Étape 1: Reset de la base de données..."
php artisan migrate:fresh --force

echo ""
echo "✅ Migrations exécutées avec succès!"
echo ""

echo "📋 Étape 2: Vérification du statut des migrations..."
php artisan migrate:status

echo ""
echo "========================================="
echo "✅ Test des migrations terminé!"
echo "========================================="
