<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuerpDanger extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'duerp_id',
        'work_unit_id',
        'risk_family_id',
        'risk_type_id',
        'organizational_unit',
        'process_id',
        'activity',
        'danger_type',
        'danger_description',
        'exposed_workers',
        'probability_score',
        'severity_score',
        'criticality_score',
        'criticality_level',
        'existing_measures',
        'actions',
        'applicable_norms',
    ];

    protected $casts = [
        'exposed_workers' => 'array',
        'actions' => 'array',
        'applicable_norms' => 'array',
        'probability_score' => 'integer',
        'severity_score' => 'integer',
        'criticality_score' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function (DuerpDanger $danger) {
            $prob = $danger->probability_score ?? 0;
            $sev = $danger->severity_score ?? 0;
            $danger->criticality_score = $prob * $sev;
            $danger->criticality_level = match (true) {
                $danger->criticality_score <= 4 => 'acceptable',
                $danger->criticality_score <= 8 => 'tolerable',
                default => 'unacceptable',
            };
        });
    }

    public function duerp()
    {
        return $this->belongsTo(Duerp::class, 'duerp_id');
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function workUnit() { return $this->belongsTo(DuerpWorkUnit::class, 'work_unit_id'); }
    public function riskFamily() { return $this->belongsTo(DuerpRiskFamily::class, 'risk_family_id'); }
    public function riskType() { return $this->belongsTo(DuerpRiskType::class, 'risk_type_id'); }
    public function preventionActions() { return $this->hasMany(DuerpPreventionAction::class, 'duerp_danger_id'); }
}
