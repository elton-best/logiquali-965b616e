<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommonQHSECatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info(' Initialisation socle commun QHSE (modules/sous-modules/sections)...');

        $commonModuleCodes = [
            'contexte',
            'leadership',
            'planification',
            'support',
            'evaluation',
            'amelioration',
        ];

        DB::table('modules')
            ->whereIn('code', $commonModuleCodes)
            ->update([
                'is_common' => true,
                'updated_at' => now(),
            ]);

        DB::table('modules')
            ->where('code', 'realisation')
            ->update([
                'is_common' => false,
                'updated_at' => now(),
            ]);

        $modulesByCode = DB::table('modules')
            ->select('id', 'code')
            ->get()
            ->keyBy('code');

        $subModules = [
            // Contexte
            ['module_code' => 'contexte', 'code' => 'comprehension_organisme', 'name' => 'Compréhension de l\'organisme', 'route' => '/company/iso/context/swot-pestel', 'icon' => 'mdi-matrix', 'order' => 1],
            ['module_code' => 'contexte', 'code' => 'parties_interessees', 'name' => 'Parties intéressées', 'route' => '/company/iso/context/stakeholders', 'icon' => 'mdi-account-group', 'order' => 2],
            ['module_code' => 'contexte', 'code' => 'domaine_application', 'name' => 'Domaine d\'application', 'route' => '/company/iso/context/application-scope', 'icon' => 'mdi-file-document-outline', 'order' => 3],
            ['module_code' => 'contexte', 'code' => 'systeme_management', 'name' => 'Système de management', 'route' => '/company/iso/context/management-system', 'icon' => 'mdi-file-document-outline', 'order' => 4],

            // Leadership
            ['module_code' => 'leadership', 'code' => 'politique', 'name' => 'Politique QHSE', 'route' => '/company/leadership/policy', 'icon' => 'mdi-book-open-variant', 'order' => 1],
            ['module_code' => 'leadership', 'code' => 'roles_responsabilites', 'name' => 'Rôles et responsabilités', 'route' => '/company/leadership/roles', 'icon' => 'mdi-account-star', 'order' => 2],

            // Planification
            ['module_code' => 'planification', 'code' => 'risques_opportunites', 'name' => 'Risques et opportunités', 'route' => '/company/iso/planning/risks-opportunities', 'icon' => 'mdi-alert-octagon', 'order' => 1],
            ['module_code' => 'planification', 'code' => 'objectifs_qualite', 'name' => 'Objectifs qualité', 'route' => '/company/iso/planning/objectives', 'icon' => 'mdi-target', 'order' => 2],
            ['module_code' => 'planification', 'code' => 'plans_action', 'name' => 'Plans du SM', 'route' => '/company/iso/planning/action-plans', 'icon' => 'mdi-playlist-check', 'order' => 3],

            // Support
            ['module_code' => 'support', 'code' => 'ressources', 'name' => 'Ressources', 'route' => '/company/iso/support/equipment', 'icon' => 'mdi-package-variant', 'order' => 1],
            ['module_code' => 'support', 'code' => 'competences', 'name' => 'Compétences', 'route' => '/company/iso/support/training', 'icon' => 'mdi-school', 'order' => 2],
            ['module_code' => 'support', 'code' => 'sensibilisation', 'name' => 'Sensibilisation', 'route' => '/company/iso/support/awareness', 'icon' => 'mdi-bullhorn', 'order' => 3],
            ['module_code' => 'support', 'code' => 'communication', 'name' => 'Communication', 'route' => '/company/iso/support/communication', 'icon' => 'mdi-message-text', 'order' => 4],
            ['module_code' => 'support', 'code' => 'documents', 'name' => 'Information documentée', 'route' => '/company/iso/support/document-inventory', 'icon' => 'mdi-folder-multiple', 'order' => 5],

            // Réalisation
            ['module_code' => 'realisation', 'code' => 'processus', 'name' => 'Processus', 'route' => '/company/processes', 'icon' => 'mdi-chart-timeline-variant', 'order' => 1],
            ['module_code' => 'realisation', 'code' => 'procedures', 'name' => 'Procédures', 'route' => '/company/processes', 'icon' => 'mdi-file-document-multiple', 'order' => 2],
            ['module_code' => 'realisation', 'code' => 'planification_maitrise_operationnelle', 'name' => 'Planification et maîtrise opérationnelle', 'route' => '/company/iso/operations/operational-planning-control', 'icon' => 'mdi-clipboard-check-outline', 'order' => 3],
            ['module_code' => 'realisation', 'code' => 'exigences_produits_services', 'name' => 'Exigences relatives aux produits et services', 'route' => '/company/iso/operations/product-service-requirements', 'icon' => 'mdi-format-list-checks', 'order' => 4],
            ['module_code' => 'realisation', 'code' => 'conception_developpement_produits_services', 'name' => 'Conception et développement de produit et service', 'route' => '/company/iso/operations/design-development-products-services', 'icon' => 'mdi-ruler-square-compass', 'order' => 5],
            ['module_code' => 'realisation', 'code' => 'gestion_prestataires', 'name' => 'Gestion des prestataires', 'route' => '/company/iso/operations/provider-management', 'icon' => 'mdi-account-tie-hat', 'order' => 6],
            ['module_code' => 'realisation', 'code' => 'production_prestation_service', 'name' => 'Production et prestation de service', 'route' => '/company/iso/operations/production-service-provision', 'icon' => 'mdi-factory', 'order' => 7],
            ['module_code' => 'realisation', 'code' => 'liberation_produits_services', 'name' => 'Libération des produits et services', 'route' => '/company/iso/operations/release-products-services', 'icon' => 'mdi-package-variant-closed-check', 'order' => 8],
            ['module_code' => 'realisation', 'code' => 'maitrise_sorties_non_conformes', 'name' => 'Maîtrise des éléments de sortie non-conformes', 'route' => '/company/iso/operations/control-nonconforming-outputs', 'icon' => 'mdi-alert-circle-outline', 'order' => 9],

            // Évaluation
            ['module_code' => 'evaluation', 'code' => 'satisfaction_client', 'name' => 'Évaluations PIP', 'route' => '/company/performance/surveillance', 'icon' => 'mdi-emoticon-happy', 'order' => 1],
            ['module_code' => 'evaluation', 'code' => 'revue_processus', 'name' => 'Revue Processus', 'route' => '/company/performance/surveillance/process-review', 'icon' => 'mdi-chart-timeline-variant', 'order' => 2],
            ['module_code' => 'evaluation', 'code' => 'audits_internes', 'name' => 'Audits internes', 'route' => '/company/performance/audits', 'icon' => 'mdi-clipboard-check', 'order' => 3],
            ['module_code' => 'evaluation', 'code' => 'revue_direction', 'name' => 'Revue de direction', 'route' => '/company/performance/revue-direction', 'icon' => 'mdi-account-tie', 'order' => 4],

            // Amélioration
            [
                'module_code' => 'amelioration',
                'code' => 'non_conformites_actions',
                'name' => 'Non-conformités et actions correctives',
                'route' => '/company/nonconformities',
                'icon' => 'mdi-alert-octagon-outline',
                'order' => 1,
            ],
            [
                'module_code' => 'amelioration',
                'code' => 'amelioration_continue',
                'name' => 'Amélioration continue',
                'route' => '/company/iso/improvement/continuous',
                'icon' => 'mdi-trending-up',
                'order' => 2,
            ],
        ];

        foreach ($subModules as $subModule) {
            $moduleId = $modulesByCode[$subModule['module_code']]->id ?? null;
            if (!$moduleId) {
                continue;
            }

            DB::table('sub_modules')->updateOrInsert(
                ['code' => $subModule['code']],
                [
                    'module_id' => $moduleId,
                    'name' => $subModule['name'],
                    'route' => $subModule['route'],
                    'icon' => $subModule['icon'],
                    'order' => $subModule['order'],
                    'is_active' => true,
                    'is_common' => $subModule['module_code'] !== 'realisation',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        // Nettoyage des anciennes entrées non alignées avec ISO 9001 §8
        $legacySubModuleIds = DB::table('sub_modules')
            ->whereIn('code', ['processus', 'procedures'])
            ->pluck('id')
            ->all();

        if (!empty($legacySubModuleIds)) {
            DB::table('norm_sub_module')
                ->whereIn('sub_module_id', $legacySubModuleIds)
                ->delete();

            DB::table('sub_modules')
                ->whereIn('id', $legacySubModuleIds)
                ->delete();
        }

        // Nettoyage des sous-modules Amélioration legacy (doublons)
        $legacyImprovementIds = DB::table('sub_modules')
            ->whereIn('code', ['non_conformites', 'actions_correctives'])
            ->pluck('id')
            ->all();

        if (!empty($legacyImprovementIds)) {
            DB::table('norm_sub_module')
                ->whereIn('sub_module_id', $legacyImprovementIds)
                ->delete();

            DB::table('sub_modules')
                ->whereIn('id', $legacyImprovementIds)
                ->delete();
        }

        $rolesSubModuleId = DB::table('sub_modules')->where('code', 'roles_responsabilites')->value('id');
        if ($rolesSubModuleId) {
            $sections = [
                ['code' => 'organigramme', 'name' => 'Organigramme', 'route' => '/company/leadership/organization-chart', 'icon' => 'mdi-sitemap', 'order' => 1],
                ['code' => 'liste_personnel', 'name' => 'Liste du personnel', 'route' => '/company/leadership/personnel', 'icon' => 'mdi-account-multiple', 'order' => 2],
                ['code' => 'fiche_poste', 'name' => 'Fiches de poste', 'route' => '/company/leadership/fiche_poste', 'icon' => 'mdi-badge-account', 'order' => 3],
                ['code' => 'fiche_responsabilite', 'name' => 'Fiches de responsabilité', 'route' => '/company/leadership/fiche_responsabilite', 'icon' => 'mdi-clipboard-account', 'order' => 4],
            ];

            foreach ($sections as $section) {
                DB::table('sub_module_sections')->updateOrInsert(
                    ['code' => $section['code']],
                    [
                        'sub_module_id' => $rolesSubModuleId,
                        'name' => $section['name'],
                        'route' => $section['route'],
                        'icon' => $section['icon'],
                        'order' => $section['order'],
                        'is_active' => true,
                        'is_common' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        $exigencesSubModuleId = DB::table('sub_modules')->where('code', 'exigences_produits_services')->value('id');
        if ($exigencesSubModuleId) {
            DB::table('sub_module_sections')->updateOrInsert(
                ['code' => 'obligations_conformite'],
                [
                    'sub_module_id' => $exigencesSubModuleId,
                    'name' => 'Obligations de conformité',
                    'description' => 'Obligations de conformité applicables aux exigences relatives aux produits et services.',
                    'icon' => 'mdi-gavel',
                    'route' => '/company/iso/operations/compliance-obligations',
                    'order' => 1,
                    'is_active' => true,
                    'is_common' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $normIds = DB::table('norms')
            ->whereIn('code', [
                'ISO 9001:2015',
                'ISO 14001:2015',
                'ISO 45001:2018',
                'ISO 27001:2022',
                'ISO 22000:2018',
                'ISO 50001:2018',
            ])
            ->pluck('id')
            ->all();

        $commonCodes = collect($subModules)
            ->where('module_code', '!=', 'realisation')
            ->pluck('code')
            ->all();
        $realisationCodes = collect($subModules)
            ->where('module_code', 'realisation')
            ->pluck('code')
            ->all();

        $commonSubModuleIds = DB::table('sub_modules')
            ->whereIn('code', $commonCodes)
            ->pluck('id')
            ->all();
        $realisationSubModuleIds = DB::table('sub_modules')
            ->whereIn('code', $realisationCodes)
            ->pluck('id')
            ->all();

        foreach ($normIds as $normId) {
            foreach ($commonSubModuleIds as $subModuleId) {
                DB::table('norm_sub_module')->updateOrInsert(
                    ['norm_id' => $normId, 'sub_module_id' => $subModuleId],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        $iso9001Id = DB::table('norms')->where('code', 'ISO 9001:2015')->value('id');
        if ($iso9001Id) {
            foreach ($realisationSubModuleIds as $subModuleId) {
                DB::table('norm_sub_module')->updateOrInsert(
                    ['norm_id' => $iso9001Id, 'sub_module_id' => $subModuleId],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        $this->command?->info(' Socle commun QHSE initialisé');
    }
}
