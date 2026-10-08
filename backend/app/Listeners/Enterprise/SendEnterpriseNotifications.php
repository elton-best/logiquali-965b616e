<?php

namespace App\Listeners\Enterprise;

use App\Events\Enterprise\EnterpriseRegistered;
use App\Notifications\Enterprise\EnterpriseRegistrationConfirmation;
use App\Notifications\Enterprise\NewEnterpriseNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendEnterpriseNotifications implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(EnterpriseRegistered $event): void
    {
        // 1. Confirmation à l'admin de l'entreprise
        $event->admin->notify(
            new EnterpriseRegistrationConfirmation($event->enterprise)
        );

        // 2. Alerte aux super-admins
        $superAdmins = User::where('user_type', 'super_admin')->get();
        
        if ($superAdmins->isNotEmpty()) {
            Notification::send(
                $superAdmins,
                new NewEnterpriseNotification($event->enterprise, $event->admin)
            );
        }
    }
}
