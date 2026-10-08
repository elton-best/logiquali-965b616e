<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfaChallenge extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'purpose',
        'code_hash',
        'context',
        'expires_at',
        'consumed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'context' => 'array',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? true;
    }

    public function isConsumed(): bool
    {
        return !empty($this->consumed_at);
    }
}
