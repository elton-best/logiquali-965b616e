<?php

namespace App\Modules\Hse\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceObligationAspect extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'site_id',
        'name',
        'description',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function norms(): BelongsToMany
    {
        return $this->belongsToMany(
            Norm::class,
            'compliance_obligation_aspect_norm',
            'aspect_id',
            'norm_id'
        )->withTimestamps();
    }

    public function texts(): HasMany
    {
        return $this->hasMany(ComplianceObligationText::class, 'aspect_id');
    }
}

