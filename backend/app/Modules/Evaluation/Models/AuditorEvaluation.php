<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditFields;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AuditorEvaluation extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, LogsActivity;

    protected $fillable = [
        'user_id',
        'audit_id',
        'type',
        'technical_knowledge',
        'iso_knowledge',
        'audit_methodology',
        'communication_skills',
        'objectivity',
        'report_writing',
        'overall_score',
        'certifications',
        'formations',
        'qualified_domains',
        'audits_conducted',
        'first_audit_date',
        'last_audit_date',
        'independence_check',
        'strengths',
        'improvement_areas',
        'action_plan',
        'm9_d5_traceability',
        'status',
        'evaluated_by',
        'evaluated_at',
        'validity_date',
    ];

    protected $attributes = [
        'status' => 'valid',
        'audits_conducted' => 0,
        'technical_knowledge' => 0,
        'iso_knowledge' => 0,
        'audit_methodology' => 0,
        'communication_skills' => 0,
        'objectivity' => 0,
        'report_writing' => 0,
    ];

    protected $casts = [
        'technical_knowledge' => 'integer',
        'iso_knowledge' => 'integer',
        'audit_methodology' => 'integer',
        'communication_skills' => 'integer',
        'objectivity' => 'integer',
        'report_writing' => 'integer',
        'overall_score' => 'decimal:2',
        'certifications' => 'array',
        'formations' => 'array',
        'qualified_domains' => 'array',
        'audits_conducted' => 'integer',
        'first_audit_date' => 'date',
        'last_audit_date' => 'date',
        'independence_check' => 'array',
        'm9_d5_traceability' => 'array',
        'evaluated_at' => 'datetime',
        'validity_date' => 'date',
    ];

    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // Calcul automatique score global
        static::saving(function ($evaluation) {
            $evaluation->calculateOverallScore();
            
            // Auto-définir validité (3 ans par défaut)
            if (empty($evaluation->validity_date) && $evaluation->evaluated_at) {
                $evaluation->validity_date = $evaluation->evaluated_at->copy()->addYears(3);
            }
        });
        
        // Vérification expiration
        static::retrieved(function ($evaluation) {
            if ($evaluation->validity_date && $evaluation->validity_date->isPast() && $evaluation->status === 'validated') {
                $evaluation->update(['status' => 'expired']);
            }
        });
    }

    /**
     * Calcule le score global /5
     */
    public function calculateOverallScore(): void
    {
        $scores = [
            $this->technical_knowledge,
            $this->iso_knowledge,
            $this->audit_methodology,
            $this->communication_skills,
            $this->objectivity,
            $this->report_writing,
        ];

        $validScores = array_filter($scores, fn($s) => !is_null($s));
        
        if (count($validScores) > 0) {
            $this->overall_score = round(array_sum($validScores) / count($validScores), 2);
        }
    }

    /**
     * Vérifie si l'auditeur est qualifié pour un domaine
     */
    public function isQualifiedFor(string $domain): bool
    {
        $qualifiedDomains = $this->qualified_domains ?? [];
        return in_array($domain, $qualifiedDomains) 
            && $this->status === 'validated' 
            && (!$this->validity_date || $this->validity_date->isFuture());
    }

    /**
     * Vérifie l'indépendance pour un processus/site
     */
    public function checkIndependence(int $processId = null, int $siteId = null): bool
    {
        $check = $this->independence_check ?? [];
        
        // Si pas de conflit enregistré, on considère indépendant
        if (empty($check['conflicts'])) {
            return true;
        }

        // Vérifier conflits processus
        if ($processId && in_array($processId, $check['conflicts']['processes'] ?? [])) {
            return false;
        }

        // Vérifier conflits site
        if ($siteId && in_array($siteId, $check['conflicts']['sites'] ?? [])) {
            return false;
        }

        return true;
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'overall_score', 'status', 'qualified_domains'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('validity_date')
              ->orWhere('validity_date', '>', now());
        });
    }

    public function scopeQualifiedFor($query, string $domain)
    {
        return $query->validated()
            ->notExpired()
            ->whereJsonContains('qualified_domains', $domain);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'competence' => 'Évaluation Compétence',
            'post_audit' => 'Post-Audit',
            'annual_review' => 'Revue Annuelle',
            default => $this->type
        };
    }

    public function getScoreLevelAttribute(): string
    {
        if (is_null($this->overall_score)) {
            return 'Non évalué';
        }

        return match(true) {
            $this->overall_score >= 4.5 => 'Excellent',
            $this->overall_score >= 3.5 => 'Très Bon',
            $this->overall_score >= 2.5 => 'Satisfaisant',
            $this->overall_score >= 1.5 => 'Insuffisant',
            default => 'Non Qualifié'
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->validity_date && $this->validity_date->isPast();
    }

    public function getIsValidAttribute(): bool
    {
        return $this->status === 'validated' && !$this->is_expired;
    }
}
