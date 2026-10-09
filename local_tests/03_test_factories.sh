#!/bin/bash

#######################################
# Script de test des factories
# Teste la génération de données avec Faker
# Auteur: Équipe BestQHSE
# Date: 2026-01-15
#######################################

set -e

echo "========================================="
echo "🏭 TEST DES FACTORIES"
echo "========================================="
echo ""

cd backend

echo "📋 Étape 1: Génération d'entreprises..."
php artisan tinker --execute="App\Models\Enterprise::factory(5)->create();"

echo ""
echo "📋 Étape 2: Génération de sites..."
php artisan tinker --execute="App\Models\Site::factory(10)->create();"

echo ""
echo "📋 Étape 3: Génération d'utilisateurs..."
php artisan tinker --execute="App\Models\User::factory(20)->create();"

echo ""
echo "📋 Étape 4: Génération de processus..."
php artisan tinker --execute="App\Models\Process::factory(15)->create();"

echo ""
echo "📋 Étape 5: Génération de documents..."
php artisan tinker --execute="App\Models\Document::factory(30)->create();"

echo ""
echo "📋 Étape 6: Génération d'audits..."
php artisan tinker --execute="App\Models\Audit::factory(10)->create();"

echo ""
echo "📋 Étape 7: Génération de risques..."
php artisan tinker --execute="App\Models\Risk::factory(25)->create();"

echo ""
echo "📋 Étape 8: Génération d'actions..."
php artisan tinker --execute="App\Models\Action::factory(40)->create();"

echo ""
echo "📋 Étape 9: Génération de NC..."
php artisan tinker --execute="App\Models\NonConformity::factory(20)->create();"

echo ""
echo "📊 Récapitulatif des données générées:"
echo "Entreprises: $(php artisan tinker --execute='echo App\Models\Enterprise::count();')"
echo "Sites: $(php artisan tinker --execute='echo App\Models\Site::count();')"
echo "Utilisateurs: $(php artisan tinker --execute='echo App\Models\User::count();')"
echo "Processus: $(php artisan tinker --execute='echo App\Models\Process::count();')"
echo "Documents: $(php artisan tinker --execute='echo App\Models\Document::count();')"
echo "Audits: $(php artisan tinker --execute='echo App\Models\Audit::count();')"
echo "Risques: $(php artisan tinker --execute='echo App\Models\Risk::count();')"
echo "Actions: $(php artisan tinker --execute='echo App\Models\Action::count();')"
echo "NC: $(php artisan tinker --execute='echo App\Models\NonConformity::count();')"

echo ""
echo "========================================="
echo "✅ Test des factories terminé!"
echo "========================================="
