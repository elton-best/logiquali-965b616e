<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NormModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Vider table avant insertion
        DB::table('norm_module')->truncate();

        // Modules communs à toutes les normes (1-7 sauf 5)
        $commonModules = [1, 2, 3, 4, 6, 7];
        $allNorms = [1, 2, 3, 4, 5];

        // Lier modules communs à toutes les normes
        foreach ($allNorms as $normId) {
            foreach ($commonModules as $moduleId) {
                DB::table('norm_module')->insert([
                    'norm_id' => $normId,
                    'module_id' => $moduleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Module 5 (Réalisation) uniquement pour ISO 9001
        DB::table('norm_module')->insert([
            'norm_id' => 1,
            'module_id' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
