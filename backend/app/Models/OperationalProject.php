<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'progress',
        'project_manager_id',
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

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OperationalProjectActivity::class, 'project_id')->orderBy('position');
    }

    public function processes(): BelongsToMany
    {
        return $this->belongsToMany(Process::class, 'operational_project_process');
    }
}
