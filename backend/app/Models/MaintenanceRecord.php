<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_plan_id',
        'performed_date',
        'performed_by_user_id',
        'work_description',
        'parts_replaced',
        'observations',
        'proof_path',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'next_maintenance_date',
    ];

    protected $casts = [
        'performed_date' => 'date',
        'parts_replaced' => 'array',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'next_maintenance_date' => 'date',
    ];

    public function maintenancePlan()
    {
        return $this->belongsTo(MaintenancePlan::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
