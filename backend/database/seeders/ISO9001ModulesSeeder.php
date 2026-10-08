<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ISO9001ModulesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('ISO9001ModulesSeeder est obsolète. Exécution du seeder unifié AllISOStandardsSeeder.');
        $this->call(AllISOStandardsSeeder::class);
        return;

        $iso9001 = DB::table('norms')->where('code', 'ISO 9001:2015')->first();
        
        if (!$iso9001) {
            $this->command->error('❌ Norme ISO 9001:2015 introuvable');
            return;
        }

        // Supprimer les offres existantes
        DB::table('offers')->whereIn('id', [2, 3, 4])->delete();
        $this->command->info('🗑️  Offres existantes supprimées');

        // MODULE 1: Contexte de l'organisme (Point 4)
        $module1 = DB::table('modules')->insertGetId([
            'code' => 'contexte',
            'name' => 'Contexte de l\'organisme',
            'description' => 'Point 4 - Compréhension de l\'organisation et de son contexte',
            'icon' => 'mdi-domain',
            'iso_point' => 4,
            'order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subModules1 = [
            ['code' => 'comprehension_organisme', 'name' => 'Compréhension de l\'organisme', 'route' => '/company/iso/context/swot-pestel', 'icon' => 'mdi-matrix'],
            ['code' => 'parties_interessees', 'name' => 'Parties intéressées', 'route' => '/company/iso/context/stakeholders', 'icon' => 'mdi-account-group'],
            ['code' => 'domaine_application', 'name' => 'Domaine d\'application', 'route' => '/company/iso/context/application-scope', 'icon' => 'mdi-file-document-outline'],
            ['code' => 'systeme_management', 'name' => 'Système de management', 'route' => '/company/iso/context/management-system', 'icon' => 'mdi-file-document-outline'],
        ];

        foreach ($subModules1 as $index => $sm) {
            DB::table('sub_modules')->insert([
                'module_id' => $module1,
                'code' => $sm['code'],
                'name' => $sm['name'],
                'route' => $sm['route'],
                'icon' => $sm['icon'],
                'order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('norm_module')->insert(['norm_id' => $iso9001->id, 'module_id' => $module1, 'created_at' => now(), 'updated_at' => now()]);

        // MODULE 2: Leadership (Point 5)
        $module2 = DB::table('modules')->insertGetId([
            'code' => 'leadership',
            'name' => 'Leadership',
            'description' => 'Point 5 - Engagement de la direction',
            'icon' => 'mdi-tie',
            'iso_point' => 5,
            'order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subModule2_1 = DB::table('sub_modules')->insertGetId([
            'module_id' => $module2,
            'code' => 'politique',
            'name' => 'Politique QHSE',
            'route' => '/company/leadership/policy',
            'icon' => 'mdi-book-open-variant',
            'order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subModule2_2 = DB::table('sub_modules')->insertGetId([
            'module_id' => $module2,
            'code' => 'roles_responsabilites',
            'name' => 'Rôles et Responsabilités',
            'route' => '/company/leadership/roles',
            'icon' => 'mdi-account-star',
            'order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sections2_2 = [
            ['code' => 'organigramme', 'name' => 'Organigramme', 'route' => '/company/leadership/organization-chart', 'icon' => 'mdi-sitemap'],
            ['code' => 'liste_personnel', 'name' => 'Liste du personnel', 'route' => '/company/leadership/personnel', 'icon' => 'mdi-account-multiple'],
            ['code' => 'fiche_poste', 'name' => 'Fiche de poste', 'route' => '/company/leadership/fiche_poste', 'icon' => 'mdi-badge-account'],
            ['code' => 'fiche_responsabilite', 'name' => 'Fiche de responsabilité', 'route' => '/company/leadership/fiche_responsabilite', 'icon' => 'mdi-clipboard-account'],
        ];

        foreach ($sections2_2 as $index => $section) {
            DB::table('sub_module_sections')->insert([
                'sub_module_id' => $subModule2_2,
                'code' => $section['code'],
                'name' => $section['name'],
                'route' => $section['route'],
                'icon' => $section['icon'],
                'order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('norm_module')->insert(['norm_id' => $iso9001->id, 'module_id' => $module2, 'created_at' => now(), 'updated_at' => now()]);

        // MODULE 3: Planification (Point 6)
        $module3 = DB::table('modules')->insertGetId([
            'code' => 'planification',
            'name' => 'Planification',
            'description' => 'Point 6 - Planification du système de management',
            'icon' => 'mdi-calendar-check',
            'iso_point' => 6,
            'order' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subModules3 = [
            ['code' => 'risques_opportunites', 'name' => 'Risques et opportunités', 'route' => '/company/iso/planning/risks-opportunities', 'icon' => 'mdi-alert-octagon'],
            ['code' => 'objectifs_qualite', 'name' => 'Objectifs qualité', 'route' => '/company/iso/planning/objectives', 'icon' => 'mdi-target'],
            ['code' => 'plans_action', 'name' => 'Plans du SM', 'route' => '/company/iso/planning/action-plans', 'icon' => 'mdi-playlist-check'],
        ];

        foreach ($subModules3 as $index => $sm) {
            DB::table('sub_modules')->insert([
                'module_id' => $module3,
                'code' => $sm['code'],
                'name' => $sm['name'],
                'route' => $sm['route'],
                'icon' => $sm['icon'],
                'order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('norm_module')->insert(['norm_id' => $iso9001->id, 'module_id' => $module3, 'created_at' => now(), 'updated_at' => now()]);

        // MODULE 4: Support (Point 7)
        $module4 = DB::table('modules')->insertGetId([
            'code' => 'support',
            'name' => 'Support',
            'description' => 'Point 7 - Ressources et support',
            'icon' => 'mdi-lifebuoy',
            'iso_point' => 7,
            'order' => 4,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subModules4 = [
            ['code' => 'ressources', 'name' => 'Ressources', 'route' => '/company/iso/support/resources', 'icon' => 'mdi-package-variant'],
            ['code' => 'competences', 'name' => 'Compétences', 'route' => '/company/iso/support/training', 'icon' => 'mdi-school'],
            ['code' => 'sensibilisation', 'name' => 'Sensibilisation', 'route' => '/company/iso/support/awareness', 'icon' => 'mdi-bullhorn'],
            ['code' => 'communication', 'name' => 'Communication', 'route' => '/company/iso/support/communication', 'icon' => 'mdi-message-text'],
            ['code' => 'documents', 'name' => 'Documents QHSE', 'route' => '/company/iso/support/documents', 'icon' => 'mdi-folder-multiple'],
        ];

        foreach ($subModules4 as $index => $sm) {
            DB::table('sub_modules')->insert([
                'module_id' => $module4,
                'code' => $sm['code'],
                'name' => $sm['name'],
                'route' => $sm['route'],
                'icon' => $sm['icon'],
                'order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('norm_module')->insert(['norm_id' => $iso9001->id, 'module_id' => $module4, 'created_at' => now(), 'updated_at' => now()]);

        // MODULE 5: Réalisation (Point 8)
        $module5 = DB::table('modules')->insertGetId([
            'code' => 'realisation',
            'name' => 'Réalisation',
            'description' => 'Point 8 - Réalisation des activités opérationnelles',
            'icon' => 'mdi-cog',
            'iso_point' => 8,
            'order' => 5,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subModules5 = [
            ['code' => 'processus', 'name' => 'Processus', 'route' => '/company/processes', 'icon' => 'mdi-chart-timeline-variant'],
            ['code' => 'procedures', 'name' => 'Procédures', 'route' => '/company/iso/operations/procedures', 'icon' => 'mdi-file-document-multiple'],
        ];

        foreach ($subModules5 as $index => $sm) {
            DB::table('sub_modules')->insert([
                'module_id' => $module5,
                'code' => $sm['code'],
                'name' => $sm['name'],
                'route' => $sm['route'],
                'icon' => $sm['icon'],
                'order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('norm_module')->insert(['norm_id' => $iso9001->id, 'module_id' => $module5, 'created_at' => now(), 'updated_at' => now()]);

        // MODULE 6: Évaluation (Point 9)
        $module6 = DB::table('modules')->insertGetId([
            'code' => 'evaluation',
            'name' => 'Évaluation des performances',
            'description' => 'Point 9 - Surveillance, mesure, analyse et évaluation',
            'icon' => 'mdi-chart-line',
            'iso_point' => 9,
            'order' => 6,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subModules6 = [
            ['code' => 'indicateurs', 'name' => 'Indicateurs', 'route' => '/company/iso/performance/indicators', 'icon' => 'mdi-chart-bar'],
            ['code' => 'audits_internes', 'name' => 'Audits internes', 'route' => '/company/audits', 'icon' => 'mdi-clipboard-check'],
            ['code' => 'revue_direction', 'name' => 'Revue de direction', 'route' => '/company/iso/performance/management-review', 'icon' => 'mdi-account-tie'],
            ['code' => 'satisfaction_client', 'name' => 'Satisfaction client', 'route' => '/company/support/satisfaction', 'icon' => 'mdi-emoticon-happy'],
        ];

        foreach ($subModules6 as $index => $sm) {
            DB::table('sub_modules')->insert([
                'module_id' => $module6,
                'code' => $sm['code'],
                'name' => $sm['name'],
                'route' => $sm['route'],
                'icon' => $sm['icon'],
                'order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('norm_module')->insert(['norm_id' => $iso9001->id, 'module_id' => $module6, 'created_at' => now(), 'updated_at' => now()]);

        // MODULE 7: Amélioration (Point 10)
        $module7 = DB::table('modules')->insertGetId([
            'code' => 'amelioration',
            'name' => 'Amélioration',
            'description' => 'Point 10 - Amélioration continue',
            'icon' => 'mdi-trending-up',
            'iso_point' => 10,
            'order' => 7,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subModules7 = [
            [
                'code' => 'non_conformites_actions',
                'name' => 'Non-conformités et actions correctives',
                'route' => '/company/nonconformities',
                'icon' => 'mdi-alert-octagon-outline',
            ],
            [
                'code' => 'amelioration_continue',
                'name' => 'Amélioration continue',
                'route' => '/company/iso/improvement/continuous',
                'icon' => 'mdi-refresh',
            ],
        ];

        foreach ($subModules7 as $index => $sm) {
            DB::table('sub_modules')->insert([
                'module_id' => $module7,
                'code' => $sm['code'],
                'name' => $sm['name'],
                'route' => $sm['route'],
                'icon' => $sm['icon'],
                'order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('norm_module')->insert(['norm_id' => $iso9001->id, 'module_id' => $module7, 'created_at' => now(), 'updated_at' => now()]);

        // Créer offres ISO 9001
        $offerBasic = DB::table('offers')->insertGetId([
            'ref' => 'OFF-ISO9001-BASIC',
            'name' => 'ISO 9001 - Offre Essentielle',
            'description' => 'Modules de base pour démarrer avec ISO 9001',
            'price' => 150000,
            'duration_months' => 12,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $offerComplete = DB::table('offers')->insertGetId([
            'ref' => 'OFF-ISO9001-COMPLETE',
            'name' => 'ISO 9001 - Offre Complète',
            'description' => 'Tous les modules ISO 9001 (Points 4 à 10)',
            'price' => 500000,
            'duration_months' => 12,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lier offres à la norme
        DB::table('norm_offer')->insert([
            ['norm_id' => $iso9001->id, 'offer_id' => $offerBasic, 'created_at' => now(), 'updated_at' => now()],
            ['norm_id' => $iso9001->id, 'offer_id' => $offerComplete, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('✅ Modules ISO 9001 créés avec succès');
        $this->command->info('   📦 7 modules');
        $this->command->info('   📂 25 sous-modules');
        $this->command->info('   📄 4 sections (Rôles et Responsabilités)');
        $this->command->info('   🎁 2 offres créées');
    }
}
