<?php

namespace App\Notifications;

use App\Models\Habilitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HabilitationExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Habilitation $habilitation,
        public int $daysRemaining
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $urgency = $this->getUrgencyLevel();
        $subject = $urgency['emoji'] . ' Habilitation expire dans ' . $this->daysRemaining . ' jour(s)';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . $notifiable->first_name)
            ->line("Votre habilitation **{$this->habilitation->title}** expire dans {$this->daysRemaining} jour(s).")
            ->line("**Type :** {$this->habilitation->type}")
            ->line("**Date d'expiration :** {$this->habilitation->expiry_date->format('d/m/Y')}")
            ->line("**Autorité :** {$this->habilitation->issuing_authority}")
            ->action('Voir l\'habilitation', url("/habilitations/{$this->habilitation->id}"))
            ->line('Veuillez prendre les mesures nécessaires pour renouveler cette habilitation.')
            ->salutation('Cordialement, L\'équipe ' . env("APP_NAME"));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'habilitation_expiring',
            'habilitation_id' => $this->habilitation->id,
            'title' => $this->habilitation->title,
            'days_remaining' => $this->daysRemaining,
            'expiry_date' => $this->habilitation->expiry_date->format('Y-m-d'),
            'urgency' => $this->getUrgencyLevel()['level'],
            'message' => "Habilitation {$this->habilitation->title} expire dans {$this->daysRemaining} jour(s)"
        ];
    }

    private function getUrgencyLevel(): array
    {
        return match (true) {
            $this->daysRemaining <= 3 => ['level' => 'critical', 'emoji' => ''],
            $this->daysRemaining <= 7 => ['level' => 'high', 'emoji' => ''],
            $this->daysRemaining <= 15 => ['level' => 'medium', 'emoji' => '🟡'],
            default => ['level' => 'low', 'emoji' => '']
        };
    }
}
