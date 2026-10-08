<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enterprise = Enterprise::first();
        
        if (!$enterprise) {
            $this->command->error('Aucune entreprise trouvée. Exécutez d\'abord les seeders de base.');
            return;
        }

        $sites = Site::where('enterprise_id', $enterprise->id)->get();
        $users = User::where('enterprise_id', $enterprise->id)->get();

        if ($sites->isEmpty() || $users->isEmpty()) {
            $this->command->error('Aucun site ou utilisateur trouvé pour cette entreprise.');
            return;
        }

        $audits = [
            [
                'type' => 'internal',
                'title' => 'Audit Interne ISO 9001 - Système de Management de la Qualité',
                'scope' => 'Processus de production et contrôle qualité',
                'objectives' => 'Vérifier la conformité aux exigences ISO 9001:2015 et identifier les opportunités d\'amélioration',
                'status' => 'planned',
                'planned_date' => now()->addDays(15),
                'conformity_rate' => null,
            ],
            [
                'type' => 'external',
                'title' => 'Audit de Surveillance ISO 14001 - Management Environnemental',
                'scope' => 'Gestion des déchets et impacts environnementaux',
                'objectives' => 'Évaluation de la conformité réglementaire et efficacité du SME',
                'status' => 'in_progress',
                'planned_date' => now()->subDays(2),
                'actual_date' => now()->subDays(2),
                'conformity_rate' => null,
            ],
            [
                'type' => 'certification',
                'title' => 'Audit de Certification ISO 45001 - Santé et Sécurité au Travail',
                'scope' => 'Ensemble des processus SST de l\'entreprise',
                'objectives' => 'Obtention de la certification ISO 45001',
                'status' => 'completed',
                'planned_date' => now()->subDays(30),
                'actual_date' => now()->subDays(25),
                'conformity_rate' => 92,
                'conclusion' => 'Le système de management SST est globalement conforme. 3 non-conformités mineures identifiées.',
            ],
            [
                'type' => 'internal',
                'title' => 'Audit Processus Achats et Fournisseurs',
                'scope' => 'Processus d\'évaluation et qualification des fournisseurs',
                'objectives' => 'Vérifier l\'efficacité du processus d\'achat et la gestion des risques fournisseurs',
                'status' => 'completed',
                'planned_date' => now()->subDays(60),
                'actual_date' => now()->subDays(55),
                'conformity_rate' => 88,
                'conclusion' => 'Processus mature avec quelques axes d\'amélioration identifiés sur la documentation.',
            ],
            [
                'type' => 'internal',
                'title' => 'Audit Gestion Documentaire et Enregistrements',
                'scope' => 'Système de gestion documentaire qualité',
                'objectives' => 'Vérifier la maîtrise de la documentation et des enregistrements',
                'status' => 'completed',
                'planned_date' => now()->subDays(90),
                'actual_date' => now()->subDays(85),
                'conformity_rate' => 95,
                'conclusion' => 'Excellent niveau de maîtrise documentaire. Plan d\'action clôturé.',
            ],
        ];

        foreach ($audits as $index => $auditData) {
            $site = $sites->random();
            $leadAuditor = $users->random();
            
            // Sélectionner 1-3 auditeurs et 2-4 audités différents
            $auditorsIds = $users->random(rand(1, min(3, $users->count())))
                ->pluck('id')
                ->toArray();
            
            $auditeesIds = $users->random(rand(2, min(4, $users->count())))
                ->pluck('id')
                ->toArray();

            $audit = Audit::create([
                'ref' => 'AUD-' . now()->year . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'site_id' => $site->id,
                'type' => $auditData['type'],
                'title' => $auditData['title'],
                'scope' => $auditData['scope'],
                'objectives' => $auditData['objectives'],
                'status' => $auditData['status'],
                'planned_date' => $auditData['planned_date'],
                'actual_date' => $auditData['actual_date'] ?? null,
                'lead_auditor_id' => $leadAuditor->id,
                'auditors' => $auditorsIds,
                'auditees' => $auditeesIds,
                'conformity_rate' => $auditData['conformity_rate'],
                'conclusion' => $auditData['conclusion'] ?? null,
                'risk_based_criteria' => 'Basé sur l\'analyse des risques et opportunités du système de management',
                'checklist' => $this->generateChecklist($auditData['type']),
                'findings' => $this->generateFindings($auditData['status']),
            ]);

            $this->command->info("✓ Audit créé: {$audit->ref} - {$audit->title}");
        }

        $this->command->info("\n✅ " . count($audits) . " audits créés avec succès");
    }

    private function generateChecklist(string $type): array
    {
        $baseLists = [
            'internal' => [
                'Revue de la documentation applicable',
                'Entretiens avec les responsables de processus',
                'Observation des activités sur le terrain',
                'Vérification des enregistrements',
                'Échantillonnage des produits/services',
            ],
            'external' => [
                'Revue documentaire préalable',
                'Audit sur site des installations',
                'Entretiens avec la direction',
                'Vérification conformité réglementaire',
                'Examen des indicateurs de performance',
            ],
            'certification' => [
                'Revue complète du manuel qualité',
                'Audit de tous les processus clés',
                'Vérification amélioration continue',
                'Entretiens multi-niveaux',
                'Inspection des installations',
                'Revue des audits internes',
            ],
        ];

        return $baseLists[$type] ?? $baseLists['internal'];
    }

    private function generateFindings(string $status): array
    {
        if ($status === 'planned') {
            return [];
        }

        if ($status === 'in_progress') {
            return [
                [
                    'type' => 'observation',
                    'description' => 'Documentation partiellement à jour',
                    'reference' => 'ISO 9001 §7.5',
                ],
            ];
        }

        return [
            [
                'type' => 'nc_minor',
                'description' => 'Enregistrements de formation incomplets pour 2 opérateurs',
                'reference' => 'ISO 9001 §7.2',
                'evidence' => 'Registre de formation 2025',
            ],
            [
                'type' => 'observation',
                'description' => 'Indicateurs de performance affichés mais non analysés mensuellement',
                'reference' => 'ISO 9001 §9.1.3',
            ],
            [
                'type' => 'strength',
                'description' => 'Excellente implication de la direction dans les revues qualité',
                'reference' => 'ISO 9001 §5.1',
            ],
        ];
    }
}
