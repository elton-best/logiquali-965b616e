<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerformanceEvaluationModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Module principal : Évaluation des performances
        $moduleId = DB::table('modules')->insertGetId([
            'code' => 'evaluation_performances',
            'name' => 'Évaluation des performances',
            'description' => 'Surveillance, mesure, analyse et évaluation - Point 9 ISO 9001',
            'icon' => 'mdi-chart-line',
            'iso_point' => 9,
            'order' => 90,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sous-module 1 : Surveillance et Mesure
        $subModule1Id = DB::table('sub_modules')->insertGetId([
            'module_id' => $moduleId,
            'code' => 'surveillance_mesure',
            'name' => 'Evaluations PIP',
            'description' => 'Surveillance, mesure, analyse et évaluation',
            'icon' => 'mdi-chart-line',
            'order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sections du sous-module Surveillance
        DB::table('sub_module_sections')->insert([
            [
                'sub_module_id' => $subModule1Id,
                'code' => 'satisfaction_personnel',
                'name' => 'Évaluation Satisfaction Personnel',
                'description' => 'M13-D3 - Fiche Satisfaction Personnel',
                'icon' => 'mdi-account-group',
                'route' => '/company/performance/surveillance/personnel',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sub_module_id' => $subModule1Id,
                'code' => 'satisfaction_client',
                'name' => 'Évaluation Satisfaction Client',
                'description' => 'M13-D4 - Fiche Satisfaction Client',
                'icon' => 'mdi-account-heart',
                'route' => '/company/performance/surveillance/client',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sub_module_id' => $subModule1Id,
                'code' => 'rapport_satisfaction',
                'name' => 'Rapport des évaluations PIP',
                'description' => 'M13-D6 - Rapport de Satisfaction des PIP',
                'icon' => 'mdi-file-document',
                'route' => '/company/performance/surveillance/rapport',
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sub_module_id' => $subModule1Id,
                'code' => 'revue_processus',
                'name' => 'Revue Processus',
                'description' => 'Revue sectionnelle par processus',
                'icon' => 'mdi-clipboard-text-search-outline',
                'route' => '/company/performance/surveillance/process-review',
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Sous-module 2 : Audit Interne
        $subModule2Id = DB::table('sub_modules')->insertGetId([
            'module_id' => $moduleId,
            'code' => 'audit_interne',
            'name' => 'Audit Interne',
            'description' => 'Gestion des audits internes',
            'icon' => 'mdi-clipboard-check',
            'order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sections du sous-module Audit
        DB::table('sub_module_sections')->insert([
            [
                'sub_module_id' => $subModule2Id,
                'code' => 'planification_audits',
                'name' => 'Planification des Audits',
                'description' => 'M9-D1 - Procédure de gestion des audits',
                'icon' => 'mdi-calendar-clock',
                'route' => '/company/performance/audits/planification',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sub_module_id' => $subModule2Id,
                'code' => 'evaluation_auditeurs',
                'name' => 'Évaluation des Auditeurs',
                'description' => 'M9-D5 - Fiche d\'évaluation des auditeurs',
                'icon' => 'mdi-account-check',
                'route' => '/company/performance/audits/evaluation',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Sous-module 3 : Revue de Direction
        $subModule3Id = DB::table('sub_modules')->insertGetId([
            'module_id' => $moduleId,
            'code' => 'revue_direction_perf',
            'name' => 'Revue de Direction',
            'description' => 'Rapports et invitations revue de direction',
            'icon' => 'mdi-account-tie',
            'order' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sections du sous-module Revue de Direction
        DB::table('sub_module_sections')->insert([
            [
                'sub_module_id' => $subModule3Id,
                'code' => 'rapports_revue',
                'name' => 'Rapports',
                'description' => 'M12-D2 - Rapport de Revue de Direction',
                'icon' => 'mdi-file-document-outline',
                'route' => '/company/performance/revue-direction/rapports',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sub_module_id' => $subModule3Id,
                'code' => 'invitations_revue',
                'name' => 'Invitations',
                'description' => 'M12-D3 - Invitation à la revue de direction',
                'icon' => 'mdi-email-outline',
                'route' => '/company/performance/revue-direction/invitations',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info(' Module Évaluation des performances créé avec succès');
    }
}
