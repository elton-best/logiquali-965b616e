<?php

namespace App\Modules\Improvement\Models;

use App\Models\Action;
use App\Models\Site;
use App\Models\User;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class PlanAction extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, LogsActivity;

    protected $fillable = [
        'ref',
        'site_id',
        'title',
        'description',
        'responsible_id',
        'start_date',
        'end_date',
        'status',
        'progress',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
        'metadata' => 'array',
    ];

    protected static $logAttributes = ['ref', 'title', 'status', 'progress'];
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['ref', 'title', 'status', 'progress'])
            ->logOnlyDirty();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    public function updateProgress(): void
    {
        $totalActions = $this->actions()->count();
        if ($totalActions === 0) {
            $this->progress = 0;
            return;
        }

        $completedActions = $this->actions()
            ->whereHas('workflowState', function ($q) {
                $q->where('code', 'completed');
            })
            ->count();

        $this->progress = round(($completedActions / $totalActions) * 100);
        $this->save();
    }
}

