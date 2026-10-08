<?php

namespace Database\Seeders;

use App\Models\DocumentTypeConfiguration;
use App\Models\CodeStructurePart;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Database\Seeder;

class DocumentTypeConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer toutes les entreprises actives
        $enterprises = Enterprise::all();

        foreach ($enterprises as $enterprise) {
            // Récupérer le premier site de l'entreprise pour la config par défaut
            $site = Site::where('enterprise_id', $enterprise->id)->first();

            if (!$site) {
                continue;
            }

            $this->createDefaultConfigurations($enterprise, $site);
        }
    }

    /**
     * Crée les 5 configurations par défaut pour une entreprise
     */
    private function createDefaultConfigurations(Enterprise $enterprise, Site $site): void
    {
        $configurations = [
            [
                'name' => 'Politique',
                'abbreviation' => 'POL',
                'description' => 'Documents de politique QHSE',
                'structure' => [
                    ['part_name' => 'Type', 'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Séquence', 'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process'],
                ],
            ],
            [
                'name' => 'Procédure',
                'abbreviation' => 'PRC',
                'description' => 'Procédures opérationnelles',
                'structure' => [
                    ['part_name' => 'Type', 'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Année', 'part_type' => 'year', 'part_length' => 4, 'separator_after' => '_'],
                    ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Séquence', 'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process_year'],
                ],
            ],
            [
                'name' => 'Instruction',
                'abbreviation' => 'PRD',
                'description' => 'Instructions de travail',
                'structure' => [
                    ['part_name' => 'Type', 'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Séquence', 'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process'],
                ],
            ],
            [
                'name' => 'Formulaire',
                'abbreviation' => 'FOR',
                'description' => 'Formulaires et modèles',
                'structure' => [
                    ['part_name' => 'Type', 'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Année', 'part_type' => 'year', 'part_length' => 4, 'separator_after' => '_'],
                    ['part_name' => 'Mois', 'part_type' => 'month', 'part_length' => 2, 'separator_after' => '_'],
                    ['part_name' => 'Séquence', 'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process_year_month'],
                ],
            ],
            [
                'name' => 'Enregistrement',
                'abbreviation' => 'ENR',
                'description' => 'Enregistrements et preuves',
                'structure' => [
                    ['part_name' => 'Type', 'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                    ['part_name' => 'Séquence', 'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process'],
                ],
            ],
        ];

        foreach ($configurations as $configData) {
            // Vérifier si la configuration existe déjà
            $exists = DocumentTypeConfiguration::where('enterprise_id', $enterprise->id)
                ->where('abbreviation', $configData['abbreviation'])
                ->exists();

            if ($exists) {
                continue;
            }

            // Créer la configuration
            $config = DocumentTypeConfiguration::create([
                'enterprise_id' => $enterprise->id,
                'site_id' => $site->id,
                'name' => $configData['name'],
                'abbreviation' => $configData['abbreviation'],
                'abbreviation_length' => 3,
                'scope' => 'site',
                'is_active' => true,
                'description' => $configData['description'],
            ]);

            // Créer les parties de la structure
            foreach ($configData['structure'] as $index => $partData) {
                CodeStructurePart::create([
                    'document_type_configuration_id' => $config->id,
                    'part_order' => $index + 1,
                    'part_name' => $partData['part_name'],
                    'part_type' => $partData['part_type'],
                    'part_length' => $partData['part_length'],
                    'separator_after' => $partData['separator_after'],
                    'is_required' => true,
                    'sequence_scope' => $partData['sequence_scope'] ?? null,
                ]);
            }

            $this->command->info("✓ Configuration '{$configData['name']}' créée pour {$enterprise->name}");
        }
    }
}
