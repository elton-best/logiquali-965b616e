<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = DB::table('modules')->where('code', 'realisation')->value('id');
        if (!$moduleId) {
            return;
        }

        $iso9001Id = DB::table('norms')
            ->whereIn('code', ['ISO 9001:2015', 'ISO9001'])
            ->orderBy('id')
            ->value('id');

        $subModules = [
            [
                'code' => 'planification_maitrise_operationnelle',
                'name' => 'Planification et maîtrise opérationnelle',
                'route' => '/company/iso/operations/operational-planning-control',
                'icon' => 'mdi-clipboard-check-outline',
                'order' => 3,
            ],
            [
                'code' => 'exigences_produits_services',
                'name' => 'Exigences relatives aux produits et services',
                'route' => '/company/iso/operations/product-service-requirements',
                'icon' => 'mdi-format-list-checks',
                'order' => 4,
            ],
            [
                'code' => 'conception_developpement_produits_services',
                'name' => 'Conception et développement de produit et service',
                'route' => '/company/iso/operations/design-development-products-services',
                'icon' => 'mdi-ruler-square-compass',
                'order' => 5,
            ],
            [
                'code' => 'gestion_prestataires',
                'name' => 'Gestion des prestataires',
                'route' => '/company/iso/operations/provider-management',
                'icon' => 'mdi-account-tie-hat',
                'order' => 6,
            ],
            [
                'code' => 'production_prestation_service',
                'name' => 'Production et prestation de service',
                'route' => '/company/iso/operations/production-service-provision',
                'icon' => 'mdi-factory',
                'order' => 7,
            ],
            [
                'code' => 'liberation_produits_services',
                'name' => 'Libération des produits et services',
                'route' => '/company/iso/operations/release-products-services',
                'icon' => 'mdi-package-variant-closed-check',
                'order' => 8,
            ],
            [
                'code' => 'maitrise_sorties_non_conformes',
                'name' => 'Maîtrise des éléments de sortie non-conformes',
                'route' => '/company/iso/operations/control-nonconforming-outputs',
                'icon' => 'mdi-alert-circle-outline',
                'order' => 9,
            ],
        ];

        foreach ($subModules as $subModule) {
            $existingId = DB::table('sub_modules')
                ->where('code', $subModule['code'])
                ->value('id');

            if ($existingId) {
                DB::table('sub_modules')
                    ->where('id', $existingId)
                    ->update([
                        'module_id' => $moduleId,
                        'name' => $subModule['name'],
                        'route' => $subModule['route'],
                        'icon' => $subModule['icon'],
                        'order' => $subModule['order'],
                        'is_active' => true,
                        'is_common' => false,
                        'updated_at' => now(),
                    ]);

                $subModuleId = $existingId;
            } else {
                $subModuleId = DB::table('sub_modules')->insertGetId([
                    'module_id' => $moduleId,
                    'code' => $subModule['code'],
                    'name' => $subModule['name'],
                    'description' => null,
                    'icon' => $subModule['icon'],
                    'route' => $subModule['route'],
                    'order' => $subModule['order'],
                    'is_active' => true,
                    'is_common' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($iso9001Id) {
                $exists = DB::table('norm_sub_module')
                    ->where('norm_id', $iso9001Id)
                    ->where('sub_module_id', $subModuleId)
                    ->exists();

                if (!$exists) {
                    DB::table('norm_sub_module')->insert([
                        'norm_id' => $iso9001Id,
                        'sub_module_id' => $subModuleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // No destructive rollback to avoid removing in-production catalog customizations.
    }
};

