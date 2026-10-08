<?php

namespace App\Events;

use App\Models\EnterpriseSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EnterpriseSubscription $subscription,
        public string $action // 'upgrade', 'downgrade', 'renewed', 'cancelled'
    ) {}
}
