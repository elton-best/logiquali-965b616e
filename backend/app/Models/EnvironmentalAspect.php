<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnvironmentalAspect extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'process_id',
        'site_id',
        'aspect_number',
        'activity',
        'aspect',
        'impact',
        'condition_type',
        'frequency_score',
        'severity_score',
        'regulatory_score',
        'significance_score',
        'is_significant',
        'control_measures',
        'action_ids',
    ];

    protected $casts = [
        'frequency_score' => 'integer',
        'severity_score' => 'integer',
        'regulatory_score' => 'integer',
        'significance_score' => 'integer',
        'is_significant' => 'boolean',
        'action_ids' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function (EnvironmentalAspect $aspect) {
            $freq = $aspect->frequency_score ?? 0;
            $sev = $aspect->severity_score ?? 0;
            $reg = $aspect->regulatory_score ?? 0;
            $aspect->significance_score = $freq + $sev + $reg;
            $aspect->is_significant = $aspect->significance_score >= 9;
        });
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
