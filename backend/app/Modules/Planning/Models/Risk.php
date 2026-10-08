<?php

namespace App\Modules\Planning\Models;

use App\Traits\BelongsToEnterprise;
use App\Traits\HasActions;
use App\Traits\HasAuditFields;
use App\Traits\HasAxes;
use App\Traits\HasReference;
use App\Traits\HasWorkflowStates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Traits\LogsActivity;

class Risk extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, HasAxes, HasWorkflowStates, HasActions, LogsActivity, Searchable, BelongsToEnterprise;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'risk_number',
        'title',
        'process_id',
        'site_id',
        'workflow_state_id',
        'type',
        'category',
        'description',
        'root_cause',
        'causes',
        'probability',
        'severity',
        'gravity',
        'probability_score',
        'severity_score',
        'residual_probability',
        'residual_gravity',
        'impact',
        'criticality',
        'criticality_score',
        'criticality_level',
        'actual_occurrences',
        'last_occurrence_at',
        'initial_assessment',
        'residual_assessment',
        'control_action',
        'treatment',
        'treatment_plan',
        'affected_stakeholders',
        'duerp_reference',
        'prevention_measures',
        'responsible_id',
        'mitigation_responsible_id',
        'responsible_user_id',
        'deadline',
        'last_review_date',
        'next_review_date',
        'validated_by',
        'validation_date',
        'status',
        'applicable_norms',
        'action_ids',
        'follow_up_status',
    ];

    protected $casts = [
        'probability' => 'integer',
        'gravity' => 'integer',
        'probability_score' => 'integer',
        'severity_score' => 'integer',
        'impact' => 'integer',
        'criticality' => 'integer',
        'criticality_score' => 'integer',
        'initial_assessment' => 'array',
        'residual_assessment' => 'array',
        'affected_stakeholders' => 'array',
        'applicable_norms' => 'array',
        'action_ids' => 'array',
        'deadline' => 'date',
        'last_review_date' => 'date',
        'next_review_date' => 'date',
        'validation_date' => 'date',
    ];

    protected static $logAttributes = ['ref', 'type', 'probability', 'gravity', 'criticality', 'status'];
    protected static $logOnlyDirty = true;

    protected static function boot()
    {
        parent::boot();
        
        // Auto-calculate criticality when probability or gravity changes
        static::saving(function ($risk) {
            if (!$risk->enterprise_id && $risk->site_id) {
                $risk->enterprise_id = Site::where('id', $risk->site_id)->value('enterprise_id');
            }
            if (empty($risk->risk_number)) {
                $month = strtoupper(now()->format('M'));
                $year = now()->format('Y');
                $prefix = "RSK_{$month}_{$year}_";
                $last = static::where('risk_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
                $seq = 1;
                if ($last && preg_match('/_(\\d+)$/', $last->risk_number, $matches)) {
                    $seq = (int) $matches[1] + 1;
                }
                $risk->risk_number = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            }

            if ($risk->isDirty(['probability_score', 'severity_score'])) {
                $risk->criticality_score = ($risk->probability_score ?? 1) * ($risk->severity_score ?? 1);
                $risk->criticality_level = static::calculateCriticalityLevel($risk->criticality_score);
            }

            if ($risk->isDirty(['probability', 'gravity'])) {
                $risk->criticality = ($risk->probability ?? 1) * ($risk->gravity ?? 1);
                $risk->criticality_level = static::calculateCriticalityLevel($risk->criticality);
            }

            if (!$risk->probability_score && $risk->probability) {
                $risk->probability_score = $risk->probability;
            }
            if (!$risk->severity_score && $risk->gravity) {
                $risk->severity_score = $risk->gravity;
            }
            
            // Auto-calculate residual criticality
            if ($risk->isDirty(['residual_probability', 'residual_gravity'])) {
                $residual = ($risk->residual_probability ?? 0) * ($risk->residual_gravity ?? 0);
                if (isset($risk->residual_assessment)) {
                    $risk->residual_assessment = array_merge(
                        $risk->residual_assessment ?? [],
                        ['residual_criticality' => $residual]
                    );
                }
                
                // Calculate risk reduction percentage
                if ($risk->criticality > 0) {
                    $risk->risk_reduction_percentage = round(
                        (($risk->criticality - $residual) / $risk->criticality) * 100
                    );
                }
            }
        });
    }

    protected static function calculateCriticalityLevel(int $criticality): string
    {
        return match(true) {
            $criticality >= 12 => 'critical',
            $criticality >= 8 => 'high',
            $criticality >= 4 => 'medium',
            default => 'low',
        };
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['ref', 'type', 'probability', 'gravity', 'criticality', 'status'])
            ->logOnlyDirty();
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function mitigationResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mitigation_responsible_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function documents(): MorphToMany
    {
        return $this->morphToMany(Document::class, 'documentable');
    }

    public function processes(): MorphToMany
    {
        return $this->morphToMany(Process::class, 'processable');
    }

    public function calculateCriticality(): void
    {
        $value = ($this->type === 'risk') 
            ? $this->probability * $this->gravity
            : $this->probability * $this->impact;

        $this->criticality = $value;
        $this->save();
    }

    public function isHighRisk(): bool
    {
        return $this->criticality >= 12; // Matrice 4x4: 12-16 = critique
    }

    public function isMediumRisk(): bool
    {
        return $this->criticality >= 8 && $this->criticality < 12;
    }

    public function needsReview(): bool
    {
        return $this->next_review_date && $this->next_review_date->isPast();
    }

    public function scopeRisks($query)
    {
        return $query->where('type', 'risk');
    }

    public function scopeOpportunities($query)
    {
        return $query->where('type', 'opportunity');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('criticality', '>=', 12);
    }

    /**
     * Get the indexable data array for Scout.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'ref' => $this->ref,
            'title' => $this->title,
            'description' => $this->description,
            'root_cause' => $this->root_cause,
            'type' => $this->type,
            'category' => $this->category,
            'criticality' => $this->criticality,
            'probability' => $this->probability,
            'gravity' => $this->gravity,
            'process' => $this->process?->title,
        ];
    }
}
