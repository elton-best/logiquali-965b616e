<?php

namespace Database\Seeders;

use App\Models\Norm;
use Illuminate\Database\Seeder;

class NormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $norms = [
            [
                'code' => 'ISO 9001:2015',
                'name' => 'Management de la qualité',
                'description' => 'Systèmes de management de la qualité - Exigences',
                'domain' => 'quality',
                'status' => 'published',
            ],
            [
                'code' => 'ISO 14001:2015',
                'name' => 'Management environnemental',
                'description' => 'Systèmes de management environnemental - Exigences et lignes directrices pour son utilisation',
                'domain' => 'environment',
                'status' => 'published',
            ],
            [
                'code' => 'ISO 45001:2018',
                'name' => 'Santé et sécurité au travail',
                'description' => 'Systèmes de management de la santé et de la sécurité au travail - Exigences et lignes directrices pour leur utilisation',
                'domain' => 'security',
                'status' => 'published',
            ],
            [
                'code' => 'ISO 27001:2022',
                'name' => 'Sécurité de l\'information',
                'description' => 'Techniques de sécurité - Systèmes de management de la sécurité de l\'information - Exigences',
                'domain' => 'security',
                'status' => 'published',
            ],
            [
                'code' => 'ISO 22000:2018',
                'name' => 'Sécurité des denrées alimentaires',
                'description' => 'Systèmes de management de la sécurité des denrées alimentaires - Exigences pour tout organisme appartenant à la chaîne alimentaire',
                'domain' => 'food_safety',
                'status' => 'published',
            ],
            [
                'code' => 'ISO 50001:2018',
                'name' => 'Management de l\'énergie',
                'description' => 'Systèmes de management de l\'énergie - Exigences et recommandations pour la mise en œuvre',
                'domain' => 'environment',
                'status' => 'published',
            ],
        ];

        foreach ($norms as $normData) {
            Norm::updateOrCreate(
                ['code' => $normData['code']],
                $normData
            );
        }
    }
}
