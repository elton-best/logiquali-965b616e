<?php

namespace App\Modules\Evaluation\Models;

use App\Traits\BelongsToEnterprise;
use App\Traits\HasActions;
use App\Traits\HasAuditFields;
use App\Traits\HasAxes;
use App\Traits\HasReference;
use App\Traits\HasWorkflowStates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Audit extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, HasAxes, HasWorkflowStates, HasActions, LogsActivity, BelongsToEnterprise;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'audit_program_id',
        'site_id',
        'workflow_state_id',
        'type',
        'title',
        'planned_date',
        'actual_date',
        'quarter',
        'frequency',
        'lead_auditor_id',
        'assigned_to',
        'team_members',
        'scope',
        'objectives',
        'reference_documents',
        'risk_based_criteria',
        'risk_based_priority',
        'checklist',
        'findings',
        'conclusion',
        'conformity_rate',
        'status',
        'global_report_path',
        'report_path',
        'external_report_path',
        'report_source',
        'report_version',
        'external_report_uploaded_at',
        'attachments',
        'm9_d2_traceability',
        'm9_d5_traceability',
        // Nouveaux champs pour compléments et rappels
        'additional_info',
        'input_elements',
        'specific_points',
        'reminder_enabled',
        'reminder_days_before',
        'reminder_sent_at',
        'sm_synthesis',
        'synthesis_generated_at',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'quarter' => 'integer',
        'team_members' => 'array',
        'checklist' => 'array',
        'findings' => 'array',
        'attachments' => 'array',
        'conformity_rate' => 'integer',
        'risk_based_priority' => 'integer',
        'external_report_uploaded_at' => 'datetime',
        'report_version' => 'integer',
        'm9_d2_traceability' => 'array',
        'm9_d5_traceability' => 'array',
        // Nouveaux casts
        'additional_info' => 'array',
        'input_elements' => 'array',
        'specific_points' => 'array',
        'reminder_enabled' => 'boolean',
        'reminder_days_before' => 'integer',
        'reminder_sent_at' => 'datetime',
        'sm_synthesis' => 'array',
        'synthesis_generated_at' => 'datetime',
    ];

    protected static $logAttributes = ['ref', 'title', 'status', 'conformity_rate'];
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['ref', 'title', 'status', 'conformity_rate'])
            ->logOnlyDirty();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function leadAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_auditor_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function nonConformities(): HasMany
    {
        return $this->hasMany(NonConformity::class);
    }

    public function documents(): MorphToMany
    {
        return $this->morphToMany(Document::class, 'documentable');
    }

    public function processes(): BelongsToMany
    {
        return $this->belongsToMany(Process::class, 'audit_process')
            ->withPivot(['priority', 'risk_level'])
            ->withTimestamps();
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(AuditChecklistItem::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AuditProgram::class, 'audit_program_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
    }

    public function auditors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_auditor')
            ->withPivot(['role', 'is_independent', 'expertise_areas'])
            ->withTimestamps();
    }

    public function auditees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_auditee')
            ->withPivot(['process_id', 'status', 'invited_at', 'confirmed_at'])
            ->withTimestamps();
    }

    public function auditedProcesses(): BelongsToMany
    {
        return $this->belongsToMany(Process::class, 'audit_process')
            ->withPivot(['priority', 'risk_level'])
            ->withTimestamps();
    }

    public function relatedRisks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'audit_risk')
            ->withPivot(['verification_status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Audit reminders
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(\App\Models\AuditReminder::class);
    }

    /**
     * Norm input elements for the audit
     */
    public function normInputs(): HasMany
    {
        return $this->hasMany(\App\Models\AuditNormInput::class);
    }

    public function calculateConformityRate(): void
    {
        $checklist = $this->checklist ?? [];
        if (empty($checklist)) {
            $this->conformity_rate = null;
            return;
        }

        $conformCount = collect($checklist)->where('conformity', 'conform')->count();
        $total = count($checklist);

        $this->conformity_rate = $total > 0 ? round(($conformCount / $total) * 100) : 0;
        $this->save();
    }

    /**
     * Scopes
     */
    public function scopeForProgram($query, int $programId)
    {
        return $query->where('audit_program_id', $programId);
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('planned_date', $year);
    }

    /**
     * Backward compatibility for legacy payloads using scheduled_date.
     */
    public function setScheduledDateAttribute($value): void
    {
        $this->attributes['planned_date'] = $value;
    }

    public function getScheduledDateAttribute()
    {
        return $this->planned_date;
    }
}
