<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class UserNotificationChannel
{
    /**
     * Legacy channel disabled: all in-app notifications now go through
     * Laravel database notifications via the standard "database" channel.
     */
    public function send($notifiable, Notification $notification): void
    {
        // No-op by design.
    }
}
