<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AuditFinding extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, LogsActivity;

    protected $fillable = [
        'ref',
        'audit_id',
        'checklist_item_id',
        'type',
        'qhse_axes',
        'process_id',
        'location',
        'clause_iso',
        'title',
        'description',
        'evidence',
        'requirement',
        'attachments',
        'severity',
        'priority',
        'detected_by',
        'concerned_user_id',
        'detected_at',
        'root_cause',
        'immediate_action',
        'non_conformity_id',
        'nc_created',
        'auto_create_nc',
        'status',
        'resolution_deadline',
        'resolved_at',
    ];

    protected $attributes = [
        'nc_created' => false,
        'status' => 'open',
        'priority' => 2,
        'auto_create_nc' => true,
    ];

    protected $casts = [
        'qhse_axes' => 'array',
        'attachments' => 'array',
        'priority' => 'integer',
        'detected_at' => 'datetime',
        'nc_created' => 'boolean',
        'auto_create_nc' => 'boolean',
        'resolution_deadline' => 'date',
        'resolved_at' => 'date',
    ];

    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($finding) {
            if (empty($finding->ref)) {
                $finding->ref = self::generateReference($finding);
            }
            
            // Auto-définir deadline selon type
            if (empty($finding->resolution_deadline)) {
                $finding->resolution_deadline = match($finding->type) {
                    'nc_major' => now()->addDays(15),
                    'nc_minor' => now()->addDays(30),
                    'observation' => now()->addDays(60),
                    default => now()->addDays(30)
                };
            }
        });
        
        // Création auto NC si écart majeur/mineur
        static::created(function ($finding) {
            if ($finding->auto_create_nc && in_array($finding->type, ['nc_major', 'nc_minor']) && !$finding->nc_created) {
                $finding->createNonConformity();
            }
        });
    }

    /**
     * Format de référence pour le trait HasReference
     */
    public function getReferencePrefix(): string
    {
        // Format: CONST-{auditId}-{seq}
        $auditId = $this->audit_id ?? 'XXX';
        return "CONST-{$auditId}";
    }

    /**
     * Crée automatiquement une NC depuis le constat
     */
    public function createNonConformity(): ?NonConformity
    {
        if ($this->nc_created || $this->non_conformity_id) {
            return null;
        }

        // Mapper priority numérique vers enum
        $priorityMap = [
            1 => 'critical',
            2 => 'high',
            3 => 'medium',
            4 => 'low',
            5 => 'low',
        ];
        
        // Mapper severity vers enum NC
        $severityMap = [
            'low' => 'minor',
            'medium' => 'minor',
            'high' => 'major',
            'critical' => 'critical',
        ];
        
        $ncPriority = $priorityMap[$this->priority] ?? 'medium';
        $ncSeverity = $severityMap[$this->severity] ?? 'minor';

        $nc = NonConformity::create([
            'site_id' => $this->audit->site_id,
            'process_id' => $this->process_id,
            'audit_id' => $this->audit_id,
            'title' => $this->title,
            'type' => 'normative', // Type de NC (normative car vient d'un audit ISO)
            'source' => 'audit',
            'severity' => $ncSeverity,
            'priority' => $ncPriority,
            'description' => $this->description,
            'detected_by' => $this->detected_by,
            'detected_at' => $this->detected_at,
            'root_cause_analysis' => $this->root_cause,
            'deadline' => $this->resolution_deadline,
            'status' => 'open',
        ]);

        $this->update([
            'non_conformity_id' => $nc->id,
            'nc_created' => true,
            'status' => 'nc_created'
        ]);

        activity('audit_finding')
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['nc_id' => $nc->id])
            ->log('NC créée automatiquement depuis constat d\'audit');

        return $nc;
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ref', 'type', 'severity', 'status', 'nc_created'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(AuditChecklistItem::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function detectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'detected_by');
    }

    public function concernedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'concerned_user_id');
    }

    public function nonConformity(): BelongsTo
    {
        return $this->belongsTo(NonConformity::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'action_planned', 'nc_created']);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'nc_major' => 'NC Majeure',
            'nc_minor' => 'NC Mineure',
            'observation' => 'Observation',
            'opportunity' => 'Opportunité',
            'good_practice' => 'Bonne Pratique',
            default => $this->type
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'Critique',
            'high' => 'Élevée',
            'medium' => 'Moyenne',
            'low' => 'Faible',
            default => $this->severity
        };
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray'
        };
    }
}
