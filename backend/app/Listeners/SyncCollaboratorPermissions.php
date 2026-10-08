<?php

namespace App\Listeners;

use App\Events\SubscriptionUpdated;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncCollaboratorPermissions implements ShouldQueue
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function handle(SubscriptionUpdated $event): void
    {
        $subscription = $event->subscription;
        $action = $event->action;

        // Ne sync que pour upgrade/downgrade
        if (!in_array($action, ['upgrade', 'downgrade'])) {
            return;
        }

        // Récupérer les sous-modules accessibles (source canonique pour permissions granularisées)
        $subModuleIds = $subscription->getAccessibleSubModules()->pluck('id')->toArray();

        // Sync permissions selon l'action
        $syncAction = $action === 'upgrade' ? 'add' : 'remove';
        $this->subscriptionService->syncCollaboratorPermissions($subscription, $subModuleIds, $syncAction);
    }
}
