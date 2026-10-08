<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionActivated;
use App\Events\Subscription\SubscriptionExpiring;
use App\Events\Subscription\SubscriptionExpired;
use App\Notifications\Subscription\SubscriptionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSubscriptionNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
    }

    public function handle(SubscriptionActivated|SubscriptionExpiring|SubscriptionExpired $event): void
    {
        $eventType = match(true) {
            $event instanceof SubscriptionActivated => 'activated',
            $event instanceof SubscriptionExpiring => 'expiring',
            $event instanceof SubscriptionExpired => 'expired',
        };

        // Notification à l'admin entreprise
        $admin = $event->subscription->enterprise?->users()
            ->where('user_type', 'company')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin_entreprise');
            })
            ->first();

        if ($admin) {
            $additionalData = match($eventType) {
                'expiring' => ['days_remaining' => $event->daysRemaining],
                default => [],
            };

            $admin->notify(
                new SubscriptionNotification($event->subscription, $eventType, $additionalData)
            );
        }
    }
}
