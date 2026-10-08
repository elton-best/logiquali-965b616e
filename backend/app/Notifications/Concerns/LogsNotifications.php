<?php

namespace App\Notifications\Concerns;

trait LogsNotifications
{
    /**
     * Log l'envoi d'une notification
     */
    protected function logNotificationSent(object $notifiable, string $channel): void
    {
        if (app()->bound('log')) {
            logger()->info('Notification sent', [
                'notification_type' => class_basename($this),
                'channel' => $channel,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
                'notifiable_email' => $notifiable->email ?? null,
                'data' => $this->toArray($notifiable),
            ]);
        }
    }

    /**
     * Log l'échec d'envoi d'une notification
     */
    protected function logNotificationFailed(object $notifiable, string $channel, \Throwable $exception): void
    {
        if (app()->bound('log')) {
            logger()->error('Notification failed', [
                'notification_type' => class_basename($this),
                'channel' => $channel,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
                'notifiable_email' => $notifiable->email ?? null,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * Log le déclenchement d'un event
     */
    protected function logEventDispatched(string $eventName, array $context = []): void
    {
        if (app()->bound('log')) {
            logger()->info('Event dispatched', array_merge([
                'event' => $eventName,
            ], $context));
        }
    }
}
