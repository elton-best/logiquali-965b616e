<?php

namespace App\Listeners\Enterprise;

use App\Events\Enterprise\EnterpriseApproved;
use App\Events\Enterprise\EnterpriseRejected;
use App\Notifications\Enterprise\EnterpriseApprovedNotification;
use App\Notifications\Enterprise\EnterpriseRejectedNotification;

class SendEnterpriseDecisionNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(EnterpriseApproved|EnterpriseRejected $event): void
    {
        // Récupérer l'admin de l'entreprise
        $admin = $event->enterprise->users()
            ->where('user_type', 'company')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin_entreprise');
            })
            ->first();

        if (!$admin) {
            return;
        }

        // Envoyer la notification appropriée
        $notification = match(true) {
            $event instanceof EnterpriseApproved => new EnterpriseApprovedNotification($event->enterprise),
            $event instanceof EnterpriseRejected => new EnterpriseRejectedNotification(
                $event->enterprise,
                $event->reason ?? 'Aucune raison spécifiée'
            ),
        };

        $admin->notify($notification);
    }
}
