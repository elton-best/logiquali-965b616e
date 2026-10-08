<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModulesSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            // Qualité (9 modules)
            ['identifier' => 'politique_qualite', 'name' => 'Politique Qualité', 'category' => 'qualite', 'route' => '/company/iso/quality/politique', 'icon' => 'mdi-file-document-outline', 'order' => 1],
            ['identifier' => 'objectifs_qualite', 'name' => 'Objectifs Qualité', 'category' => 'qualite', 'route' => '/company/iso/quality/objectifs', 'icon' => 'mdi-target', 'order' => 2],
            ['identifier' => 'indicateurs', 'name' => 'Indicateurs', 'category' => 'qualite', 'route' => '/company/iso/quality/indicateurs', 'icon' => 'mdi-chart-line', 'order' => 3],
            ['identifier' => 'risques_opportunites', 'name' => 'Risques et Opportunités', 'category' => 'qualite', 'route' => '/company/iso/quality/risques', 'icon' => 'mdi-alert-circle-outline', 'order' => 4],
            ['identifier' => 'planification_actions', 'name' => 'Planification des Actions', 'category' => 'qualite', 'route' => '/company/iso/quality/planification', 'icon' => 'mdi-calendar-check', 'order' => 5],
            ['identifier' => 'informations_documentees', 'name' => 'Informations Documentées', 'category' => 'qualite', 'route' => '/company/iso/support/document-inventory', 'icon' => 'mdi-folder-multiple-outline', 'order' => 6],
            ['identifier' => 'audits_internes', 'name' => 'Audits Internes', 'category' => 'qualite', 'route' => '/company/iso/quality/audits', 'icon' => 'mdi-clipboard-check-outline', 'order' => 7],
            ['identifier' => 'revue_direction', 'name' => 'Revue de Direction', 'category' => 'qualite', 'route' => '/company/iso/quality/revue-direction', 'icon' => 'mdi-account-tie', 'order' => 8],
            ['identifier' => 'amelioration_continue', 'name' => 'Amélioration Continue', 'category' => 'qualite', 'route' => '/company/iso/quality/amelioration', 'icon' => 'mdi-trending-up', 'order' => 9],
            
            // Ressources (4 modules)
            ['identifier' => 'ressources', 'name' => 'Ressources', 'category' => 'support', 'route' => '/company/iso/support/ressources', 'icon' => 'mdi-package-variant', 'order' => 10],
            ['identifier' => 'competences', 'name' => 'Compétences', 'category' => 'support', 'route' => '/company/iso/support/training', 'icon' => 'mdi-school-outline', 'order' => 11],
            ['identifier' => 'sensibilisation', 'name' => 'Sensibilisation', 'category' => 'support', 'route' => '/company/iso/support/sensibilisation', 'icon' => 'mdi-lightbulb-on-outline', 'order' => 12],
            ['identifier' => 'communication', 'name' => 'Communication', 'category' => 'support', 'route' => '/company/iso/support/communication', 'icon' => 'mdi-message-text-outline', 'order' => 13],
            
            // Non-Conformités (1 module)
            ['identifier' => 'non_conformites_actions', 'name' => 'Non-Conformités et Actions Correctives', 'category' => 'qualite', 'route' => '/company/iso/quality/non-conformites', 'icon' => 'mdi-alert-octagon-outline', 'order' => 14],
            
            // SST (1 module - ISO 45001)
            ['identifier' => 'consultation_travailleurs', 'name' => 'Consultation et Participation des Travailleurs', 'category' => 'sst', 'route' => '/company/iso/sst/consultation', 'icon' => 'mdi-account-group-outline', 'order' => 15],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->insert(array_merge($module, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
