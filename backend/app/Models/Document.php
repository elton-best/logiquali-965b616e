<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Document extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, Searchable;

    protected $fillable = [
        'ref',
        'site_id',
        'enterprise_id',
        'category_id',
        'workflow_id',
        'process_id',
        'processus',
        'source_type',
        'source_module',
        'source_submodule',
        'source_section',
        'template_id',
        'nomenclature_template_id',
        'nomenclature_template_version',
        'document_type_configuration_id',
        'metadata',
        'generation_context',
        'code',
        'code_status',
        'document_number',
        'title',
        'description',
        'keywords',
        'tags',
        'language',
        'version',
        'collaboration_version',
        'file_path',
        'template_path',
        'status',
        'etat',
        'author_id',
        'approver_id',
        'approved_at',
        'verified_at',
        'verified_by',
        'published_at',
        'effective_date',
        'review_due_date',
        'periodicite_revision',
        'date_revision',
        'prochaine_revision',
        'is_active',
        'retention_period_years',
        'archived_at',
        'archived_by',
        'archive_reason',
        'is_confidential',
        'confidentiality_level',
        'module_type',
        'module_id',
        'needs_verification',
        'verifier_id',
        'rejection_reason',
        'workflow_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_confidential' => 'boolean',
            'needs_verification' => 'boolean',
            'tags' => 'array',
            'metadata' => 'array',
            'approved_at' => 'datetime',
            'verified_at' => 'datetime',
            'published_at' => 'datetime',
            'effective_date' => 'datetime',
            'review_due_date' => 'datetime',
            'date_revision' => 'date',
            'prochaine_revision' => 'date',
            'archived_at' => 'datetime',
            'collaboration_version' => 'integer',
            'retention_period_years' => 'integer',
            'periodicite_revision' => 'integer',
            'nomenclature_template_version' => 'integer',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function processes()
    {
        return $this->belongsToMany(Process::class, 'document_process')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function typeConfiguration()
    {
        return $this->belongsTo(DocumentTypeConfiguration::class, 'document_type_configuration_id');
    }

    public function module()
    {
        return $this->morphTo();
    }

    public function archiver()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function workflow()
    {
        return $this->belongsTo(DocumentWorkflow::class, 'workflow_id');
    }

    public function nomenclatureTemplate()
    {
        return $this->belongsTo(NomenclatureTemplate::class, 'nomenclature_template_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function currentVersion()
    {
        return $this->hasOne(DocumentVersion::class)->where('is_current', true);
    }

    public function workflowEvents()
    {
        return $this->hasMany(DocumentWorkflowEvent::class)->orderBy('chain_index');
    }

    public function isPublished(): bool
    {
        return !is_null($this->published_at) && $this->status === 'approved';
    }

    public function isArchived(): bool
    {
        return !is_null($this->archived_at) || $this->status === 'obsolete';
    }

    public function isReviewOverdue(): bool
    {
        return !is_null($this->review_due_date) && $this->review_due_date->isPast();
    }

    /**
     * Scopes pour le workflow
     */
    public function scopePendingVerification(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('workflow_status', 'pending_verification');
    }

    public function scopePendingApproval(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('workflow_status', 'pending_approval');
    }

    public function scopeVerified(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'approved')
            ->whereNotNull('approved_at');
    }

    public function scopeActiveCode(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('code_status', 'active');
    }

    public function scopeReleasedCode(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('code_status', 'released');
    }

    /**
     * Vérifie si le document est en attente de vérification
     */
    public function isPendingVerification(): bool
    {
        return $this->workflow_status === 'pending_verification';
    }

    /**
     * Vérifie si le document est en attente d'approbation
     */
    public function isPendingApproval(): bool
    {
        return $this->workflow_status === 'pending_approval';
    }

    /**
     * Vérifie si le document a été vérifié
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Vérifie si le code est actif
     */
    public function hasActiveCode(): bool
    {
        return $this->code_status === 'active';
    }

    /**
     * Vérifie si le code a été libéré
     */
    public function hasReleasedCode(): bool
    {
        return $this->code_status === 'released';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'ref' => $this->ref,
            'title' => $this->title,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
            'site_id' => $this->site_id,
            'process_id' => $this->process_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->timestamp,
        ];
    }
}
