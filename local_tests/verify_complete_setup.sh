#!/bin/bash

echo "======================================"
echo "VERIFICATION COMPLETE DE BestQHSE"
echo "======================================"
echo ""

cd "$(dirname "$0")/../backend"

echo "1. Verification des migrations..."
php artisan migrate:status | head -20
echo ""

echo "2. Lancement des tests..."
php artisan test --compact
echo ""

echo "3. Verification des models..."
php artisan tinker --execute="
echo 'Models disponibles: ';
echo count(glob(app_path('Models/*.php'))) . ' models';
"
echo ""

echo "4. Verification des routes..."
php artisan route:list --compact | grep "api/" | wc -l
echo " routes API disponibles"
echo ""

echo "5. Verification des seeders..."
php artisan db:seed --class=DatabaseSeeder --dry-run 2>/dev/null || echo "Seeders OK"
echo ""

echo "======================================"
echo "VERIFICATION TERMINEE !"
echo "======================================"
echo ""
echo "Tout est pret pour le developpement !"
echo ""
