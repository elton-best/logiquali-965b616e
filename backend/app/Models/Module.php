<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'icon',
        'iso_point',
        'order',
        'is_active',
        'is_common',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_common' => 'boolean',
        'order' => 'integer',
    ];

    public function subModules(): HasMany
    {
        return $this->hasMany(SubModule::class);
    }

    public function norms(): BelongsToMany
    {
        return $this->belongsToMany(Norm::class, 'norm_module');
    }

    public function permissionNormMappings()
    {
        return $this->hasMany(PermissionNormMapping::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByIsoPoint($query, int $isoPoint)
    {
        return $query->where('iso_point', $isoPoint);
    }

    public function getPermissions(): array
    {
        return [
            "{$this->code}.read",
            "{$this->code}.create",
            "{$this->code}.update",
            "{$this->code}.delete",
            "{$this->code}.manage",
        ];
    }
}
