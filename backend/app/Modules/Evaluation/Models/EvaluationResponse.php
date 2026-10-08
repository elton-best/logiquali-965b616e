<?php

namespace App\Modules\Evaluation\Models;

use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationResponse extends Model
{
    use HasFactory, HasReference;

    protected $fillable = [
        'ref',
        'evaluation_request_id',
        'responses',
        'total_score',
        'weighted_score',
        'percentage',
        'general_comment',
        'recommendations',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'responses' => 'array',
        'total_score' => 'decimal:2',
        'weighted_score' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    /**
     * Préfixe pour la référence auto-générée
     */
    public function getReferencePrefix(): string
    {
        return 'EVAL-RESP';
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function request(): BelongsTo
    {
        return $this->belongsTo(EvaluationRequest::class, 'evaluation_request_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Calculer les scores à partir des réponses
     */
    public function calculateScores(array $criteria): void
    {
        $responses = $this->responses ?? [];
        $totalScore = 0;
        $weightedScore = 0;
        $maxWeightedScore = 0;

        foreach ($criteria as $criterion) {
            $criterionId = $criterion['id'];
            if (isset($responses[$criterionId]['score'])) {
                $score = (float) $responses[$criterionId]['score'];
                $weight = (float) ($criterion['weight'] ?? 1);
                $maxScore = (float) ($criterion['scale_max'] ?? 4);

                $totalScore += $score;
                $weightedScore += $score * $weight;
                $maxWeightedScore += $maxScore * $weight;
            }
        }

        $this->total_score = $totalScore;
        $this->weighted_score = $weightedScore;
        $this->percentage = $maxWeightedScore > 0
            ? round(($weightedScore / $maxWeightedScore) * 100, 2)
            : 0;

        $this->save();
    }

    /**
     * Obtenir le score pour un critère spécifique
     */
    public function getScoreForCriterion(int $criterionId): ?float
    {
        return $this->responses[$criterionId]['score'] ?? null;
    }

    /**
     * Obtenir le commentaire pour un critère spécifique
     */
    public function getCommentForCriterion(int $criterionId): ?string
    {
        return $this->responses[$criterionId]['comment'] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Obtenir le niveau de satisfaction basé sur le pourcentage
     */
    public function getSatisfactionLevelAttribute(): string
    {
        $percentage = $this->percentage ?? 0;

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

    public function getSatisfactionLevelLabelAttribute(): string
    {
        return match ($this->satisfaction_level) {
            'very_satisfied' => 'Très satisfait',
            'satisfied' => 'Satisfait',
            'dissatisfied' => 'Insatisfait',
            'very_dissatisfied' => 'Très insatisfait',
            default => 'Non défini',
        };
    }
}
