<?php

namespace App\Models;

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
use Spatie\Activitylog\Traits\LogsActivity;

class Objective extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, HasAxes, HasWorkflowStates, HasActions, LogsActivity, BelongsToEnterprise;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'process_id',
        'site_id',
        'strategic_axis_id',
        'workflow_state_id',
        'indicateur_id',
        'type',
        'is_smart_validated',
        'smart_criteria',
        'title',
        'description',
        'start_date',
        'indicator',
        'target_value',
        'current_value',
        'progress',
        'milestones',
        'responsible_id',
        'deadline',
        'allocated_budget',
        'required_resources',
        'measurement_frequency',
        'applicable_norms',
        'status',
    ];

    protected $casts = [
        'deadline' => 'date',
        'start_date' => 'date',
        'is_smart_validated' => 'boolean',
        'smart_criteria' => 'array',
        'milestones' => 'array',
        'progress' => 'integer',
        'allocated_budget' => 'decimal:2',
        'applicable_norms' => 'array',
    ];

    protected static $logAttributes = ['ref', 'title', 'status', 'progress', 'current_value'];
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['ref', 'title', 'status', 'progress', 'current_value'])
            ->logOnlyDirty();
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function strategicAxis(): BelongsTo
    {
        return $this->belongsTo(StrategicAxis::class);
    }

    public function indicateur(): BelongsTo
    {
        return $this->belongsTo(Indicateur::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function documents(): MorphToMany
    {
        return $this->morphToMany(Document::class, 'documentable');
    }

    public function processes(): MorphToMany
    {
        return $this->morphToMany(Process::class, 'processable');
    }

    public function updateProgress(): void
    {
        // If objective has linked actions, compute average progress of those actions
        try {
            $actionsCount = $this->actions()->count();
        } catch (\Throwable $e) {
            $actionsCount = 0;
        }

        if ($actionsCount > 0) {
            // Use progress_rate if available, fallback to progress
            $avg = $this->actions()
                ->selectRaw('AVG(COALESCE(progress_rate, progress, 0)) as avg_progress')
                ->value('avg_progress');

            $this->progress = min(100, max(0, (int) round($avg ?? 0)));
            $this->save();
            return;
        }

        // Fallback to indicator values if no actions
        if (!$this->target_value || !$this->current_value) {
            $this->progress = 0;
            $this->save();
            return;
        }

        $progress = ($this->current_value / $this->target_value) * 100;
        $this->progress = min(100, max(0, round($progress)));
        $this->save();
    }

    public function isAchieved(): bool
    {
        return $this->current_value >= $this->target_value;
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !$this->isAchieved();
    }
}
