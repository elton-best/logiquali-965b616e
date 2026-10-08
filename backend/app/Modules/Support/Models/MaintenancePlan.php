<?php

namespace App\Modules\Support\Models;

use App\Models\Enterprise;
use App\Models\User;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenancePlan extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'equipment_id',
        'maintenance_type',
        'frequency',
        'frequency_value',
        'last_maintenance_date',
        'next_maintenance_date',
        'scheduled_date',
        'alert_days_before',
        'alert_sent',
        'alert_sent_at',
        'responsible_user_id',
        'instructions',
        'checklist',
        'status',
    ];

    protected $casts = [
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'scheduled_date' => 'date',
        'alert_sent' => 'boolean',
        'alert_sent_at' => 'datetime',
        'checklist' => 'array',
    ];

    protected static function booted(): void
    {
        // Keep scheduled_date in sync with next_maintenance_date
        static::saving(function (self $plan) {
            if ($plan->isDirty('next_maintenance_date') || empty($plan->scheduled_date)) {
                $plan->scheduled_date = $plan->next_maintenance_date;
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
        return $this->hasMany(MaintenanceRecord::class);
    }
}
