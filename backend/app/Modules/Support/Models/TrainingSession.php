<?php

namespace App\Modules\Support\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingSession extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'training_plan_id',
        'title',
        'description',
        'training_type',
        'target_user_ids',
        'target_job_roles',
        'planned_date',
        'frequency',
        'actual_date',
        'trainer',
        'location',
        'duration_hours',
        'status',
        'cancellation_reason',
        'alert_before_months',
        'cost',
    ];

    protected $casts = [
        'target_user_ids' => 'array',
        'target_job_roles' => 'array',
        'planned_date' => 'date',
        'actual_date' => 'date',
        'duration_hours' => 'decimal:2',
        'alert_before_months' => 'integer',
        'cost' => 'decimal:2',
    ];

    public function plan()
    {
        return $this->belongsTo(TrainingPlan::class, 'training_plan_id');
    }

    public function evaluations()
    {
        return $this->hasMany(TrainingEvaluation::class);
    }
}
