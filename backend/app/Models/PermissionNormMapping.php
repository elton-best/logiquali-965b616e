<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionNormMapping extends Model
{
    protected $fillable = [
        'permission_id',
        'norm_id',
        'module_id',
        'sub_module_id',
        'section_id',
        'is_shared',
        'description',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function norm(): BelongsTo
    {
        return $this->belongsTo(Norm::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function subModule(): BelongsTo
    {
        return $this->belongsTo(SubModule::class, 'sub_module_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SubModuleSection::class, 'section_id');
    }

    // Scopes
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    public function scopeSpecificToNorm($query)
    {
        return $query->where('is_shared', false);
    }

    public function scopeForNorm($query, $normId)
    {
        return $query->where('norm_id', $normId);
    }

    public function scopeForModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }
}
