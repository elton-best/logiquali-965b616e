<?php

namespace Database\Seeders;

use App\Models\Process;
use App\Models\Site;
use Illuminate\Database\Seeder;

class ProcessCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $processes = [
            [
                'code' => 'PLT',
                'name' => 'Pilotage',
                'description' => 'Processus de pilotage et management',
                'type' => 'management',
            ],
            [
                'code' => 'QAM',
                'name' => 'Qualité et Amélioration',
                'description' => 'Processus qualité et amélioration continue',
                'type' => 'support',
            ],
            [
                'code' => 'RH',
                'name' => 'Ressources Humaines',
                'description' => 'Gestion des ressources humaines',
                'type' => 'support',
            ],
            [
                'code' => 'COM',
                'name' => 'Commercial',
                'description' => 'Processus commercial et relation client',
                'type' => 'operational',
            ],
            [
                'code' => 'PRO',
                'name' => 'Production',
                'description' => 'Processus de production',
                'type' => 'operational',
            ],
            [
                'code' => 'OMI',
                'name' => 'Opérations Maintenance Industrielle',
                'description' => 'Maintenance et opérations industrielles',
                'type' => 'operational',
            ],
            [
                'code' => 'REL',
                'name' => 'Relations Externes',
                'description' => 'Gestion des relations externes et partenaires',
                'type' => 'support',
            ],
            [
                'code' => 'ACH',
                'name' => 'Achats',
                'description' => 'Processus achats et approvisionnements',
                'type' => 'support',
            ],
            [
                'code' => 'LOG',
                'name' => 'Logistique',
                'description' => 'Gestion logistique et supply chain',
                'type' => 'operational',
            ],
            [
                'code' => 'FIN',
                'name' => 'Finance',
                'description' => 'Gestion financière et comptabilité',
                'type' => 'support',
            ],
        ];

        // Récupérer tous les sites actifs
        $sites = Site::whereHas('enterprise', function ($query) {
            $query->where('is_active', true);
        })->get();

        foreach ($sites as $site) {
            // Récupérer un utilisateur par défaut pour le site (pilote)
            $defaultPilot = \App\Models\User::where('site_id', $site->id)->first();
            
            if (!$defaultPilot) {
                $this->command->warn("Aucun utilisateur trouvé pour le site {$site->name}, processus ignorés");
                continue;
            }

            foreach ($processes as $processData) {
                // Vérifier si le processus existe déjà pour ce site
                $exists = Process::where('site_id', $site->id)
                    ->where('code', $processData['code'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Créer le processus
                Process::create([
                    'site_id' => $site->id,
                    'code' => $processData['code'],
                    'title' => $processData['name'],
                    'purpose' => $processData['description'],
                    'type' => $processData['type'],
                    'status' => 'active',
                    'pilot_id' => $defaultPilot->id,
                ]);

                $this->command->info("✓ Processus '{$processData['name']}' créé pour site {$site->name}");
            }
        }
    }
}
