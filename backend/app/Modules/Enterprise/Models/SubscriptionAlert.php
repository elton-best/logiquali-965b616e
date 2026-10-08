<?php

namespace App\Modules\Enterprise\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionAlert extends Model
{
    protected $fillable = [
        'subscription_id',
        'alert_type',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(EnterpriseSubscription::class, 'subscription_id');
    }
}
