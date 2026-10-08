<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CertificationsCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $certifications = [
            // ISO Management
            [
                'type' => 'ISO',
                'code' => '9001',
                'name' => 'ISO 9001:2015 - Système de Management de la Qualité',
                'description' => 'Norme internationale pour les systèmes de management de la qualité',
                'issuing_body' => 'ISO',
                'validity_years' => 3,
                'is_active' => true,
            ],
            [
                'type' => 'ISO',
                'code' => '14001',
                'name' => 'ISO 14001:2015 - Système de Management Environnemental',
                'description' => 'Norme pour les systèmes de management environnemental',
                'issuing_body' => 'ISO',
                'validity_years' => 3,
                'is_active' => true,
            ],
            [
                'type' => 'ISO',
                'code' => '45001',
                'name' => 'ISO 45001:2018 - Santé et Sécurité au Travail',
                'description' => 'Norme pour les systèmes de management de la santé et sécurité au travail',
                'issuing_body' => 'ISO',
                'validity_years' => 3,
                'is_active' => true,
            ],
            [
                'type' => 'ISO',
                'code' => '50001',
                'name' => 'ISO 50001:2018 - Management de l\'Énergie',
                'description' => 'Norme pour les systèmes de management de l\'énergie',
                'issuing_body' => 'ISO',
                'validity_years' => 3,
                'is_active' => true,
            ],
            [
                'type' => 'ISO',
                'code' => '27001',
                'name' => 'ISO 27001:2013 - Sécurité de l\'Information',
                'description' => 'Norme pour les systèmes de management de la sécurité de l\'information',
                'issuing_body' => 'ISO',
                'validity_years' => 3,
                'is_active' => true,
            ],
            [
                'type' => 'ISO',
                'code' => '22000',
                'name' => 'ISO 22000:2018 - Sécurité des Denrées Alimentaires',
                'description' => 'Norme pour les systèmes de management de la sécurité des denrées alimentaires',
                'issuing_body' => 'ISO',
                'validity_years' => 3,
                'is_active' => true,
            ],

            // Labels & Certifications Régionales
            [
                'type' => 'Label',
                'code' => 'OHSAS18001',
                'name' => 'OHSAS 18001 - Santé et Sécurité au Travail',
                'description' => 'Ancienne norme remplacée par ISO 45001',
                'issuing_body' => 'BSI',
                'validity_years' => 3,
                'is_active' => false, // Obsolète
            ],
            [
                'type' => 'Label',
                'code' => 'HACCP',
                'name' => 'HACCP - Hazard Analysis Critical Control Point',
                'description' => 'Système de gestion de la sécurité sanitaire des aliments',
                'issuing_body' => 'Codex Alimentarius',
                'validity_years' => null,
                'is_active' => true,
            ],
        ];

        foreach ($certifications as $certification) {
            DB::table('certifications_catalog')->insert(array_merge($certification, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
