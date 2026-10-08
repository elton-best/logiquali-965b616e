<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NormativeCatalogMatrixSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('📚 Normalisation matrice catalogue normatif...');

        $commonModuleCodes = [
            'contexte',
            'leadership',
            'planification',
            'support',
            'evaluation',
            'amelioration',
        ];

        $commonSubModuleCodes = [
            // ISO 9001 socle + communs transverses (codes legacy + codes actuels)
            'comprehension_organisme',
            'profil_entreprise',
            'parties_interessees',
            'domaine_application',
            'systeme_management',
            'politique',
            'politique_qhse',
            'roles_responsabilites',
            'risques_opportunites',
            'objectifs_qualite',
            'plans_action',
            'ressources',
            'competences',
            'sensibilisation',
            'communication',
            'documents',
            'indicateurs_performance',
            'processus',
            'procedures',
            'indicateurs',
            'audits_internes',
            'revue_direction',
            'satisfaction_client',
            'revue_processus',
            'non_conformites_actions',
            'amelioration_continue',
        ];

        $commonSectionCodes = [
            'organigramme',
            'liste_personnel',
            'fiche_poste',
            'fiche_responsabilite',
        ];

        DB::table('modules')->update(['is_common' => false]);
        DB::table('sub_modules')->update(['is_common' => false]);
        DB::table('sub_module_sections')->update(['is_common' => false]);

        DB::table('modules')->whereIn('code', $commonModuleCodes)->update(['is_common' => true]);
        DB::table('sub_modules')->whereIn('code', $commonSubModuleCodes)->update(['is_common' => true]);
        DB::table('sub_module_sections')->whereIn('code', $commonSectionCodes)->update(['is_common' => true]);

        $this->command?->info('✅ Matrice catalogue normalisée');
        $this->command?->info('   - Modules communs: ' . count($commonModuleCodes));
        $this->command?->info('   - Sous-modules communs: ' . count($commonSubModuleCodes));
        $this->command?->info('   - Sections communes: ' . count($commonSectionCodes));
    }
}
