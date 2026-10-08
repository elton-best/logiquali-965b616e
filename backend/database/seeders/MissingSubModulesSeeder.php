<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MissingSubModulesSeeder extends Seeder
{
    public function run(): void
    {
        $subModules = [
            // Point 4 - Context
            [
                'code' => 'parties_interessees',
                'name' => 'Parties intéressées',
                'description' => 'Identification et analyse des parties intéressées pertinentes',
                'icon' => 'mdi-account-group',
                'order' => 2,
                'iso_module_code' => 'contexte',
            ],
            [
                'code' => 'domaine_application',
                'name' => 'Domaine d\'application',
                'description' => 'Définition du périmètre et domaine d\'application du SMI',
                'icon' => 'mdi-map-marker-radius',
                'order' => 3,
                'iso_module_code' => 'contexte',
            ],
            [
                'code' => 'enjeux_internes_externes',
                'name' => 'Enjeux internes et externes',
                'description' => 'Analyse des enjeux internes et externes pertinents',
                'icon' => 'mdi-scale-balance',
                'order' => 4,
                'iso_module_code' => 'contexte',
            ],
        ];

        foreach ($subModules as $subModule) {
            // Récupérer l'ID du module
            $module = DB::table('modules')
                ->where('code', $subModule['iso_module_code'])
                ->first();

            if (!$module) {
                $this->command->warn("Module '{$subModule['iso_module_code']}' non trouvé");
                continue;
            }

            // Vérifier si existe déjà
            $exists = DB::table('sub_modules')
                ->where('code', $subModule['code'])
                ->exists();

            if ($exists) {
                $this->command->info("Sous-module '{$subModule['code']}' existe déjà");
                continue;
            }

            // Insérer le sous-module
            DB::table('sub_modules')->insert([
                'code' => $subModule['code'],
                'name' => $subModule['name'],
                'description' => $subModule['description'],
                'icon' => $subModule['icon'],
                'order' => $subModule['order'],
                'module_id' => $module->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("✓ Sous-module '{$subModule['name']}' créé");
        }
    }
}
