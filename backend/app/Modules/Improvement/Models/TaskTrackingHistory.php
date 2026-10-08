<?php

namespace App\Modules\Improvement\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTrackingHistory extends Model
{
    protected $table = 'task_tracking_history';

    protected $fillable = [
        'task_tracking_id',
        'status_old',
        'status_new',
        'progress_rate_old',
        'progress_rate_new',
        'notes',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'progress_rate_old' => 'integer',
        'progress_rate_new' => 'integer',
        'notes' => 'string',
        'changed_at' => 'datetime',
    ];

    public function taskTracking(): BelongsTo
    {
        return $this->belongsTo(TaskTracking::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
