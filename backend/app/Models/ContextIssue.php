<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContextIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'context_id',
        'category_id',
        'description',
        'sentiment',
        'is_major',
        'impact_score',
        'action_plan_summary',
    ];

    protected $casts = [
        'is_major' => 'boolean',
        'impact_score' => 'integer',
    ];

    public function context()
    {
        return $this->belongsTo(OrganizationContext::class, 'context_id');
    }

    public function category()
    {
        return $this->belongsTo(AnalysisCategory::class, 'category_id');
    }
}
