<?php

namespace App\Notifications\Complaint;

use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Reclamation $incident,
        private readonly int $daysOverdue,
        private readonly int $overdueActionsCount = 0,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Alerte incident en retard - {$this->incident->ref}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un incident n'est toujours pas clôturé sur votre périmètre.")
            ->line("Référence: {$this->incident->ref}")
            ->line("Titre: " . (string) ($this->incident->title ?? 'N/A'))
            ->line("Retard: {$this->daysOverdue} jour(s)")
            ->line("Actions incidents échues: {$this->overdueActionsCount}")
            ->line("Merci de traiter cet incident dès que possible.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_alert',
            'incident_id' => (int) $this->incident->id,
            'incident_ref' => (string) $this->incident->ref,
            'incident_title' => (string) ($this->incident->title ?? ''),
            'days_overdue' => $this->daysOverdue,
            'overdue_actions_count' => $this->overdueActionsCount,
            'message' => "Incident {$this->incident->ref} en retard de {$this->daysOverdue} jour(s).",
        ];
    }
}

