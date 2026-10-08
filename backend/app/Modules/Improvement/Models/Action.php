<?php

namespace App\Modules\Improvement\Models;

use App\Traits\BelongsToEnterprise;
use App\Traits\HasAuditFields;
use App\Traits\HasAxes;
use App\Traits\HasReference;
use App\Traits\HasWorkflowStates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Action extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasAxes, HasReference, HasWorkflowStates, LogsActivity, BelongsToEnterprise;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'process_id',
        'source_type',
        'source_id',
        'objective_id',
        'risk_id',
        'non_conformity_id',
        'audit_id',
        'complaint_id',
        'plan_action_id',
        'workflow_state_id',
        'type',
        'priority',
        'title',
        'description',
        'expected_result',
        'source',
        'initiator_id',
        'root_cause',
        'immediate_action',
        'immediate_action_date',
        'responsible_id',
        'approved_by',
        'approval_date',
        'approval_notes',
        'deadline',
        'start_date',
        'completion_date',
        'progress',
        'progress_percentage',
        'progress_notes',
        'm7_d4_traceability',
        'estimated_cost',
        'actual_cost',
        'required_resources',
        'status',
        'effectiveness_verified',
        'verification_date',
        'effectiveness_verification_date',
        'effectiveness_comments',
        'verified_by',
        'proof_path',
        'delay_alert_threshold',
        'delay_days',
        'progress_rate',
    ];

    protected $casts = [
        'deadline' => 'date',
        'immediate_action_date' => 'date',
        'approval_date' => 'date',
        'verification_date' => 'date',
        'start_date' => 'date',
        'completion_date' => 'date',
        'effectiveness_verification_date' => 'date',
        'progress' => 'integer',
        'progress_percentage' => 'integer',
        'progress_notes' => 'array',
        'm7_d4_traceability' => 'array',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'effectiveness_verified' => 'boolean',
        'progress_rate' => 'integer',
    ];

    protected static $logAttributes = ['ref', 'title', 'status', 'progress', 'effectiveness_verified'];
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['ref', 'title', 'status', 'progress', 'effectiveness_verified'])
            ->logOnlyDirty();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
    
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }
    
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function planAction(): BelongsTo
    {
        return $this->belongsTo(PlanAction::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Alias for responsible (used in notifications)
     */
    public function pilot(): BelongsTo
    {
        return $this->responsible();
    }

    /**
     * Manager accessor (for notifications) - returns initiator or responsible's manager
     */
    public function manager(): BelongsTo
    {
        return $this->initiator();
    }

    /**
     * Get pilot attribute (alias for responsible)
     */
    public function getPilotAttribute()
    {
        return $this->responsible;
    }

    /**
     * Get manager attribute (alias for initiator)
     */
    public function getManagerAttribute()
    {
        return $this->initiator;
    }

    public function actionables(): MorphToMany
    {
        return $this->morphedByMany('*', 'actionable');
    }

    public function nonConformities(): MorphToMany
    {
        return $this->morphedByMany(NonConformity::class, 'actionable');
    }

    public function audits(): MorphToMany
    {
        return $this->morphedByMany(Audit::class, 'actionable');
    }

    public function objectives(): MorphToMany
    {
        return $this->morphedByMany(Objective::class, 'actionable');
    }

    public function risks(): MorphToMany
    {
        return $this->morphedByMany(Risk::class, 'actionable');
    }

    public function reclamations(): MorphToMany
    {
        return $this->morphedByMany(Reclamation::class, 'actionable');
    }

    public function documents(): MorphToMany
    {
        return $this->morphToMany(Document::class, 'documentable');
    }

    public function processes(): MorphToMany
    {
        return $this->morphToMany(Process::class, 'processable');
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !$this->isFinal();
    }

    public function addProgressNote(string $note): void
    {
        $actor = auth()->user();
        $notes = $this->progress_notes ?? [];
        $notes[] = [
            'date' => now()->format('Y-m-d H:i:s'),
            'note' => $note,
            'user_id' => auth()->id(),
            'user_name' => $actor?->name ?? $actor?->username,
        ];
        $this->progress_notes = $notes;
        $this->save();
    }

    protected static function booted()
    {
        static::creating(function (Action $action) {
            if (!$action->enterprise_id && $action->site_id) {
                $action->enterprise_id = Site::where('id', $action->site_id)->value('enterprise_id');
            }
        });

        static::saved(function ($action) {
            if ($action->plan_action_id && $action->isDirty('progress')) {
                $action->planAction?->updateProgress();
            }
        });

        static::saving(function (Action $action) {
            if ($action->deadline) {
                $today = now()->startOfDay();
                $deadline = $action->deadline instanceof \Illuminate\Support\Carbon ? $action->deadline->startOfDay() : \Illuminate\Support\Carbon::parse($action->deadline)->startOfDay();
                $action->delay_days = $today->greaterThan($deadline) ? $deadline->diffInDays($today) : 0;
            }
            if ($action->progress !== null && $action->progress_rate === null) {
                $action->progress_rate = $action->progress;
            }
        });
    }
}
