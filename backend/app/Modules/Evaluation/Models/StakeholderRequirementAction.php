<?php

namespace App\Modules\Evaluation\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StakeholderRequirementAction extends Model
{
    protected $fillable = [
        'stakeholder_requirement_id',
        'title',
        'description',
        'responsible_id',
        'deadline',
        'status',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(StakeholderRequirement::class, 'stakeholder_requirement_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}
