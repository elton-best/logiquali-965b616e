<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientSatisfactionFormResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ref' => $this->ref,
            'site_id' => $this->site_id,
            'site' => new SiteResource($this->whenLoaded('site')),
            'client_name' => $this->client_name,
            'survey_date' => $this->survey_date?->format('Y-m-d'),
            'survey_date_formatted' => $this->survey_date?->format('d/m/Y'),
            
            // Critères individuels
            'criteria' => [
                'amabilite_ecoute' => [
                    'label' => 'Aimabilité et écoute du client',
                    'score' => $this->amabilite_ecoute,
                    'color' => $this->getScoreColor($this->amabilite_ecoute),
                ],
                'disponibilite_spontaneite' => [
                    'label' => 'Disponibilité/Spontanéité',
                    'score' => $this->disponibilite_spontaneite,
                    'color' => $this->getScoreColor($this->disponibilite_spontaneite),
                ],
                'rapidite_traitement' => [
                    'label' => 'Rapidité dans le traitement des requêtes',
                    'score' => $this->rapidite_traitement,
                    'color' => $this->getScoreColor($this->rapidite_traitement),
                ],
                'respect_delais' => [
                    'label' => 'Respect des délais de livraison',
                    'score' => $this->respect_delais,
                    'color' => $this->getScoreColor($this->respect_delais),
                ],
                'conformite_produits' => [
                    'label' => 'Conformité des produits livrés par rapport aux commandes',
                    'score' => $this->conformite_produits,
                    'color' => $this->getScoreColor($this->conformite_produits),
                ],
                'traitement_reclamations' => [
                    'label' => 'Traitement des réclamations et plaintes',
                    'score' => $this->traitement_reclamations,
                    'color' => $this->getScoreColor($this->traitement_reclamations),
                ],
            ],
            
            // Scores globaux
            'total_score' => $this->total_score,
            'max_score' => 24,
            'satisfaction_percentage' => round($this->satisfaction_percentage, 2),
            'satisfaction_level' => $this->satisfaction_level,
            'satisfaction_level_label' => $this->satisfaction_level_label,
            'satisfaction_color' => $this->getSatisfactionColor(),
            
            // Recommandations
            'recommendations' => $this->recommendations,
            'm13_d4_traceability' => $this->m13_d4_traceability,
            'm13_d6_traceability' => $this->m13_d6_traceability,
            
            // Métadonnées
            'status' => $this->status,
            'status_label' => $this->status_label,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'reviewed_by' => $this->reviewed_by,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'created_by' => $this->created_by,
        ];
    }

    /**
     * Obtenir la couleur basée sur le score (1-4)
     */
    private function getScoreColor(int $score): string
    {
        return match($score) {
            4 => 'success',      // Très satisfait - Vert
            3 => 'info',         // Satisfait - Bleu
            2 => 'warning',      // Insatisfait - Orange
            1 => 'error',        // Très insatisfait - Rouge
            default => 'grey',
        };
    }

    /**
     * Obtenir la couleur du niveau de satisfaction global
     */
    private function getSatisfactionColor(): string
    {
        return match($this->satisfaction_level) {
            'very_satisfied' => 'success',
            'satisfied' => 'info',
            'dissatisfied' => 'warning',
            'very_dissatisfied' => 'error',
            default => 'grey',
        };
    }
}
