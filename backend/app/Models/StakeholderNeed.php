<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StakeholderNeed extends Model
{
    protected $fillable = [
        'stakeholder_id',
        'description',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    public function stakeholder(): BelongsTo
    {
        return $this->belongsTo(Stakeholder::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(StakeholderRequirement::class);
    }
}
