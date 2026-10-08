<?php

namespace App\Modules\Planning\Models;

use App\Models\Process;
use App\Models\ProcessIndicator;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessObjective extends Model
{
    use HasFactory, HasAuditFields;

    protected $fillable = [
        'process_id',
        'title',
        'strategic_axis',
        'strategic_axes',
        'indicator_name',
        'description',
        'calculation_mode',
        'target_value',
        'target_date',
        'measurement_frequency',
        'indicator_id',
        'indicator_name',
        'status',
        'achievement_percentage',
        'period_realizations',
        'notes',
        'action_plan',
        'planned_actions',
        'responsibles',
        'special_resources',
        'applicable_norms',
    ];

    protected $attributes = [
        'achievement_percentage' => 0,
        'status' => 'not_started',
        'measurement_frequency' => 'monthly',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'target_date' => 'date',
            'achievement_percentage' => 'integer',
            'strategic_axes' => 'array',
            'applicable_norms' => 'array',
            'period_realizations' => 'array',
            'planned_actions' => 'array',
        ];
    }

    // Relations

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function indicator()
    {
        return $this->belongsTo(ProcessIndicator::class, 'indicator_id');
    }

    // Scopes

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeAchieved($query)
    {
        return $query->where('status', 'achieved');
    }

    public function scopeOverdue($query)
    {
        return $query->where('target_date', '<', now())
            ->whereNotIn('status', ['achieved', 'failed']);
    }

    // Helpers

    public function isNotStarted()
    {
        return $this->status === 'not_started';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isAchieved()
    {
        return $this->status === 'achieved';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isOverdue()
    {
        return $this->target_date && $this->target_date->isPast() && !$this->isAchieved();
    }

    public function updateProgress(int $percentage)
    {
        $this->achievement_percentage = max(0, min(100, $percentage));

        if ($this->achievement_percentage >= 100) {
            $this->status = 'achieved';
        } elseif ($this->achievement_percentage > 0) {
            $this->status = 'in_progress';
        }

        $this->save();
    }
}
