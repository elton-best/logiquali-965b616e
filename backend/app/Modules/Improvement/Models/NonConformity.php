<?php

namespace App\Modules\Improvement\Models;

use App\Models\Action;
use App\Models\Audit;
use App\Models\Document;
use App\Models\Process;
use App\Models\Risk;
use App\Models\Site;
use App\Models\User;

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
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Traits\LogsActivity;

class NonConformity extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, HasAxes, HasWorkflowStates, HasActions, LogsActivity, Searchable, BelongsToEnterprise;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'process_id',
        'audit_id',
        'workflow_state_id',
        'title',
        'finding_type',
        'type',
        'source',
        'detection_source',
        'severity',
        'priority',
        'description',
        'requirement_reference',
        'detected_by',
        'detected_at',
        'root_cause_analysis',
        'cause_analysis',
        'impacts',
        'corrective_action',
        'preventive_action',
        'responsible_id',
        'investigator_user_ids',
        'deadline',
        'resolution_date',
        'effectiveness_verified',
        'verification_date',
        'verified_by',
        'verification_notes',
        'result_summary',
        'rq_signature_date',
        'cost_impact',
        'status',
    ];

    protected $casts = [
        'deadline' => 'date',
        'resolution_date' => 'date',
        'verification_date' => 'date',
        'rq_signature_date' => 'date',
        'detected_at' => 'date',
        'effectiveness_verified' => 'boolean',
        'investigator_user_ids' => 'array',
        'root_cause_analysis' => 'array',
        'cause_analysis' => 'array',
        'impacts' => 'array',
        'cost_impact' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::created(function (NonConformity $nc) {
            if ($nc->actions()->exists()) {
                return;
            }

            // Some NCs (e.g. audit findings without mapped process) cannot spawn an Action
            // because actions.process_id is required.
            if (empty($nc->process_id)) {
                return;
            }

            $enterpriseId = $nc->site?->enterprise_id;
            $responsibleId = $nc->responsible_id ?: $nc->detected_by;

            $action = Action::create([
                'enterprise_id' => $enterpriseId,
                'site_id' => $nc->site_id,
                'process_id' => $nc->process_id,
                'type' => 'corrective',
                'title' => $nc->title ?: 'Action corrective - NC ' . $nc->ref,
                'description' => $nc->description ?? 'Action corrective générée automatiquement.',
                'source' => 'non_conformity',
                'initiator_id' => $nc->detected_by,
                'responsible_id' => $responsibleId,
                'deadline' => $nc->deadline,
                'status' => 'planned',
            ]);

            $nc->actions()->attach($action->id);
        });
    }

    protected static $logAttributes = ['ref', 'status', 'severity', 'effectiveness_verified'];
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['ref', 'status', 'severity', 'effectiveness_verified'])
            ->logOnlyDirty();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
    
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function detectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'detected_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function documents(): MorphToMany
    {
        return $this->morphToMany(Document::class, 'documentable');
    }

    public function processes(): MorphToMany
    {
        return $this->morphToMany(Process::class, 'processable');
    }

    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class, 'nc_risk')
            ->withPivot('relation_type')
            ->withTimestamps();
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !$this->resolution_date;
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
            'immediate_action' => $this->immediate_action,
            'root_cause' => $this->root_cause,
            'type' => $this->type,
            'source' => $this->source,
            'gravity' => $this->gravity,
            'process' => $this->process?->title,
        ];
    }
}
