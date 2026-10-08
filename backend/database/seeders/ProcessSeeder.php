<?php

namespace Database\Seeders;

use App\Models\Process;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🔄 Création des processus de base pour tous les sites...\n";

        // Récupérer tous les sites
        $sites = DB::table('sites')->get();
        
        if ($sites->isEmpty()) {
            echo "❌ Aucun site trouvé.\n";
            return;
        }

        echo "📍 Sites trouvés : " . $sites->count() . "\n";

        $processTemplates = [
            [
                'code' => 'PROC-001',
                'title' => 'Gestion de la qualité',
                'type' => 'management',
                'category' => 'pilotage',
                'purpose' => 'Assurer la conformité aux exigences qualité et l\'amélioration continue du système de management de la qualité.'
            ],
            [
                'code' => 'PROC-002',
                'title' => 'Gestion des achats',
                'type' => 'support',
                'category' => 'support',
                'purpose' => 'Gérer l\'acquisition de biens et services conformes aux exigences de l\'entreprise.'
            ],
            [
                'code' => 'PROC-003',
                'title' => 'Gestion des ressources humaines',
                'type' => 'support',
                'category' => 'support',
                'purpose' => 'Assurer la gestion efficace des compétences, formations et ressources humaines.'
            ],
            [
                'code' => 'PROC-004',
                'title' => 'Gestion commerciale',
                'type' => 'operational',
                'category' => 'operationnel',
                'purpose' => 'Gérer les relations clients, les offres commerciales et les commandes.'
            ],
            [
                'code' => 'PROC-005',
                'title' => 'Gestion de la production',
                'type' => 'operational',
                'category' => 'operationnel',
                'purpose' => 'Planifier, organiser et contrôler les activités de production.'
            ],
            [
                'code' => 'PROC-006',
                'title' => 'Contrôle qualité',
                'type' => 'operational',
                'category' => 'operationnel',
                'purpose' => 'Vérifier la conformité des produits/services aux exigences définies.'
            ],
            [
                'code' => 'PROC-007',
                'title' => 'Gestion des non-conformités',
                'type' => 'management',
                'category' => 'mesure_amelioration',
                'purpose' => 'Identifier, traiter et suivre les non-conformités pour prévenir leur récurrence.'
            ],
            [
                'code' => 'PROC-008',
                'title' => 'Amélioration continue',
                'type' => 'management',
                'category' => 'mesure_amelioration',
                'purpose' => 'Piloter les actions d\'amélioration continue du système de management.'
            ],
        ];

        $totalCreated = 0;

        foreach ($sites as $site) {
            echo "\n🏢 Site : {$site->name} (ID: {$site->id})\n";
            
            // Récupérer le premier utilisateur admin du site comme pilote
            $pilot = User::where('site_id', $site->id)
                ->where('is_active', true)
                ->first();
            
            if (!$pilot) {
                // Fallback sur premier utilisateur de l'entreprise
                $pilot = User::where('enterprise_id', $site->enterprise_id)
                    ->where('is_active', true)
                    ->first();
            }
            
            if (!$pilot) {
                echo "  ⚠️ Aucun pilote trouvé, processus ignorés\n";
                continue;
            }

            foreach ($processTemplates as $index => $processData) {
                // Code unique par site
                $uniqueCode = $processData['code'] . '-S' . $site->id;
                
                $processData['site_id'] = $site->id;
                $processData['pilot_id'] = $pilot->id;
                $processData['code'] = $uniqueCode;
                $processData['ref'] = 'PROC-' . date('Y') . '-S' . $site->id . '-' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);

                $process = Process::updateOrCreate(
                    ['code' => $uniqueCode],
                    $processData
                );

                echo "  ✓ {$process->code} - {$process->title}\n";
                $totalCreated++;
            }
        }

        echo "\n✅ {$totalCreated} processus créés avec succès pour " . $sites->count() . " site(s)\n";
    }
}
