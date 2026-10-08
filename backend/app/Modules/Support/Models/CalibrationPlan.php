<?php

namespace App\Modules\Support\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalibrationPlan extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'equipment_id',
        'frequency',
        'last_calibration_date',
        'next_calibration_date',
        'scheduled_date',
        'calibration_certificate_path',
        'alert_days_before',
        'alert_sent',
        'service_provider',
        'service_provider_accreditation',
        'responsible_user_id',
        'status',
    ];

    protected $casts = [
        'last_calibration_date' => 'date',
        'next_calibration_date' => 'date',
        'scheduled_date' => 'date',
        'alert_sent' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Keep scheduled_date in sync with next_calibration_date
        static::saving(function (self $plan) {
            if ($plan->isDirty('next_calibration_date') || empty($plan->scheduled_date)) {
                $plan->scheduled_date = $plan->next_calibration_date;
            }
        });
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function records()
    {
        return $this->hasMany(CalibrationRecord::class);
    }
}
