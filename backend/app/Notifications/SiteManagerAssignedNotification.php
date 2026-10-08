<?php

namespace App\Notifications;

use App\Models\Site;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SiteManagerAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Site $site,
        private ?User $assignedBy = null,
        private string $eventType = 'assigned'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->eventType === 'changed'
            ? 'Changement de responsable de site'
            : 'Attribution responsable de site';

        $intro = $this->eventType === 'changed'
            ? 'Vous avez été défini(e) comme nouveau responsable de site.'
            : 'Vous avez été défini(e) comme responsable de site.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . ($notifiable->name ?? ''))
            ->line($intro)
            ->line('**Site :** ' . $this->site->name)
            ->line('**Ville :** ' . ($this->site->city ?: 'Non renseignée'))
            ->line('Action effectuée par : ' . ($this->assignedBy?->name ?? 'un administrateur'))
            ->action('Accéder à la plateforme', url('/auth/login'))
            ->salutation("Cordialement, L'équipe " . env("APP_NAME"));
    }
}

