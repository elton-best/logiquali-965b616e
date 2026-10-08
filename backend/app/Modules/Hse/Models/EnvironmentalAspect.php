<?php

namespace App\Modules\Hse\Models;

use App\Models\Enterprise;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnvironmentalAspect extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'environmental_aspects';

    protected $fillable = [
        'enterprise_id',
        'process_id',
        'site_id',
        'sub_process',
        'mode', // 'N' (Normal), 'A' (Accidentel / Dégradé)
        'aspect_number',
        'activity',
        'aspect',
        'impact',
        'risk',
        'existing_controls',
        'gravity',
        'frequency',
        'sensitivity',
        'mastery',
        'criticality_score',
        'significance_threshold',
        'is_significant',
        'condition_type',
        'frequency_score',
        'severity_score',
        'regulatory_score',
        'significance_score',
        'control_measures',
        'additional_actions',
        'responsible_id',
        'responsible_name',
        'deadline',
        'effectiveness_criterion',
        'efficiency_criterion',
        'observations',
        'action_ids',
    ];

    protected $casts = [
        'gravity' => 'integer',
        'frequency' => 'integer',
        'sensitivity' => 'integer',
        'mastery' => 'integer',
        'criticality_score' => 'integer',
        'significance_threshold' => 'integer',
        'frequency_score' => 'integer',
        'severity_score' => 'integer',
        'regulatory_score' => 'integer',
        'significance_score' => 'integer',
        'is_significant' => 'boolean',
        'deadline' => 'date',
        'action_ids' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function (EnvironmentalAspect $aspect) {
            // ISO 14001 CBI BENIN Standard: Cr = Gravity * Frequency * Sensitivity * Mastery (Seuil: 343)
            if ($aspect->gravity !== null || $aspect->frequency !== null || $aspect->sensitivity !== null || $aspect->mastery !== null) {
                $g = (int) ($aspect->gravity ?? 1);
                $f = (int) ($aspect->frequency ?? 1);
                $s = (int) ($aspect->sensitivity ?? 1);
                $m = (int) ($aspect->mastery ?? 1);
                $aspect->criticality_score = $g * $f * $s * $m;
                $threshold = (int) ($aspect->significance_threshold ?? 343);
                $aspect->is_significant = $aspect->criticality_score >= $threshold;
            } elseif ($aspect->frequency_score !== null || $aspect->severity_score !== null) {
                // Legacy fallback
                $freq = $aspect->frequency_score ?? 0;
                $sev = $aspect->severity_score ?? 0;
                $reg = $aspect->regulatory_score ?? 0;
                $aspect->significance_score = $freq + $sev + $reg;
                $aspect->is_significant = $aspect->significance_score >= 9;
            }
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

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}

