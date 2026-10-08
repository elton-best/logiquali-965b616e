<?php

namespace App\Modules\Evaluation\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientSatisfactionForm extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'site_id',
        'client_name',
        'survey_date',
        'amabilite_ecoute',
        'disponibilite_spontaneite',
        'rapidite_traitement',
        'respect_delais',
        'conformite_produits',
        'traitement_reclamations',
        'total_score',
        'satisfaction_percentage',
        'satisfaction_level',
        'recommendations',
        'm13_d4_traceability',
        'm13_d6_traceability',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'survey_date' => 'date',
            'amabilite_ecoute' => 'integer',
            'disponibilite_spontaneite' => 'integer',
            'rapidite_traitement' => 'integer',
            'respect_delais' => 'integer',
            'conformite_produits' => 'integer',
            'traitement_reclamations' => 'integer',
            'total_score' => 'integer',
            'satisfaction_percentage' => 'decimal:2',
            'm13_d4_traceability' => 'array',
            'm13_d6_traceability' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Préfixe pour la référence auto-générée
     */
    protected function getReferencePrefix(): string
    {
        return 'FS';
    }

    /**
     * Relations
     */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Calcul automatique des scores avant sauvegarde
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($form) {
            $form->calculateScores();
        });
    }

    /**
     * Calcule le score total et le pourcentage de satisfaction
     */
    public function calculateScores(): void
    {
        $criteria = [
            $this->amabilite_ecoute,
            $this->disponibilite_spontaneite,
            $this->rapidite_traitement,
            $this->respect_delais,
            $this->conformite_produits,
            $this->traitement_reclamations,
        ];

        // Calcul du score total
        $this->total_score = array_sum($criteria);

        // Calcul du pourcentage (score max = 6 critères × 4 points = 24)
        $maxScore = 24;
        $this->satisfaction_percentage = ($this->total_score / $maxScore) * 100;

        // Détermination du niveau de satisfaction
        $this->satisfaction_level = $this->determineSatisfactionLevel();
    }

    /**
     * Détermine le niveau de satisfaction basé sur le pourcentage
     */
    private function determineSatisfactionLevel(): string
    {
        $percentage = $this->satisfaction_percentage;

        if ($percentage >= 90) {
            return 'very_satisfied';
        } elseif ($percentage >= 70) {
            return 'satisfied';
        } elseif ($percentage >= 50) {
            return 'dissatisfied';
        } else {
            return 'very_dissatisfied';
        }
    }

    /**
     * Soumet la fiche (change le statut)
     */
    public function submit(): void
    {
        $this->status = 'submitted';
        $this->submitted_at = now();
        $this->save();
    }

    /**
     * Marque la fiche comme revue
     */
    public function markAsReviewed(int $reviewerId): void
    {
        $this->status = 'reviewed';
        $this->reviewed_at = now();
        $this->reviewed_by = $reviewerId;
        $this->save();
    }

    /**
     * Scopes
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeVerySatisfied($query)
    {
        return $query->where('satisfaction_level', 'very_satisfied');
    }

    public function scopeSatisfied($query)
    {
        return $query->where('satisfaction_level', 'satisfied');
    }

    public function scopeDissatisfied($query)
    {
        return $query->whereIn('satisfaction_level', ['dissatisfied', 'very_dissatisfied']);
    }

    /**
     * Accesseurs pour labels lisibles
     */
    public function getSatisfactionLevelLabelAttribute(): string
    {
        return match($this->satisfaction_level) {
            'very_satisfied' => 'Très satisfait',
            'satisfied' => 'Satisfait',
            'dissatisfied' => 'Insatisfait',
            'very_dissatisfied' => 'Très insatisfait',
            default => 'Non défini',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'submitted' => 'Soumis',
            'reviewed' => 'Examiné',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir tous les critères sous forme de tableau
     */
    public function getCriteriaAttribute(): array
    {
        return [
            'amabilite_ecoute' => [
                'label' => 'Aimabilité et écoute du client',
                'score' => $this->amabilite_ecoute,
            ],
            'disponibilite_spontaneite' => [
                'label' => 'Disponibilité/Spontanéité',
                'score' => $this->disponibilite_spontaneite,
            ],
            'rapidite_traitement' => [
                'label' => 'Rapidité dans le traitement des requêtes',
                'score' => $this->rapidite_traitement,
            ],
            'respect_delais' => [
                'label' => 'Respect des délais de livraison',
                'score' => $this->respect_delais,
            ],
            'conformite_produits' => [
                'label' => 'Conformité des produits livrés par rapport aux commandes',
                'score' => $this->conformite_produits,
            ],
            'traitement_reclamations' => [
                'label' => 'Traitement des réclamations et plaintes',
                'score' => $this->traitement_reclamations,
            ],
        ];
    }
}
