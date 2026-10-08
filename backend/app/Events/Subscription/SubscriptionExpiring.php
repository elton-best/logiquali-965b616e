<?php

namespace App\Events\Subscription;

use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiring
{
    use Dispatchable, SerializesModels;

    public Subscription $subscription;
    public int $daysRemaining;

    public function __construct(Subscription $subscription, int $daysRemaining)
    {
        $this->subscription = $subscription;
        $this->daysRemaining = $daysRemaining;
    }
}
