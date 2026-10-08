<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'reminder_type',
        'scheduled_for',
        'sent_at',
        'status',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Reminder types
     */
    const TYPE_ONE_MONTH = 'one_month';
    const TYPE_ONE_WEEK = 'one_week';
    const TYPE_ONE_DAY = 'one_day';
    const TYPE_OVERDUE = 'overdue';
    const TYPE_CUSTOM = 'custom';

    /**
     * Statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('scheduled_for', '<=', now());
    }

    /**
     * Mark as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }
}
