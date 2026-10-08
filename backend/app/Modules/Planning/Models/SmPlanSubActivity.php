<?php

namespace App\Modules\Planning\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmPlanSubActivity extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'sm_plan_sub_activities';

    protected $fillable = [
        'activity_id',
        'code',
        'title',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(SmPlanActivity::class, 'activity_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(SmPlanAction::class, 'sub_activity_id')->orderBy('id');
    }
}

