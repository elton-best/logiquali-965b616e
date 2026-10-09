#!/bin/bash

#######################################
# Script de test des seeders
# Teste le seeding des permissions et super admin
# Auteur: Équipe BestQHSE
# Date: 2026-01-15
#######################################

set -e

echo "========================================="
echo "🌱 TEST DES SEEDERS"
echo "========================================="
echo ""

cd backend

echo "📋 Étape 1: Reset et migration..."
php artisan migrate:fresh --force

echo ""
echo "📋 Étape 2: Exécution des seeders..."
php artisan db:seed --force

echo ""
echo "📋 Étape 3: Vérification des données créées..."
echo ""

echo "Nombre de permissions créées:"
php artisan tinker --execute="echo App\Models\Permission::count();"

echo ""
echo "Nombre d'utilisateurs créés:"
php artisan tinker --execute="echo App\Models\User::count();"

echo ""
echo "Super Admin créé:"
php artisan tinker --execute="echo App\Models\User::where('user_type', 'admin')->first()->email;"

echo ""
echo "========================================="
echo "✅ Test des seeders terminé!"
echo "========================================="
