<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AllISOStandardsSeeder extends Seeder
{
    public function run(): void
    {
        // Supprimer les anciennes offres ISO 9001
        DB::table('offers')->whereIn('ref', ['OFF-ISO9001-BASIC', 'OFF-ISO9001-COMPLETE'])->delete();
        
        $this->command->info('🚀 Création des sous-modules spécifiques par norme...');

        // Récupérer les normes
        $iso9001 = DB::table('norms')->where('code', 'ISO 9001:2015')->first();
        $iso14001 = DB::table('norms')->where('code', 'ISO 14001:2015')->first();
        $iso45001 = DB::table('norms')->where('code', 'ISO 45001:2018')->first();
        $iso27001 = DB::table('norms')->where('code', 'ISO 27001:2022')->first();
        $iso22000 = DB::table('norms')->where('code', 'ISO 22000:2018')->first();
        $iso50001 = DB::table('norms')->where('code', 'ISO 50001:2018')->first();

        // Récupérer les modules
        $modules = DB::table('modules')->get()->keyBy('code');
        $evaluationModule = $modules->get('evaluation');
        if ($evaluationModule) {
            DB::table('sub_modules')
                ->where('code', 'satisfaction_client')
                ->update([
                    'name' => 'Evaluations PIP',
                    'route' => '/company/performance/surveillance',
                    'icon' => 'mdi-account-star-outline',
                    'updated_at' => now(),
                ]);

            DB::table('sub_modules')->updateOrInsert(
                ['code' => 'revue_processus'],
                [
                    'module_id' => $evaluationModule->id,
                    'name' => 'Revue Processus',
                    'route' => '/company/performance/surveillance/process-review',
                    'icon' => 'mdi-clipboard-text-search-outline',
                    'order' => 2,
                    'is_active' => true,
                    'is_common' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // ============================================
        // ISO 14001 - SOUS-MODULES SPÉCIFIQUES
        // ============================================
        if ($iso14001) {
            $this->createISO14001SubModules($iso14001, $modules);
            $this->createISO14001Offer($iso14001);
        }

        // ============================================
        // ISO 45001 - SOUS-MODULES SPÉCIFIQUES
        // ============================================
        if ($iso45001) {
            $this->createISO45001SubModules($iso45001, $modules);
            $this->createISO45001Offer($iso45001);
        }

        // ============================================
        // ISO 27001 - SOUS-MODULES SPÉCIFIQUES
        // ============================================
        if ($iso27001) {
            $this->createISO27001SubModules($iso27001, $modules);
            $this->createISO27001Offer($iso27001);
        }

        // ============================================
        // ISO 22000 - SOUS-MODULES SPÉCIFIQUES
        // ============================================
        if ($iso22000) {
            $this->createISO22000SubModules($iso22000, $modules);
            $this->createISO22000Offer($iso22000);
        }

        // ============================================
        // ISO 50001 - SOUS-MODULES SPÉCIFIQUES
        // ============================================
        if ($iso50001) {
            $this->createISO50001SubModules($iso50001, $modules);
            $this->createISO50001Offer($iso50001);
        }

        // ============================================
        // ISO 9001 - OFFRE COMPLÈTE
        // ============================================
        if ($iso9001) {
            $this->createISO9001Offer($iso9001);
        }

        $revueProcessusId = DB::table('sub_modules')->where('code', 'revue_processus')->value('id');
        if ($revueProcessusId) {
            foreach ([$iso9001, $iso14001, $iso45001, $iso27001, $iso22000, $iso50001] as $norm) {
                if (!$norm) {
                    continue;
                }

                DB::table('norm_sub_module')->updateOrInsert(
                    ['norm_id' => (int) $norm->id, 'sub_module_id' => (int) $revueProcessusId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $this->command->info('✅ Toutes les normes ISO configurées avec succès!');
        $this->command->info('   📦 6 normes ISO');
        $this->command->info('   🎁 6 offres complètes créées');
    }

    private function createISO14001SubModules($norm, $modules): void
    {
        $planification = $modules->get('planification');
        $realisation = $modules->get('realisation');

        $subModules = [
            // Module Planification
            ['module_id' => $planification->id, 'code' => 'aspects_environnementaux', 'name' => 'Aspects environnementaux significatifs', 'route' => '/company/iso/planning/aspects-environmentaux', 'icon' => 'mdi-leaf', 'order' => 10],
            ['module_id' => $planification->id, 'code' => 'revue_environnementale', 'name' => 'Revue environnementale', 'route' => '/company/iso/planning/environmental-review', 'icon' => 'mdi-earth', 'order' => 12],
            
            // Module Réalisation
            ['module_id' => $realisation->id, 'code' => 'maitrise_operationnelle_env', 'name' => 'Maîtrise opérationnelle environnementale', 'route' => '/company/iso/operations/environmental-control', 'icon' => 'mdi-shield-check', 'order' => 10],
            ['module_id' => $realisation->id, 'code' => 'urgences_environnementales', 'name' => 'Préparation et réponse aux urgences', 'route' => '/company/iso/operations/emergency-preparedness', 'icon' => 'mdi-alert', 'order' => 11],
        ];

        foreach ($subModules as $sm) {
            $subModuleId = $this->upsertSubModule($sm);
            $this->linkNormSubModule((int) $norm->id, $subModuleId);
        }

        $this->command->info('   ✓ ISO 14001: 5 sous-modules créés');
    }

    private function createISO45001SubModules($norm, $modules): void
    {
        $leadership = $modules->get('leadership');
        $planification = $modules->get('planification');
        $realisation = $modules->get('realisation');
        $evaluation = $modules->get('evaluation');

        // Sous-module Leadership: Consultation et participation
        $consultationId = $this->upsertSubModule([
            'module_id' => $leadership->id,
            'code' => 'consultation_participation',
            'name' => 'Consultation et participation des travailleurs',
            'route' => '/company/leadership/consultation',
            'icon' => 'mdi-account-voice',
            'order' => 3,
        ]);

        $this->linkNormSubModule((int) $norm->id, $consultationId);

        $subModules = [
            // Module Planification
            ['module_id' => $planification->id, 'code' => 'duerp', 'name' => 'DUERP', 'route' => '/company/iso/planning/duerp', 'icon' => 'mdi-shield-alert', 'order' => 10],
            ['module_id' => $planification->id, 'code' => 'identification_dangers_sst', 'name' => 'Identification des dangers SST', 'route' => '/company/iso/planning/hazard-identification', 'icon' => 'mdi-hazard-lights', 'order' => 11],
            ['module_id' => $planification->id, 'code' => 'exigences_legales_sst', 'name' => 'Exigences légales SST', 'route' => '/company/iso/planning/legal-requirements', 'icon' => 'mdi-scale-balance', 'order' => 12],
            
            // Module Réalisation
            ['module_id' => $realisation->id, 'code' => 'elimination_risques_sst', 'name' => 'Élimination des dangers et réduction des risques', 'route' => '/company/iso/operations/risk-elimination', 'icon' => 'mdi-shield-remove', 'order' => 10],
            ['module_id' => $realisation->id, 'code' => 'gestion_changement_sst', 'name' => 'Gestion du changement SST', 'route' => '/company/iso/operations/change-management', 'icon' => 'mdi-swap-horizontal', 'order' => 11],
            ['module_id' => $realisation->id, 'code' => 'achats_sous_traitance_sst', 'name' => 'Achats et sous-traitance SST', 'route' => '/company/iso/operations/procurement', 'icon' => 'mdi-cart', 'order' => 12],
            ['module_id' => $realisation->id, 'code' => 'preparation_reponse_urgences_sst', 'name' => 'Préparation et réponse aux situations d\'urgence', 'route' => '/company/iso/operations/emergency-preparedness', 'icon' => 'mdi-alert', 'order' => 13],
            
            // Module Évaluation
            ['module_id' => $evaluation->id, 'code' => 'surveillance_accidents', 'name' => 'Surveillance des accidents du travail', 'route' => '/company/iso/performance/accident-monitoring', 'icon' => 'mdi-ambulance', 'order' => 10],
            ['module_id' => $evaluation->id, 'code' => 'enquetes_incidents', 'name' => 'Enquêtes sur les incidents', 'route' => '/company/iso/performance/incident-investigation', 'icon' => 'mdi-magnify', 'order' => 11],
        ];

        foreach ($subModules as $sm) {
            $subModuleId = $this->upsertSubModule($sm);
            $this->linkNormSubModule((int) $norm->id, $subModuleId);
        }

        $this->command->info('   ✓ ISO 45001: 10 sous-modules créés');
    }

    private function createISO27001SubModules($norm, $modules): void
    {
        $planification = $modules->get('planification');
        $realisation = $modules->get('realisation');
        $evaluation = $modules->get('evaluation');

        $subModules = [
            // Module Planification
            ['module_id' => $planification->id, 'code' => 'evaluation_risques_si', 'name' => 'Évaluation des risques de sécurité de l\'information', 'route' => '/company/iso/planning/information-security-risks', 'icon' => 'mdi-security', 'order' => 10],
            ['module_id' => $planification->id, 'code' => 'traitement_risques_si', 'name' => 'Traitement des risques SI', 'route' => '/company/iso/planning/risk-treatment', 'icon' => 'mdi-shield-lock', 'order' => 11],
            ['module_id' => $planification->id, 'code' => 'declaration_applicabilite', 'name' => 'Déclaration d\'applicabilité (SoA)', 'route' => '/company/iso/planning/statement-applicability', 'icon' => 'mdi-file-document-check', 'order' => 12],
            
            // Module Réalisation
            ['module_id' => $realisation->id, 'code' => 'inventaire_actifs', 'name' => 'Inventaire des actifs informationnels', 'route' => '/company/iso/operations/asset-inventory', 'icon' => 'mdi-database', 'order' => 10],
            ['module_id' => $realisation->id, 'code' => 'controles_acces', 'name' => 'Contrôles d\'accès', 'route' => '/company/iso/operations/access-controls', 'icon' => 'mdi-key', 'order' => 11],
            ['module_id' => $realisation->id, 'code' => 'cryptographie', 'name' => 'Cryptographie', 'route' => '/company/iso/operations/cryptography', 'icon' => 'mdi-lock', 'order' => 12],
            ['module_id' => $realisation->id, 'code' => 'securite_physique', 'name' => 'Sécurité physique', 'route' => '/company/iso/operations/physical-security', 'icon' => 'mdi-office-building-cog', 'order' => 13],
            ['module_id' => $realisation->id, 'code' => 'pca_pra', 'name' => 'Plan de continuité d\'activité (PCA/PRA)', 'route' => '/company/iso/operations/business-continuity', 'icon' => 'mdi-backup-restore', 'order' => 14],
            
            // Module Évaluation
            ['module_id' => $evaluation->id, 'code' => 'gestion_incidents_si', 'name' => 'Gestion des incidents de sécurité', 'route' => '/company/iso/performance/security-incidents', 'icon' => 'mdi-alert-octagon', 'order' => 10],
            ['module_id' => $evaluation->id, 'code' => 'conformite_rgpd', 'name' => 'Conformité RGPD', 'route' => '/company/iso/performance/gdpr-compliance', 'icon' => 'mdi-shield-account', 'order' => 11],
        ];

        foreach ($subModules as $sm) {
            $subModuleId = $this->upsertSubModule($sm);
            $this->linkNormSubModule((int) $norm->id, $subModuleId);
        }

        $this->command->info('   ✓ ISO 27001: 10 sous-modules créés');
    }

    private function createISO22000SubModules($norm, $modules): void
    {
        $planification = $modules->get('planification');
        $realisation = $modules->get('realisation');
        $evaluation = $modules->get('evaluation');

        $subModules = [
            // Module Planification
            ['module_id' => $planification->id, 'code' => 'analyse_dangers_haccp', 'name' => 'Analyse des dangers (HACCP)', 'route' => '/company/iso/planning/haccp-analysis', 'icon' => 'mdi-biohazard', 'order' => 10],
            ['module_id' => $planification->id, 'code' => 'points_critiques_ccp', 'name' => 'Points critiques de contrôle (CCP)', 'route' => '/company/iso/planning/critical-control-points', 'icon' => 'mdi-target-variant', 'order' => 11],
            ['module_id' => $planification->id, 'code' => 'programmes_prerequis', 'name' => 'Programmes prérequis (PRP)', 'route' => '/company/iso/planning/prerequisite-programs', 'icon' => 'mdi-checkbox-marked-circle', 'order' => 12],
            ['module_id' => $planification->id, 'code' => 'plan_haccp', 'name' => 'Plan HACCP', 'route' => '/company/iso/planning/haccp-plan', 'icon' => 'mdi-file-chart', 'order' => 13],
            
            // Module Réalisation
            ['module_id' => $realisation->id, 'code' => 'maitrise_dangers_alimentaires', 'name' => 'Maîtrise des dangers alimentaires', 'route' => '/company/iso/operations/food-hazard-control', 'icon' => 'mdi-food-apple', 'order' => 10],
            ['module_id' => $realisation->id, 'code' => 'tracabilite_alimentaire', 'name' => 'Traçabilité alimentaire', 'route' => '/company/iso/operations/food-traceability', 'icon' => 'mdi-barcode-scan', 'order' => 11],
            ['module_id' => $realisation->id, 'code' => 'gestion_allergenes', 'name' => 'Gestion des allergènes', 'route' => '/company/iso/operations/allergen-management', 'icon' => 'mdi-alert-circle', 'order' => 12],
            ['module_id' => $realisation->id, 'code' => 'controle_fournisseurs', 'name' => 'Contrôle des fournisseurs', 'route' => '/company/iso/operations/supplier-control', 'icon' => 'mdi-truck-delivery', 'order' => 13],
            
            // Module Évaluation
            ['module_id' => $evaluation->id, 'code' => 'surveillance_ccp', 'name' => 'Surveillance des CCP', 'route' => '/company/iso/performance/ccp-monitoring', 'icon' => 'mdi-monitor-eye', 'order' => 10],
            ['module_id' => $evaluation->id, 'code' => 'verification_haccp', 'name' => 'Vérification du système HACCP', 'route' => '/company/iso/performance/haccp-verification', 'icon' => 'mdi-check-decagram', 'order' => 11],
            ['module_id' => $evaluation->id, 'code' => 'analyses_microbiologiques', 'name' => 'Analyses microbiologiques', 'route' => '/company/iso/performance/microbiological-analysis', 'icon' => 'mdi-microscope', 'order' => 12],
        ];

        foreach ($subModules as $sm) {
            $subModuleId = $this->upsertSubModule($sm);
            $this->linkNormSubModule((int) $norm->id, $subModuleId);
        }

        $this->command->info('   ✓ ISO 22000: 11 sous-modules créés');
    }

    private function createISO50001SubModules($norm, $modules): void
    {
        $planification = $modules->get('planification');
        $realisation = $modules->get('realisation');
        $evaluation = $modules->get('evaluation');

        $subModules = [
            // Module Planification
            ['module_id' => $planification->id, 'code' => 'revue_energetique', 'name' => 'Revue énergétique', 'route' => '/company/iso/planning/revue', 'icon' => 'mdi-lightning-bolt', 'order' => 10],
            ['module_id' => $planification->id, 'code' => 'situation_energetique_reference', 'name' => 'Situation énergétique de référence', 'route' => '/company/iso/planning/energy', 'icon' => 'mdi-chart-line', 'order' => 11],
            ['module_id' => $planification->id, 'code' => 'collecte_donnees_energetiques', 'name' => 'Planification de collecte des données énergétiques', 'route' => '/company/iso/planning/analysts', 'icon' => 'mdi-database-search', 'order' => 12],
            ['module_id' => $planification->id, 'code' => 'ipe', 'name' => 'Indicateurs de performance énergétique (IPÉ)', 'route' => '/company/iso/planning/energy-indicators', 'icon' => 'mdi-gauge', 'order' => 13],
            ['module_id' => $planification->id, 'code' => 'objectifs_cibles_energetiques', 'name' => 'Objectifs et cibles énergétiques', 'route' => '/company/iso/planning/energy-objectives', 'icon' => 'mdi-bullseye-arrow', 'order' => 14],
            
            // Module Réalisation
            ['module_id' => $realisation->id, 'code' => 'conception_energetique', 'name' => 'Conception énergétique', 'route' => '/company/iso/operations/energy-design', 'icon' => 'mdi-drawing', 'order' => 10],
            ['module_id' => $realisation->id, 'code' => 'achats_energie', 'name' => 'Achats d\'énergie', 'route' => '/company/iso/operations/energy-procurement', 'icon' => 'mdi-shopping', 'order' => 11],
            ['module_id' => $realisation->id, 'code' => 'achats_equipements_energetiques', 'name' => 'Achats d\'équipements énergétiques', 'route' => '/company/iso/operations/equipment-procurement', 'icon' => 'mdi-tools', 'order' => 12],
            
            // Module Évaluation
            ['module_id' => $evaluation->id, 'code' => 'surveillance_performance_energetique', 'name' => 'Surveillance de la performance énergétique', 'route' => '/company/iso/performance/energy-monitoring', 'icon' => 'mdi-chart-areaspline', 'order' => 10],
            ['module_id' => $evaluation->id, 'code' => 'conformite_energetique', 'name' => 'Évaluation de la conformité énergétique', 'route' => '/company/iso/performance/energy-compliance', 'icon' => 'mdi-check-circle', 'order' => 11],
            ['module_id' => $evaluation->id, 'code' => 'audit_energetique', 'name' => 'Audit énergétique interne', 'route' => '/company/iso/performance/energy-audit', 'icon' => 'mdi-clipboard-check', 'order' => 12],
        ];

        foreach ($subModules as $sm) {
            $subModuleId = $this->upsertSubModule($sm);
            $this->linkNormSubModule((int) $norm->id, $subModuleId);
        }

        $this->command->info('   ✓ ISO 50001: 11 sous-modules créés');
    }

    private function createISO9001Offer($norm): void
    {
        $offerId = $this->upsertOffer([
            'ref' => 'OFF-ISO9001-2026',
            'name' => 'ISO 9001:2015 - Management de la Qualité',
            'description' => 'Offre complète ISO 9001 - Tous les modules (Points 4 à 10)',
            'price' => 500000,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $this->linkNormOffer((int) $norm->id, $offerId);
    }

    private function createISO14001Offer($norm): void
    {
        $offerId = $this->upsertOffer([
            'ref' => 'OFF-ISO14001-2026',
            'name' => 'ISO 14001:2015 - Management Environnemental',
            'description' => 'Offre complète ISO 14001 - Tous les modules environnementaux',
            'price' => 450000,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $this->linkNormOffer((int) $norm->id, $offerId);
    }

    private function createISO45001Offer($norm): void
    {
        $offerId = $this->upsertOffer([
            'ref' => 'OFF-ISO45001-2026',
            'name' => 'ISO 45001:2018 - Santé et Sécurité au Travail',
            'description' => 'Offre complète ISO 45001 - Tous les modules SST',
            'price' => 450000,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $this->linkNormOffer((int) $norm->id, $offerId);
    }

    private function createISO27001Offer($norm): void
    {
        $offerId = $this->upsertOffer([
            'ref' => 'OFF-ISO27001-2026',
            'name' => 'ISO 27001:2022 - Sécurité de l\'Information',
            'description' => 'Offre complète ISO 27001 - Tous les modules sécurité SI',
            'price' => 600000,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $this->linkNormOffer((int) $norm->id, $offerId);
    }

    private function createISO22000Offer($norm): void
    {
        $offerId = $this->upsertOffer([
            'ref' => 'OFF-ISO22000-2026',
            'name' => 'ISO 22000:2018 - Sécurité des Denrées Alimentaires',
            'description' => 'Offre complète ISO 22000 - Tous les modules HACCP',
            'price' => 550000,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $this->linkNormOffer((int) $norm->id, $offerId);
    }

    private function createISO50001Offer($norm): void
    {
        $offerId = $this->upsertOffer([
            'ref' => 'OFF-ISO50001-2026',
            'name' => 'ISO 50001:2018 - Management de l\'Énergie',
            'description' => 'Offre complète ISO 50001 - Tous les modules énergétiques',
            'price' => 400000,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $this->linkNormOffer((int) $norm->id, $offerId);
    }

    private function upsertSubModule(array $subModule): int
    {
        DB::table('sub_modules')->updateOrInsert(
            ['code' => $subModule['code']],
            [
                'module_id' => $subModule['module_id'],
                'name' => $subModule['name'],
                'route' => $subModule['route'],
                'icon' => $subModule['icon'],
                'order' => $subModule['order'],
                'is_active' => true,
                'is_common' => false,
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('sub_modules')->where('code', $subModule['code'])->value('id');
    }

    private function linkNormSubModule(int $normId, int $subModuleId): void
    {
        DB::table('norm_sub_module')->updateOrInsert(
            ['norm_id' => $normId, 'sub_module_id' => $subModuleId],
            ['updated_at' => now()],
        );
    }

    private function upsertOffer(array $offer): int
    {
        DB::table('offers')->updateOrInsert(
            ['ref' => $offer['ref']],
            [
                'name' => $offer['name'],
                'description' => $offer['description'],
                'price' => $offer['price'],
                'duration_months' => $offer['duration_months'],
                'is_active' => (bool) $offer['is_active'],
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('offers')->where('ref', $offer['ref'])->value('id');
    }

    private function linkNormOffer(int $normId, int $offerId): void
    {
        DB::table('norm_offer')->updateOrInsert(
            ['norm_id' => $normId, 'offer_id' => $offerId],
            ['updated_at' => now()],
        );
    }
}
