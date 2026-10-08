<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AxeSeeder extends Seeder
{
    public function run(): void
    {
        $axes = [
            [
                'code' => 'Q',
                'name' => 'Qualité',
                'description' => 'Management de la Qualité (ISO 9001)',
                'color' => '#3B82F6', // Blue
                'icon' => 'CheckCircleIcon',
                'is_active' => true,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'HS',
                'name' => 'Santé & Sécurité',
                'description' => 'Management de la Santé et Sécurité au Travail (ISO 45001)',
                'color' => '#EF4444', // Red
                'icon' => 'ShieldCheckIcon',
                'is_active' => false, // Désactivé par défaut (SMQ simple)
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'E',
                'name' => 'Environnement',
                'description' => 'Management Environnemental (ISO 14001)',
                'color' => '#10B981', // Green
                'icon' => 'GlobeAltIcon',
                'is_active' => false, // Désactivé par défaut (SMQ simple)
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($axes as $axe) {
            $payload = $axe;
            unset($payload['created_at']);

            DB::table('axes')->updateOrInsert(
                ['code' => $axe['code']],
                $payload,
            );
        }
    }
}
