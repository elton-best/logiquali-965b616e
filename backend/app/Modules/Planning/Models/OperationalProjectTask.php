<?php

namespace App\Modules\Planning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationalProjectTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'activity_id',
        'enterprise_id',
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'progress',
        'position',
        'responsible_user_id',
        'assigned_to',
        'assigned_user_ids',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'progress' => 'integer',
            'position' => 'integer',
            'assigned_user_ids' => 'array',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(OperationalProjectActivity::class, 'activity_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

