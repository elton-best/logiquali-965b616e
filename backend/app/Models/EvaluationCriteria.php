<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EvaluationCriteria extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, LogsActivity, BelongsToEnterprise;

    protected $table = 'evaluation_criteria';

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'name',
        'code',
        'description',
        'category',
        'scale_type',
        'scale_min',
        'scale_max',
        'scale_labels',
        'weight',
        'is_mandatory',
        'is_active',
        'form_type',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scale_labels' => 'array',
        'weight' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'scale_min' => 'integer',
        'scale_max' => 'integer',
        'display_order' => 'integer',
    ];

    protected $attributes = [
        'scale_type' => 'numeric',
        'scale_min' => 0,
        'scale_max' => 4,
        'weight' => 1.00,
        'is_mandatory' => true,
        'is_active' => true,
        'form_type' => 'custom',
        'display_order' => 0,
    ];

    /**
     * Préfixe pour la référence auto-générée
     */
    public function getReferencePrefix(): string
    {
        return 'CRIT';
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'category', 'is_active', 'form_type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Formulaires de satisfaction liés
     */
    public function satisfactionForms(): MorphToMany
    {
        return $this->morphedByMany(ClientSatisfactionForm::class, 'form', 'evaluation_criteria_form')
            ->withPivot(['score', 'comment'])
            ->withTimestamps();
    }

    /**
     * Audits liés
     */
    public function audits(): MorphToMany
    {
        return $this->morphedByMany(Audit::class, 'form', 'evaluation_criteria_form')
            ->withPivot(['score', 'comment'])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFormType($query, string $formType)
    {
        return $query->where('form_type', $formType);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('category')->orderBy('display_order')->orderBy('name');
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Obtenir les labels d'échelle formatés
     */
    public function getScaleOptionsAttribute(): array
    {
        $options = [];
        $labels = $this->scale_labels ?? [];

        for ($i = $this->scale_min; $i <= $this->scale_max; $i++) {
            $options[$i] = $labels[$i] ?? (string) $i;
        }

        return $options;
    }

    /**
     * Obtenir le label pour un score donné
     */
    public function getLabelForScore(int $score): string
    {
        $labels = $this->scale_labels ?? [];
        return $labels[$score] ?? (string) $score;
    }

    /**
     * Calculer le score pondéré
     */
    public function calculateWeightedScore(int $rawScore): float
    {
        return $rawScore * $this->weight;
    }

    /**
     * Obtenir le score maximum possible pondéré
     */
    public function getMaxWeightedScoreAttribute(): float
    {
        return $this->scale_max * $this->weight;
    }

    /**
     * Label du type de formulaire
     */
    public function getFormTypeLabelAttribute(): string
    {
        return match ($this->form_type) {
            'satisfaction_client' => 'Satisfaction client',
            'satisfaction_personnel' => 'Satisfaction personnel',
            'performance_personnel' => 'Performance personnel',
            'evaluation_personnel' => 'Évaluation personnel',
            'evaluation_auditeur' => 'Évaluation auditeur',
            'satisfaction_fournisseur' => 'Satisfaction prestataire',
            'performance_fournisseur' => 'Performance prestataire',
            'evaluation_fournisseur' => 'Évaluation fournisseur',
            'audit_interne' => 'Audit interne',
            'custom' => 'Personnalisé',
            default => $this->form_type,
        };
    }

    /**
     * Label du type d'échelle
     */
    public function getScaleTypeLabelAttribute(): string
    {
        return match ($this->scale_type) {
            'numeric' => 'Numérique',
            'stars' => 'Étoiles',
            'percentage' => 'Pourcentage',
            'custom' => 'Personnalisé',
            default => $this->scale_type,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Static methods - Default criteria templates
    |--------------------------------------------------------------------------
    */

    /**
     * Critères par défaut pour la satisfaction client (basés sur ClientSatisfactionForm existant)
     */
    public static function getDefaultSatisfactionClientCriteria(): array
    {
        return [
            [
                'code' => 'SC1',
                'name' => 'Amabilité et écoute du client',
                'category' => 'Relation client',
                'scale_labels' => [
                    0 => 'Non applicable',
                    1 => 'Très insatisfait',
                    2 => 'Insatisfait',
                    3 => 'Satisfait',
                    4 => 'Très satisfait',
                ],
            ],
            [
                'code' => 'SC2',
                'name' => 'Disponibilité/Spontanéité',
                'category' => 'Relation client',
                'scale_labels' => [
                    0 => 'Non applicable',
                    1 => 'Très insatisfait',
                    2 => 'Insatisfait',
                    3 => 'Satisfait',
                    4 => 'Très satisfait',
                ],
            ],
            [
                'code' => 'SC3',
                'name' => 'Rapidité dans le traitement des requêtes',
                'category' => 'Réactivité',
                'scale_labels' => [
                    0 => 'Non applicable',
                    1 => 'Très insatisfait',
                    2 => 'Insatisfait',
                    3 => 'Satisfait',
                    4 => 'Très satisfait',
                ],
            ],
            [
                'code' => 'SC4',
                'name' => 'Respect des délais de livraison',
                'category' => 'Réactivité',
                'scale_labels' => [
                    0 => 'Non applicable',
                    1 => 'Très insatisfait',
                    2 => 'Insatisfait',
                    3 => 'Satisfait',
                    4 => 'Très satisfait',
                ],
            ],
            [
                'code' => 'SC5',
                'name' => 'Conformité des produits livrés par rapport aux commandes',
                'category' => 'Qualité',
                'scale_labels' => [
                    0 => 'Non applicable',
                    1 => 'Très insatisfait',
                    2 => 'Insatisfait',
                    3 => 'Satisfait',
                    4 => 'Très satisfait',
                ],
            ],
            [
                'code' => 'SC6',
                'name' => 'Traitement des réclamations et plaintes',
                'category' => 'Qualité',
                'scale_labels' => [
                    0 => 'Non applicable',
                    1 => 'Très insatisfait',
                    2 => 'Insatisfait',
                    3 => 'Satisfait',
                    4 => 'Très satisfait',
                ],
            ],
        ];
    }

    /**
     * Critères par défaut pour l'évaluation des auditeurs internes
     */
    public static function getDefaultAuditorEvaluationCriteria(): array
    {
        return [
            [
                'code' => 'AU1',
                'name' => 'Maîtrise des techniques d\'audit',
                'category' => 'Compétences techniques',
                'scale_labels' => [
                    0 => 'Non évalué',
                    1 => 'À améliorer',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
            [
                'code' => 'AU2',
                'name' => 'Connaissance des référentiels normatifs',
                'category' => 'Compétences techniques',
                'scale_labels' => [
                    0 => 'Non évalué',
                    1 => 'À améliorer',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
            [
                'code' => 'AU3',
                'name' => 'Capacité d\'analyse et de synthèse',
                'category' => 'Compétences comportementales',
                'scale_labels' => [
                    0 => 'Non évalué',
                    1 => 'À améliorer',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
            [
                'code' => 'AU4',
                'name' => 'Communication et écoute',
                'category' => 'Compétences comportementales',
                'scale_labels' => [
                    0 => 'Non évalué',
                    1 => 'À améliorer',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
            [
                'code' => 'AU5',
                'name' => 'Objectivité et impartialité',
                'category' => 'Éthique',
                'scale_labels' => [
                    0 => 'Non évalué',
                    1 => 'À améliorer',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
            [
                'code' => 'AU6',
                'name' => 'Qualité des rapports d\'audit',
                'category' => 'Livrables',
                'scale_labels' => [
                    0 => 'Non évalué',
                    1 => 'À améliorer',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
        ];
    }

    /**
     * Critères par défaut pour l'évaluation du personnel (échelle 1-3)
     */
    public static function getDefaultEvaluationPersonnelCriteria(): array
    {
        return [
            [
                'code' => 'EP1',
                'name' => 'Qualité du travail',
                'category' => 'Performance',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 3,
                'scale_labels' => [
                    1 => 'Insuffisant',
                    2 => 'Correct',
                    3 => 'Excellent',
                ],
            ],
            [
                'code' => 'EP2', 
                'name' => 'Respect des délais',
                'category' => 'Performance',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 3,
                'scale_labels' => [
                    1 => 'Insuffisant',
                    2 => 'Correct', 
                    3 => 'Excellent',
                ],
            ],
            [
                'code' => 'EP3',
                'name' => 'Autonomie',
                'category' => 'Comportement',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 3,
                'scale_labels' => [
                    1 => 'Insuffisant',
                    2 => 'Correct',
                    3 => 'Excellent',
                ],
            ],
            [
                'code' => 'EP4',
                'name' => 'Esprit d\'équipe',
                'category' => 'Comportement',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 3,
                'scale_labels' => [
                    1 => 'Insuffisant',
                    2 => 'Correct',
                    3 => 'Excellent',
                ],
            ],
            [
                'code' => 'EP5',
                'name' => 'Initiative et proactivité',
                'category' => 'Comportement',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 3,
                'scale_labels' => [
                    1 => 'Insuffisant',
                    2 => 'Correct',
                    3 => 'Excellent',
                ],
            ],
        ];
    }

    /**
     * Critères par défaut pour l'évaluation des prestataires.
     */
    public static function getDefaultSupplierEvaluationCriteria(): array
    {
        return [
            [
                'code' => 'FR1',
                'name' => 'Qualité des livrables',
                'category' => 'Qualité',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 4,
                'scale_labels' => [
                    1 => 'Insuffisant',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
            [
                'code' => 'FR2',
                'name' => 'Respect des délais',
                'category' => 'Performance',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 4,
                'scale_labels' => [
                    1 => 'Insuffisant',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
            [
                'code' => 'FR3',
                'name' => 'Réactivité en cas d\'incident',
                'category' => 'Service',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 4,
                'scale_labels' => [
                    1 => 'Insuffisant',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
            [
                'code' => 'FR4',
                'name' => 'Conformité documentaire',
                'category' => 'Conformité',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 4,
                'scale_labels' => [
                    1 => 'Insuffisant',
                    2 => 'Acceptable',
                    3 => 'Bon',
                    4 => 'Excellent',
                ],
            ],
        ];
    }

    /**
     * Critères par défaut pour les questionnaires audit interne.
     */
    public static function getDefaultInternalAuditCriteria(): array
    {
        return [
            [
                'code' => 'AI1',
                'name' => 'Conformité aux exigences applicables',
                'category' => 'Conformité',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 4,
                'scale_labels' => [
                    1 => 'Non conforme',
                    2 => 'Partiellement conforme',
                    3 => 'Conforme',
                    4 => 'Conforme avec bonnes pratiques',
                ],
            ],
            [
                'code' => 'AI2',
                'name' => 'Efficacité du processus audité',
                'category' => 'Efficacité',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 4,
                'scale_labels' => [
                    1 => 'Faible',
                    2 => 'Moyenne',
                    3 => 'Bonne',
                    4 => 'Excellente',
                ],
            ],
            [
                'code' => 'AI3',
                'name' => 'Maîtrise des risques opérationnels',
                'category' => 'Risque',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 4,
                'scale_labels' => [
                    1 => 'Non maîtrisé',
                    2 => 'Partiellement maîtrisé',
                    3 => 'Maîtrisé',
                    4 => 'Très bien maîtrisé',
                ],
            ],
            [
                'code' => 'AI4',
                'name' => 'Traçabilité et preuves disponibles',
                'category' => 'Documentation',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 4,
                'scale_labels' => [
                    1 => 'Insuffisante',
                    2 => 'Moyenne',
                    3 => 'Bonne',
                    4 => 'Très bonne',
                ],
            ],
        ];
    }
}
