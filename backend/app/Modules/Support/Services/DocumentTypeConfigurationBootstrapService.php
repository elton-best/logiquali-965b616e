<?php

namespace App\Modules\Support\Services;

use App\Models\CodeStructurePart;
use App\Models\DocumentTypeConfiguration;
use App\Models\Enterprise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service d'initialisation des configurations documentaires par défaut.
 *
 * Crée automatiquement les 5 types documentaires standards ISO (POL, PRC, PRD, FOR, ENR)
 * pour chaque nouvelle entreprise avec scope = 'enterprise' (couvre tous les sites).
 *
 * Ce service est idempotent : il ne recrée pas les types qui existent déjà.
 *
 * Appelé depuis :
 * - EnterpriseObserver::created()
 * - DatabaseSeeder (pour les entreprises existantes)
 */
class DocumentTypeConfigurationBootstrapService
{
    /**
     * Configurations documentaires standards ISO.
     * Chaque type a une structure de code définie par des parties ordonnées.
     */
    private const DEFAULT_CONFIGURATIONS = [
        [
            'name'        => 'Politique',
            'abbreviation' => 'POL',
            'description' => 'Documents de politique QHSE (politique qualité, environnementale, SST…)',
            'structure'   => [
                ['part_name' => 'Type',      'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Séquence',  'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process'],
            ],
        ],
        [
            'name'        => 'Procédure',
            'abbreviation' => 'PRC',
            'description' => 'Procédures opérationnelles et modes opératoires',
            'structure'   => [
                ['part_name' => 'Type',      'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Année',     'part_type' => 'year', 'part_length' => 4, 'separator_after' => '_'],
                ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Séquence',  'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process_year'],
            ],
        ],
        [
            'name'        => 'Instruction',
            'abbreviation' => 'PRD',
            'description' => 'Instructions de travail, fiches processus, fiches techniques',
            'structure'   => [
                ['part_name' => 'Type',      'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Séquence',  'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process'],
            ],
        ],
        [
            'name'        => 'Formulaire',
            'abbreviation' => 'FOR',
            'description' => 'Formulaires, modèles et templates documentaires',
            'structure'   => [
                ['part_name' => 'Type',      'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Année',     'part_type' => 'year', 'part_length' => 4, 'separator_after' => '_'],
                ['part_name' => 'Mois',      'part_type' => 'month', 'part_length' => 2, 'separator_after' => '_'],
                ['part_name' => 'Séquence',  'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process_year_month'],
            ],
        ],
        [
            'name'        => 'Enregistrement',
            'abbreviation' => 'ENR',
            'description' => 'Enregistrements, registres, rapports et preuves documentaires',
            'structure'   => [
                ['part_name' => 'Type',      'part_type' => 'fixed_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Processus', 'part_type' => 'process_abbreviation', 'part_length' => 3, 'separator_after' => '_'],
                ['part_name' => 'Séquence',  'part_type' => 'sequence', 'part_length' => 3, 'separator_after' => null, 'sequence_scope' => 'by_type_process'],
            ],
        ],
    ];

    /**
     * Initialise les configurations documentaires par défaut pour une entreprise.
     *
     * Idempotent : ne recrée pas les types qui existent déjà pour cette entreprise.
     *
     * @param Enterprise $enterprise L'entreprise à initialiser
     * @return array{created: int, skipped: int} Nombre de configs créées et ignorées
     */
    public function initializeForEnterprise(Enterprise $enterprise): array
    {
        $enterpriseId = (int) $enterprise->id;
        $created      = 0;
        $skipped      = 0;

        DB::transaction(function () use ($enterpriseId, &$created, &$skipped) {
            foreach (self::DEFAULT_CONFIGURATIONS as $configData) {
                // Vérifier si la configuration existe déjà pour cette entreprise
                $exists = DocumentTypeConfiguration::query()
                    ->where('enterprise_id', $enterpriseId)
                    ->where('abbreviation', $configData['abbreviation'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Créer la configuration avec scope = 'enterprise' pour couvrir tous les sites
                $config = DocumentTypeConfiguration::create([
                    'enterprise_id'      => $enterpriseId,
                    'site_id'            => null, // scope enterprise = tous les sites
                    'name'               => $configData['name'],
                    'abbreviation'       => $configData['abbreviation'],
                    'abbreviation_length' => 3,
                    'scope'              => 'enterprise',
                    'is_active'          => true,
                    'description'        => $configData['description'],
                ]);

                // Créer les parties de la structure
                foreach ($configData['structure'] as $index => $partData) {
                    CodeStructurePart::create([
                        'document_type_configuration_id' => $config->id,
                        'part_order'                     => $index + 1,
                        'part_name'                      => $partData['part_name'],
                        'part_type'                      => $partData['part_type'],
                        'part_length'                    => $partData['part_length'],
                        'separator_after'                => $partData['separator_after'] ?? null,
                        'is_required'                    => true,
                        'sequence_scope'                 => $partData['sequence_scope'] ?? null,
                    ]);
                }

                $created++;
            }
        });

        Log::info('DocumentTypeConfigurationBootstrapService: initialisation terminée', [
            'enterprise_id' => $enterpriseId,
            'created'       => $created,
            'skipped'       => $skipped,
        ]);

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Initialise les configurations pour toutes les entreprises qui n'en ont pas encore.
     * Utilisé par le DatabaseSeeder pour les entreprises existantes.
     *
     * @return array{total: int, initialized: int, already_configured: int}
     */
    public function initializeForAllEnterprises(): array
    {
        $enterprises        = Enterprise::all();
        $initialized        = 0;
        $alreadyConfigured  = 0;

        foreach ($enterprises as $enterprise) {
            $result = $this->initializeForEnterprise($enterprise);

            if ($result['created'] > 0) {
                $initialized++;
            } else {
                $alreadyConfigured++;
            }
        }

        return [
            'total'              => $enterprises->count(),
            'initialized'        => $initialized,
            'already_configured' => $alreadyConfigured,
        ];
    }
}
