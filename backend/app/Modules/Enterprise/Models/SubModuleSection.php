<?php

namespace App\Modules\Enterprise\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubModuleSection extends Model
{
    protected $fillable = [
        'sub_module_id',
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

    public function subModule(): BelongsTo
    {
        return $this->belongsTo(SubModule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getPermissions(): array
    {
        $subModule = $this->subModule;
        $moduleCode = $subModule?->module?->code;
        $subModuleCode = $subModule?->code;
        if (!$moduleCode || !$subModuleCode) {
            return [];
        }
        return [
            "{$moduleCode}.{$subModuleCode}.{$this->code}.read",
            "{$moduleCode}.{$subModuleCode}.{$this->code}.create",
            "{$moduleCode}.{$subModuleCode}.{$this->code}.update",
            "{$moduleCode}.{$subModuleCode}.{$this->code}.delete",
            "{$moduleCode}.{$subModuleCode}.{$this->code}.manage",
        ];
    }
}
