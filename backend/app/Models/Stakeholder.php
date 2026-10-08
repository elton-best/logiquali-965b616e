<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stakeholder extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'name',
        'type',
        'category',
        'relevance_degree',
        'relevance_level',
        'relevance_justification',
        'needs_expectations',
        'needs',
        'requirements',
        'actions',
        'contact_person',
        'contact_email',
        'responsible_id',
        'responsible_user_id',
        'compliance_requirement_ids',
        'applicable_norms',
    ];

    protected $casts = [
        'needs' => 'array',
        'requirements' => 'array',
        'actions' => 'array',
        'compliance_requirement_ids' => 'array',
        'applicable_norms' => 'array',
    ];

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
    
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    // Nouvelle structure métier
    public function stakeholderNeeds(): HasMany
    {
        return $this->hasMany(StakeholderNeed::class);
    }
}
