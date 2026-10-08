<?php

namespace App\Modules\Evaluation\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AuditProgram extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, LogsActivity;

    protected $fillable = [
        'ref',
        'site_id',
        'year',
        'title',
        'program_manager_id',
        'objectives',
        'scope',
        'target_processes',
        'target_sites',
        'risk_based_criteria',
        'planned_audits_count',
        'completed_audits_count',
        'avg_conformity_rate',
        'conformity_rate',
        'nc_major_count',
        'nc_minor_count',
        'observations_count',
        'last_review_date',
        'next_review_date',
        'status',
        'validated_by',
        'validated_at',
        'notes',
    ];

    protected $attributes = [
        'status' => 'draft',
        'completed_audits_count' => 0,
        'nc_major_count' => 0,
        'nc_minor_count' => 0,
        'observations_count' => 0,
    ];

    protected $casts = [
        'year' => 'integer',
        'target_processes' => 'array',
        'target_sites' => 'array',
        'risk_based_criteria' => 'array',
        'planned_audits_count' => 'integer',
        'completed_audits_count' => 'integer',
        'conformity_rate' => 'integer',
        'nc_major_count' => 'integer',
        'nc_minor_count' => 'integer',
        'observations_count' => 'integer',
        'last_review_date' => 'date',
        'next_review_date' => 'date',
        'validated_at' => 'datetime',
    ];

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($program) {
            // Génération ref par HasReference trait
            
            // Auto-set title si vide
            if (empty($program->title)) {
                $program->title = "Programme Audits Internes {$program->year}";
            }
        });
        
        // Mise à jour auto statistiques quand audits changent
        static::updating(function ($program) {
            $program->calculateStatistics();
        });
    }

    /**
     * Format de référence pour le trait HasReference
     * PRG-AUDIT-YYYY-NNN
     */
    public function getReferencePrefix(): string
    {
        return 'PRG-AUDIT';
    }

    /**
     * Calcule les statistiques du programme
     */
    public function calculateStatistics(): void
    {
        $audits = $this->audits()->get();
        
        $this->completed_audits_count = $audits->where('status', 'completed')->count();
        
        // Taux de conformité moyen
        $conformityRates = $audits->whereNotNull('conformity_rate')->pluck('conformity_rate');
        $this->conformity_rate = $conformityRates->isNotEmpty() 
            ? round($conformityRates->average()) 
            : null;
        
        // Comptage NC et observations
        $this->nc_major_count = 0;
        $this->nc_minor_count = 0;
        $this->observations_count = 0;
        
        foreach ($audits as $audit) {
            $findings = $audit->findings ?? [];
            foreach ($findings as $finding) {
                if ($finding['type'] === 'nc_major') $this->nc_major_count++;
                if ($finding['type'] === 'nc_minor') $this->nc_minor_count++;
                if ($finding['type'] === 'observation') $this->observations_count++;
            }
        }
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ref', 'year', 'status', 'conformity_rate'])
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

    public function programManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'program_manager_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class, 'audit_program_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['validated', 'in_progress']);
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    public function getCompletionRateAttribute(): int
    {
        if ($this->planned_audits_count === 0) {
            return 0;
        }
        return round(($this->completed_audits_count / $this->planned_audits_count) * 100);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'validated' => 'Validé',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'archived' => 'Archivé',
            default => $this->status
        };
    }
}
