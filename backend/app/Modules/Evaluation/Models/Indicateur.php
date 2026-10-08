<?php

namespace App\Modules\Evaluation\Models;

use App\Models\Objective;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;

use App\Traits\BelongsToEnterprise;
use App\Traits\HasActions;
use App\Traits\HasAuditFields;
use App\Traits\HasAxes;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Indicateur extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, HasAxes, HasActions, LogsActivity, BelongsToEnterprise;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'process_id',
        'code',
        'name',
        'description',
        'formula',
        'calculation_formula',
        'unit',
        'measurement_unit',
        'type',
        'frequency',
        'target_value',
        'min_threshold',
        'max_threshold',
        'threshold_green',
        'threshold_orange',
        'threshold_red',
        'alert_threshold',
        'current_value',
        'historical_data',
        'responsible_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'min_threshold' => 'decimal:2',
        'max_threshold' => 'decimal:2',
        'alert_threshold' => 'decimal:2',
        'current_value' => 'decimal:2',
        'threshold_green' => 'decimal:2',
        'threshold_orange' => 'decimal:2',
        'threshold_red' => 'decimal:2',
        'historical_data' => 'array',
        'metadata' => 'array',
    ];

    protected static $logAttributes = ['ref', 'code', 'name', 'current_value', 'status'];
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['ref', 'code', 'name', 'current_value', 'status'])
            ->logOnlyDirty();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(Objective::class);
    }

    public function addValue(float $value, ?string $date = null, ?string $note = null): void
    {
        $data = $this->historical_data ?? [];
        $data[] = [
            'date' => $date ?? now()->format('Y-m-d'),
            'value' => $value,
            'note' => $note,
        ];

        $this->historical_data = $data;
        $this->current_value = $value;
        $this->save();
    }

    public function isAboveThreshold(): bool
    {
        if ($this->max_threshold === null || $this->current_value === null) {
            return false;
        }
        return $this->current_value > $this->max_threshold;
    }

    public function isBelowThreshold(): bool
    {
        if ($this->min_threshold === null || $this->current_value === null) {
            return false;
        }
        return $this->current_value < $this->min_threshold;
    }

    public function needsAlert(): bool
    {
        if ($this->alert_threshold === null || $this->current_value === null) {
            return false;
        }
        return abs($this->current_value - $this->target_value) >= $this->alert_threshold;
    }
}
