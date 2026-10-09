<?php

namespace App\Modules\Enterprise\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Leadership\Models\Norm;

class SubModule extends Model
{
    protected $fillable = [
        'module_id',
        'name',
        'code',
        'description',
        'icon',
        'route',
        'order',
        'is_active',
        'is_common',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_common' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function norms(): BelongsToMany
    {
        return $this->belongsToMany(Norm::class, 'norm_sub_module');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SubModuleSection::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getPermissions(): array
    {
        $moduleCode = $this->module?->code;
        if (!$moduleCode) {
            return [];
        }
        return [
            "{$moduleCode}.{$this->code}.read",
            "{$moduleCode}.{$this->code}.create",
            "{$moduleCode}.{$this->code}.update",
            "{$moduleCode}.{$this->code}.delete",
            "{$moduleCode}.{$this->code}.manage",
        ];
    }
}
