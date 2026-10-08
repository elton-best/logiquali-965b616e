<?php

namespace App\Listeners\User;

use App\Events\User\UserCreated;
use App\Notifications\User\WelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeNotification implements ShouldQueue
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
    public function handle(UserCreated $event): void
    {
        $event->user->notify(
            new WelcomeNotification(
                $event->userType,
                $event->accessMode,
                $event->temporaryPassword,
                $event->setPasswordUrl
            )
        );
    }
}
