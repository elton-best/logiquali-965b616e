<?php

namespace App\Modules\Planning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationalProjectActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(OperationalProject::class, 'project_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OperationalProjectTask::class, 'activity_id')->orderBy('position');
    }
}

