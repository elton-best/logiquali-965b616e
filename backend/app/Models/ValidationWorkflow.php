<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValidationWorkflow extends Model
{
    use HasFactory, BelongsToEnterprise;

    protected $table = 'workflows_validation';

    protected $fillable = [
        'enterprise_id',
        'objet_type',
        'objet_id',
        'status',
        'commentaire',
        'metadata',
        'created_by',
        'verified_by',
        'approved_by',
        'refused_by',
        'verified_at',
        'approved_at',
        'refused_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'refused_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ValidationWorkflowHistory::class, 'validation_workflow_id')->latest();
    }
}
