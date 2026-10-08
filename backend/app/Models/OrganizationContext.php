<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationContext extends Model
{
    //

    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'title',
        'is_active',
        'year',
        'internal_strengths',
        'internal_weaknesses',
        'external_opportunities',
        'external_threats',
        'political_factors',
        'economic_factors',
        'social_factors',
        'technological_factors',
        'environmental_factors',
        'legal_factors',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function issues()
    {
        return $this->hasMany(ContextIssue::class, 'context_id');
    }
}
