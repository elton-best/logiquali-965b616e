<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessIndicator extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'process_id',
        'code',
        'name',
        'description',
        'type',
        'category',
        'unit',
        'measurement_frequency',
        'target_value',
        'min_threshold',
        'max_threshold',
        'alert_threshold',
        'formula',
        'responsible_user_id',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'target_value' => 'decimal:2',
        'min_threshold' => 'decimal:2',
        'max_threshold' => 'decimal:2',
        'alert_threshold' => 'decimal:2',
    ];

    // Relations
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function values()
    {
        return $this->hasMany(ProcessIndicatorValue::class, 'indicator_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeCritical($query)
    {
        return $query->where('status', 'critical');
    }

    // Helpers
    public function getLatestValue()
    {
        return $this->values()->latest('measurement_date')->first();
    }

    public function calculateStatus($value)
    {
        if ($this->max_threshold && $value > $this->max_threshold) {
            return 'critical';
        }
        
        if ($this->min_threshold && $value < $this->min_threshold) {
            return 'critical';
        }
        
        if ($this->alert_threshold) {
            if ($this->max_threshold && $value > $this->alert_threshold) {
                return 'warning';
            }
            if ($this->min_threshold && $value < $this->alert_threshold) {
                return 'warning';
            }
        }
        
        return 'ok';
    }

    public function updateStatus()
    {
        $latestValue = $this->getLatestValue();
        if ($latestValue) {
            $this->status = $this->calculateStatus($latestValue->value);
            $this->save();
        }
    }
}
