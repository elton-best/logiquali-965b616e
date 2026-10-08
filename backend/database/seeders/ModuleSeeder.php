<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['name' => 'Contexte de l\'organisme', 'code' => 'contexte', 'icon' => 'mdi-domain', 'iso_point' => 4, 'order' => 1],
            ['name' => 'Leadership', 'code' => 'leadership', 'icon' => 'mdi-account-tie', 'iso_point' => 5, 'order' => 2],
            ['name' => 'Planification', 'code' => 'planification', 'icon' => 'mdi-calendar-check', 'iso_point' => 6, 'order' => 3],
            ['name' => 'Support', 'code' => 'support', 'icon' => 'mdi-lifebuoy', 'iso_point' => 7, 'order' => 4],
            ['name' => 'Réalisation des activités opérationnelles', 'code' => 'realisation', 'icon' => 'mdi-cog', 'iso_point' => 8, 'order' => 5],
            ['name' => 'Évaluation des performances', 'code' => 'evaluation', 'icon' => 'mdi-chart-line', 'iso_point' => 9, 'order' => 6],
            ['name' => 'Amélioration', 'code' => 'amelioration', 'icon' => 'mdi-trending-up', 'iso_point' => 10, 'order' => 7],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(
                ['code' => $module['code']],
                array_merge($module, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }
}
