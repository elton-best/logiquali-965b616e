<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QhseSubModulesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Liaison sous-modules QHSE aux normes ISO...');

        // Récupérer les normes
        $iso9001 = DB::table('norms')->where('code', 'ISO 9001:2015')->first();
        $iso14001 = DB::table('norms')->where('code', 'ISO 14001:2015')->first();
        $iso45001 = DB::table('norms')->where('code', 'ISO 45001:2018')->first();
        $iso50001 = DB::table('norms')->where('code', 'ISO 50001:2018')->first();

        // Récupérer le module Support (Point 7)
        $moduleSupport = DB::table('modules')->where('code', 'support')->first();

        if (!$moduleSupport) {
            $this->command->error('❌ Module Support introuvable');
            return;
        }

        // ============================================
        // ISO 45001 - SOUS-MODULES SÉCURITÉ
        // ============================================
        if ($iso45001) {
            $subModules45001 = [
                ['code' => 'habilitations', 'name' => 'Habilitations du Personnel', 'route' => '/company/iso/securite/habilitations', 'icon' => 'mdi-certificate'],
                ['code' => 'epi', 'name' => 'Équipements de Protection Individuelle', 'route' => '/company/iso/securite/epi', 'icon' => 'mdi-shield-account'],
                ['code' => 'vgp', 'name' => 'Vérifications Réglementaires (VGP)', 'route' => '/company/iso/securite/vgp', 'icon' => 'mdi-clipboard-check'],
            ];

            foreach ($subModules45001 as $index => $sm) {
                $subModuleId = DB::table('sub_modules')->insertGetId([
                    'module_id' => $moduleSupport->id,
                    'code' => $sm['code'],
                    'name' => $sm['name'],
                    'route' => $sm['route'],
                    'icon' => $sm['icon'],
                    'order' => 100 + $index,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Lier à ISO 45001
                DB::table('norm_sub_module')->insert([
                    'norm_id' => $iso45001->id,
                    'sub_module_id' => $subModuleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info('   ✓ ISO 45001: 3 sous-modules créés');
        }

        // ============================================
        // ISO 14001 - SOUS-MODULES ENVIRONNEMENT
        // ============================================
        // Vérifier si les sous-modules existent déjà
        $existingSubModules = DB::table('sub_modules')
            ->where('module_id', $moduleSupport->id)
            ->whereIn('code', ['aspects_environnementaux', 'obligations_conformite_env'])
            ->pluck('code')
            ->toArray();

        if ($iso14001) {
            $subModules14001 = [
                ['code' => 'aspects_environnementaux', 'name' => 'Aspects Environnementaux', 'route' => '/company/iso/environnement/aspects', 'icon' => 'mdi-leaf'],
                ['code' => 'obligations_conformite_env', 'name' => 'Obligations de Conformité', 'route' => '/company/iso/environnement/obligations', 'icon' => 'mdi-gavel'],
            ];

            foreach ($subModules14001 as $index => $sm) {
                // Skip si déjà existant
                if (in_array($sm['code'], $existingSubModules)) {
                    continue;
                }
                
                $subModuleId = DB::table('sub_modules')->insertGetId([
                    'module_id' => $moduleSupport->id,
                    'code' => $sm['code'],
                    'name' => $sm['name'],
                    'route' => $sm['route'],
                    'icon' => $sm['icon'],
                    'order' => 200 + $index,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Lier à ISO 14001
                DB::table('norm_sub_module')->insertOrIgnore([
                    'norm_id' => $iso14001->id,
                    'sub_module_id' => $subModuleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info('   ✓ ISO 14001: 2 sous-modules créés');
        }

        // ============================================
        // ISO 50001 - SOUS-MODULES ÉNERGIE
        // ============================================
        if ($iso50001) {
            $subModules50001 = [
                ['code' => 'consommations_energie', 'name' => 'Consommations Énergétiques', 'route' => '/company/iso/energie/consommations', 'icon' => 'mdi-lightning-bolt'],
                ['code' => 'ipe', 'name' => 'Indicateurs Performance Énergétique', 'route' => '/company/iso/energie/ipe', 'icon' => 'mdi-gauge'],
            ];

            foreach ($subModules50001 as $index => $sm) {
                $subModuleId = DB::table('sub_modules')->insertGetId([
                    'module_id' => $moduleSupport->id,
                    'code' => $sm['code'],
                    'name' => $sm['name'],
                    'route' => $sm['route'],
                    'icon' => $sm['icon'],
                    'order' => 300 + $index,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Lier à ISO 50001
                DB::table('norm_sub_module')->insert([
                    'norm_id' => $iso50001->id,
                    'sub_module_id' => $subModuleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info('   ✓ ISO 50001: 2 sous-modules créés');
        }

        // Lier aussi à ISO 9001 (Support commun)
        if ($iso9001) {
            $allSubModules = DB::table('sub_modules')
                ->where('module_id', $moduleSupport->id)
                ->whereIn('code', [
                    'habilitations', 'epi', 'vgp',
                    'aspects_environnementaux', 'obligations_conformite_env',
                    'consommations_energie', 'ipe'
                ])
                ->get();

            foreach ($allSubModules as $sm) {
                DB::table('norm_sub_module')->insertOrIgnore([
                    'norm_id' => $iso9001->id,
                    'sub_module_id' => $sm->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->command->info('   ✓ ISO 9001: Sous-modules QHSE liés');
        }

        $this->command->info('✅ Sous-modules QHSE configurés avec succès!');
    }
}
