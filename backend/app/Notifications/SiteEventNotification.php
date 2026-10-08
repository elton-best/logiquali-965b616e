<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification générique pour tous les événements du site
 * Utilisée par NotifiesSiteUsers pour notifications ciblées
 * 
 * Structure du canal : database (Laravel notifications table)
 */
class SiteEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $type;
    public array $data;
    public ?int $actorId;

    public function __construct(string $type, array $data, ?int $actorId = null)
    {
        $this->type = $type;
        $this->data = $data;
        $this->actorId = $actorId;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'actor_id' => $this->actorId,
            'actor_name' => $this->data['actor_name'] ?? null,
        ];
    }
}
