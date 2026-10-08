<?php

namespace App\Modules\Planning\Models;

use App\Models\Process;
use App\Models\Site;
use App\Traits\BelongsToEnterprise;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmPlanActivity extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, BelongsToEnterprise;

    protected $table = 'sm_plan_activities';

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'process_id',
        'year',
        'code',
        'title',
        'description',
        'order',
    ];

    protected $casts = [
        'year' => 'integer',
        'order' => 'integer',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function subActivities(): HasMany
    {
        return $this->hasMany(SmPlanSubActivity::class, 'activity_id')->orderBy('order');
    }
}

