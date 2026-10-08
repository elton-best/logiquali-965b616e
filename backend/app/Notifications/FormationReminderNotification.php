<?php

namespace App\Notifications;

use App\Models\Formation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FormationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Formation $formation,
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
            ->subject("Formation à échéance - {$this->formation->designation} ({$urgency})")
            ->line("Une formation est prévue dans {$this->daysRemaining} jour(s).")
            ->line("**Formation:** {$this->formation->designation}")
            ->line("**Formateur:** {$this->formation->formateur}")
            ->line("**Date prévue:** {$this->formation->date_debut->format('d/m/Y')}")
            ->action('Voir la formation', url('/company/iso/support/training'))
            ->line($this->daysRemaining <= 3 ? '⚠️ Préparation urgente requise !' : 'Merci de préparer cette formation.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'formation_reminder',
            'formation_id' => $this->formation->id,
            'designation' => $this->formation->designation,
            'formateur' => $this->formation->formateur,
            'date_debut' => $this->formation->date_debut,
            'days_remaining' => $this->daysRemaining,
            'urgency' => $this->getUrgencyLevel(),
            'message' => "Formation '{$this->formation->designation}' prévue dans {$this->daysRemaining} jour(s)"
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
