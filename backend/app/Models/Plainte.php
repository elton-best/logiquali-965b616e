<?php

namespace App\Models;

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

class Plainte extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, HasAxes, HasWorkflowStates, HasActions, LogsActivity;

    protected $fillable = [
        'ref',
        'site_id',
        'workflow_state_id',
        'plaignant_name',
        'plaignant_email',
        'plaignant_phone',
        'plaignant_company',
        'stakeholder_type',
        'title',
        'description',
        'category',
        'severity',
        'priority',
        'received_date',
        'due_date',
        'closed_date',
        'assigned_to',
        'analysis',
        'immediate_response',
        'response_date',
        'responded_by',
        'corrective_actions',
        'preventive_actions',
        'confidential',
        'anonymous',
        'satisfaction_rating',
        'satisfaction_comment',
        'satisfaction_date',
        'cost_impact',
        'status',
    ];

    protected $casts = [
        'confidential' => 'boolean',
        'anonymous' => 'boolean',
        'satisfaction_rating' => 'integer',
        'cost_impact' => 'decimal:2',
        'received_date' => 'date',
        'due_date' => 'date',
        'closed_date' => 'date',
        'response_date' => 'date',
        'satisfaction_date' => 'date',
    ];

    protected static $logAttributes = ['ref', 'title', 'status', 'severity', 'satisfaction_rating'];
    protected static $logOnlyDirty = true;

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($plainte) {
            // Auto-generate reference if not provided
            if (empty($plainte->ref)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $plainte->ref = sprintf('PLT_%d_%04d', $year, $count);
            }
            
            // Calculate response deadline based on severity
            if (empty($plainte->due_date) && !empty($plainte->received_date)) {
                $receivedAt = \Carbon\Carbon::parse($plainte->received_date);
                
                $plainte->due_date = match($plainte->severity ?? 'medium') {
                    'critical' => $receivedAt->addHours(24),
                    'high' => $receivedAt->addDays(2),
                    'medium' => $receivedAt->addDays(5),
                    'low' => $receivedAt->addDays(10),
                    default => $receivedAt->addDays(5),
                };
            }
        });
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['ref', 'title', 'status', 'severity', 'satisfaction_rating'])
            ->logOnlyDirty();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function respondedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
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
        return $this->due_date && $this->due_date->isPast() && !$this->closed_date;
    }
}
