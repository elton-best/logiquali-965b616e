<?php

namespace App\Modules\Support\Models;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipementCodeAlias extends Model
{
    use HasFactory, BelongsToEnterprise;

    protected $fillable = [
        'equipement_id',
        'enterprise_id',
        'code_alias',
        'change_reason',
        'changed_by',
        'effective_from',
        'effective_to',
        'is_primary_at_time',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'is_primary_at_time' => 'boolean',
    ];

    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }
}
