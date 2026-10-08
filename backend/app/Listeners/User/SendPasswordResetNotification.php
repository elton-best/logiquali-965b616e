<?php

namespace App\Listeners\User;

use App\Events\User\PasswordResetRequested;
use App\Notifications\User\PasswordResetNotification;
class SendPasswordResetNotification
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
    public function handle(PasswordResetRequested $event): void
    {
        $event->user->notify(
            new PasswordResetNotification($event->token, $event->resetUrl)
        );
    }
}
