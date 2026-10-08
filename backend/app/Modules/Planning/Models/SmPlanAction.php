<?php

namespace App\Modules\Planning\Models;

use App\Models\User;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmPlanAction extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $table = 'sm_plan_actions';

    protected $fillable = [
        'sub_activity_id',
        'code',
        'title',
        'responsible_id',
        'responsible_name',
        'internal_actors',
        'external_actors',
        'deliverables',
        'indicators',
        'start_date',
        'deadline',
        'months',
        'status', // a_planifier, en_cours, realise, en_retard, replanifie
        'observations',
        'rescheduled_count',
        'rescheduled_reason',
        'rescheduled_by',
        'rescheduled_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date',
        'months' => 'array',
        'rescheduled_count' => 'integer',
        'rescheduled_at' => 'datetime',
    ];

    public function subActivity(): BelongsTo
    {
        return $this->belongsTo(SmPlanSubActivity::class, 'sub_activity_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function rescheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rescheduled_by');
    }
}

