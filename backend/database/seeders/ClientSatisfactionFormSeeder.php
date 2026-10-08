<?php

namespace Database\Seeders;

use App\Models\ClientSatisfactionForm;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSatisfactionFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifier qu'il y a des sites
        $sites = Site::all();
        if ($sites->isEmpty()) {
            $this->command->warn('Aucun site trouvé. Création de données de satisfaction ignorée.');
            return;
        }

        // Trouver un utilisateur Client B
        $clientBUser = User::where('user_type', 'clientb')->first();
        if (!$clientBUser) {
            $this->command->warn('Aucun utilisateur Client B trouvé. Création de données de satisfaction ignorée.');
            return;
        }

        $this->command->info('Création de fiches de satisfaction client...');

        // Créer des fiches pour les 6 derniers mois
        $companies = [
            'SARL LogiTech',
            'ENTREPRISE BENIN SERVICES',
            'STE COTONOU DISTRIBUTION',
            'GROUPE ALPHA CONSEIL',
            'BENIN EXPORT SA',
        ];

        $currentDate = now();

        foreach ($companies as $index => $companyName) {
            // Créer 2-3 fiches par entreprise
            $numberOfForms = rand(2, 3);
            
            for ($i = 0; $i < $numberOfForms; $i++) {
                $monthsAgo = ($index * 2) + $i;
                $surveyDate = $currentDate->copy()->subMonths($monthsAgo);
                
                // Scores aléatoires mais réalistes
                $scores = [
                    'amabilite_ecoute' => $this->getWeightedRandomScore(),
                    'disponibilite_spontaneite' => $this->getWeightedRandomScore(),
                    'rapidite_traitement' => $this->getWeightedRandomScore(),
                    'respect_delais' => $this->getWeightedRandomScore(),
                    'conformite_produits' => $this->getWeightedRandomScore(),
                    'traitement_reclamations' => $this->getWeightedRandomScore(),
                ];

                $totalScore = array_sum($scores);
                $percentage = ($totalScore / 24) * 100;

                // Statut varié
                $statuses = ['draft', 'submitted', 'reviewed'];
                $status = $statuses[array_rand($statuses)];

                $form = ClientSatisfactionForm::create([
                    'site_id' => $sites->random()->id,
                    'client_name' => $companyName,
                    'survey_date' => $surveyDate,
                    'amabilite_ecoute' => $scores['amabilite_ecoute'],
                    'disponibilite_spontaneite' => $scores['disponibilite_spontaneite'],
                    'rapidite_traitement' => $scores['rapidite_traitement'],
                    'respect_delais' => $scores['respect_delais'],
                    'conformite_produits' => $scores['conformite_produits'],
                    'traitement_reclamations' => $scores['traitement_reclamations'],
                    'recommendations' => $this->getRecommendation($percentage),
                    'status' => $status,
                    'submitted_at' => $status !== 'draft' ? $surveyDate->copy()->addDays(1) : null,
                    'reviewed_at' => $status === 'reviewed' ? $surveyDate->copy()->addDays(3) : null,
                    'reviewed_by' => $status === 'reviewed' ? $clientBUser->id : null,
                    'created_by' => $clientBUser->id,
                ]);

                $this->command->info("✓ Fiche créée: {$form->ref} - {$companyName} - Score: {$totalScore}/24");
            }
        }

        $this->command->info('Fiches de satisfaction créées avec succès!');
    }

    /**
     * Génère un score pondéré (plus de scores élevés que faibles)
     */
    private function getWeightedRandomScore(): int
    {
        $random = rand(1, 100);
        
        if ($random <= 10) {
            return 1; // 10% de chances
        } elseif ($random <= 30) {
            return 2; // 20% de chances
        } elseif ($random <= 70) {
            return 3; // 40% de chances
        } else {
            return 4; // 30% de chances
        }
    }

    /**
     * Génère une recommandation basée sur le pourcentage
     */
    private function getRecommendation(float $percentage): ?string
    {
        if ($percentage >= 90) {
            $recommendations = [
                'Continuer dans cette voie. Excellente prestation.',
                'Très satisfait de la qualité du service. Aucune amélioration nécessaire.',
                'Service impeccable. Nous recommandons vivement.',
            ];
        } elseif ($percentage >= 70) {
            $recommendations = [
                'Bon service dans l\'ensemble. Quelques améliorations possibles sur les délais.',
                'Satisfait de la prestation. Améliorer la rapidité de traitement des demandes.',
                'Bonne qualité générale. Travailler sur la disponibilité.',
            ];
        } elseif ($percentage >= 50) {
            $recommendations = [
                'Améliorer les délais de livraison et la conformité des produits.',
                'Revoir le processus de traitement des réclamations.',
                'Service acceptable mais des progrès sont nécessaires sur plusieurs aspects.',
            ];
        } else {
            $recommendations = [
                'Amélioration urgente nécessaire sur tous les critères.',
                'Délais non respectés, produits non conformes. Action corrective immédiate requise.',
                'Insatisfait de la prestation globale. Révision complète du service nécessaire.',
            ];
        }

        return $recommendations[array_rand($recommendations)];
    }
}
