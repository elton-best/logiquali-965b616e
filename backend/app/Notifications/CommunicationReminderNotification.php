<?php

namespace App\Notifications;

use App\Models\Communication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommunicationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Communication $communication,
        public int $daysRemaining
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $urgency = $this->getUrgencyLevel();
        
        return (new MailMessage)
            ->subject("Rappel Communication - {$this->communication->titre} ({$urgency})")
            ->line("Une communication est prévue dans {$this->daysRemaining} jour(s).")
            ->line("**Titre:** {$this->communication->titre}")
            ->line("**Date prévue:** {$this->communication->date_prevue->format('d/m/Y')}")
            ->line("**Public cible:** {$this->communication->public_cible}")
            ->action('Voir la communication', url("/communications/{$this->communication->id}"))
            ->line($this->daysRemaining <= 3 ? '⚠️ Action requise rapidement !' : 'Merci de préparer cette communication.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'communication_reminder',
            'communication_id' => $this->communication->id,
            'titre' => $this->communication->titre,
            'date_prevue' => $this->communication->date_prevue,
            'days_remaining' => $this->daysRemaining,
            'urgency' => $this->getUrgencyLevel(),
            'message' => "Communication '{$this->communication->titre}' prévue dans {$this->daysRemaining} jour(s)"
        ];
    }

    private function getUrgencyLevel(): string
    {
        return match (true) {
            $this->daysRemaining <= 1 => 'URGENT',
            $this->daysRemaining <= 3 => 'ÉLEVÉ',
            $this->daysRemaining <= 7 => 'MOYEN',
            default => 'NORMAL'
        };
    }
}