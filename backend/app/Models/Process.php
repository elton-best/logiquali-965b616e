<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Process extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, Searchable, BelongsToEnterprise;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'title',
        'type',
        'category',
        'code',
        'abbreviation',
        'pilot_id',
        'copilot_id',
        'process_owner_id',
        'process_type',
        'purpose',
        'finalite',
        'acteurs',
        'ressources',
        'methodes',
        'interfaces',
        'turtle_diagram',
        'interactions',
        'aspect_qualite',
        'aspect_environnement',
        'aspect_sante_securite',
        'normes_iso',
        'status',
        'is_validated',
        'validated_by',
        'validated_at',
        'last_review_at',
        'next_review_at',
        'review_frequency_months',
        'version',
        'parent_process_id',
        'level',
        'order',
        'sequence_order',
    ];

    protected function casts(): array
    {
        return [
            'is_validated' => 'boolean',
            'validated_at' => 'datetime',
            'acteurs' => 'array',
            'ressources' => 'array',
            'methodes' => 'array',
            'interfaces' => 'array',
            'turtle_diagram' => 'array',
            'interactions' => 'array',
            'normes_iso' => 'array',
            'aspect_qualite' => 'boolean',
            'aspect_environnement' => 'boolean',
            'aspect_sante_securite' => 'boolean',
            'last_review_at' => 'datetime',
            'next_review_at' => 'datetime',
            'sequence_order' => 'integer',
        ];
    }

    protected static function booted()
    {
        static::creating(function (Process $process) {
            if (empty($process->code)) {
                $month = strtoupper(now()->format('M'));
                $year = now()->format('Y');
                $prefix = "PRC_{$month}_{$year}_";
                $last = static::where('code', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
                $seq = 1;
                if ($last && preg_match('/_(\\d+)$/', $last->code, $matches)) {
                    $seq = (int) $matches[1] + 1;
                }
                $process->code = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function pilot()
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }

    public function copilot()
    {
        return $this->belongsTo(User::class, 'copilot_id');
    }

    public function copilots()
    {
        return $this->belongsToMany(User::class, 'team_members', 'process_id', 'user_id')
            ->wherePivot('role', 'copilot')
            ->wherePivot('is_active', true)
            ->whereNull('team_members.deleted_at')
            ->withPivot(['role', 'is_active']);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'process_owner_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function risks()
    {
        return $this->hasMany(Risk::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function objectives()
    {
        return $this->hasMany(Objective::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function resources()
    {
        return $this->hasMany(ProcessResource::class);
    }

    public function responsibilities()
    {
        return $this->hasMany(Responsibility::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function dashboards()
    {
        return $this->hasMany(Dashboard::class);
    }

    public function nonConformities()
    {
        return $this->hasMany(NonConformity::class);
    }

    // === NOUVELLES RELATIONS QHSE ===

    public function parentProcess()
    {
        return $this->belongsTo(Process::class, 'parent_process_id');
    }

    public function childProcesses()
    {
        return $this->hasMany(Process::class, 'parent_process_id');
    }

    public function indicators()
    {
        return $this->hasMany(ProcessIndicator::class);
    }

    public function risksOpportunities()
    {
        return $this->hasMany(ProcessRiskOpportunity::class);
    }

    public function isoCoverages()
    {
        return $this->hasMany(ProcessIsoCoverage::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProcessReview::class);
    }

    public function operationalControls()
    {
        return $this->hasMany(OperationalControl::class);
    }

    public function sequences()
    {
        return $this->hasMany(ProcessSequence::class)->orderBy('sequence_order');
    }

    public function versions()
    {
        return $this->hasMany(ProcessVersion::class)->orderBy('version_date', 'desc');
    }

    public function currentVersion()
    {
        return $this->hasOne(ProcessVersion::class)->where('is_current', true);
    }

    public function processObjectives()
    {
        return $this->hasMany(ProcessObjective::class);
    }

    public function audits()
    {
        return $this->belongsToMany(Audit::class, 'audit_process')
            ->withPivot('covered', 'conformity_rate')
            ->withTimestamps();
    }

    public function documentsViaProcess()
    {
        return $this->belongsToMany(Document::class, 'document_process')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    // === SCOPES ===

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
    }

    public function scopeReviewDue($query)
    {
        return $query->whereNotNull('next_review_at')
            ->where('next_review_at', '<=', now());
    }

    public function scopeByAspect($query, $aspect)
    {
        return $query->where("aspect_{$aspect}", true);
    }

    public function scopeMacro($query)
    {
        return $query->where('level', 1);
    }

    // === HELPERS ===

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isReviewDue()
    {
        return $this->next_review_at && $this->next_review_at->isPast();
    }

    public function isSMI()
    {
        return $this->aspect_environnement || $this->aspect_sante_securite;
    }

    public function getApplicableNormes()
    {
        return $this->normes_iso ?? [];
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
            'code' => $this->code,
            'abbreviation' => $this->abbreviation,
            'type' => $this->type,
            'category' => $this->category,
            'purpose' => $this->purpose,
            'finalite' => $this->finalite,
            'pilot' => $this->pilot?->name,
            'copilot' => $this->copilot?->name,
        ];
    }
}
