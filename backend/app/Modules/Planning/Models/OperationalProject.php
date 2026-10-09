<?php

namespace App\Modules\Planning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationalProject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'code',
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'process_id',
        'project_manager_id',
        'team_user_ids',
        'release_notes',
        'release_evidences',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'progress' => 'integer',
            'team_user_ids' => 'array',
            'release_evidences' => 'array',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Process::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function getTeamMembersAttribute()
    {
        if (empty($this->team_user_ids)) {
            return collect();
        }
        return User::whereIn('id', $this->team_user_ids)->get();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OperationalProjectActivity::class, 'project_id')->orderBy('position');
    }
}
