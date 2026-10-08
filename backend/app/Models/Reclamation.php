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

class Reclamation extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, HasAxes, HasWorkflowStates, HasActions, LogsActivity;

    protected $fillable = [
        'ref',
        'user_id',
        'site_id',
        'process_id',
        'workflow_state_id',
        'client_name',
        'client_email',
        'client_phone',
        'client_company',
        'stakeholder_type',
        'title',
        'description',
        'category',
        'severity',
        'product_ref',
        'batch_number',
        'order_number',
        'incident_date',
        'affected_quantity',
        'analysis',
        'immediate_response',
        'resolution_type',
        'refund_amount',
        'response_date',
        'wants_mail',
        'recommandations',
        'status',
        'assigned_to',
        'responded_by',
        'satisfaction_rating',
        'satisfaction_comment',
        'satisfaction_date',
        'received_date',
        'due_date',
        'closed_date',
        'cost_impact',
        'warranty_claim',
    ];

    protected $casts = [
        'wants_mail' => 'boolean',
        'warranty_claim' => 'boolean',
        'satisfaction_rating' => 'integer',
        'cost_impact' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'affected_quantity' => 'integer',
        'response_date' => 'date',
        'satisfaction_date' => 'date',
        'received_date' => 'date',
        'due_date' => 'date',
        'closed_date' => 'date',
        'incident_date' => 'date',
    ];

    protected static $logAttributes = ['ref', 'title', 'status', 'satisfaction_rating'];
    protected static $logOnlyDirty = true;

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($reclamation) {
            // Auto-generate reference if not provided
            if (empty($reclamation->ref)) {
                $year = now()->year;
                $count = static::whereYear('created_at', $year)->count() + 1;
                $reclamation->ref = sprintf('REC_%d_%04d', $year, $count);
            }
            
            // Calculate response deadline based on severity
            if (empty($reclamation->due_date) && !empty($reclamation->received_date)) {
                $receivedAt = \Carbon\Carbon::parse($reclamation->received_date);
                
                $reclamation->due_date = match($reclamation->severity ?? 'normal') {
                    'critical' => $receivedAt->addHours(24),
                    'major', 'high' => $receivedAt->addDays(3),
                    'minor', 'low' => $receivedAt->addDays(7),
                    default => $receivedAt->addDays(5),
                };
            }
        });
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['ref', 'title', 'status', 'satisfaction_rating'])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
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
