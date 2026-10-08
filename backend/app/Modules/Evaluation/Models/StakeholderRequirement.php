<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StakeholderRequirement extends Model
{
    protected $fillable = [
        'stakeholder_need_id',
        'description',
        'type',
        'reference',
    ];

    public function need(): BelongsTo
    {
        return $this->belongsTo(StakeholderNeed::class, 'stakeholder_need_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(StakeholderRequirementAction::class);
    }
}
