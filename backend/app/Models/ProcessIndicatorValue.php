<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessIndicatorValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicator_id',
        'measurement_date',
        'period',
        'value',
        'comment',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'measurement_date' => 'date',
        'value' => 'decimal:2',
    ];

    // Relations
    public function indicator()
    {
        return $this->belongsTo(ProcessIndicator::class, 'indicator_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Events
    protected static function booted()
    {
        static::created(function ($value) {
            // Auto-calculate status based on indicator thresholds
            $value->status = $value->indicator->calculateStatus($value->value);
            $value->save();
            
            // Update indicator status
            $value->indicator->updateStatus();
        });
    }
}
