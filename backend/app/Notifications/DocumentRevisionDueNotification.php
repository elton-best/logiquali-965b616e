<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRevisionDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $document;
    protected $daysUntilDue;

    public function __construct(Document $document, int $daysUntilDue)
    {
        $this->document = $document;
        $this->daysUntilDue = $daysUntilDue;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $urgency = $this->daysUntilDue <= 7 ? '🔴 URGENT' : '⚠️ Rappel';
        
        return (new MailMessage)
            ->subject("{$urgency} - Révision document due")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le document **{$this->document->nom}** ({$this->document->code}) nécessite une révision.")
            ->line("Date de révision prévue : **{$this->document->prochaine_revision->format('d/m/Y')}**")
            ->line("Jours restants : **{$this->daysUntilDue} jour(s)**")
            ->action('Voir le document', url("/documents/{$this->document->id}"))
            ->line('Merci de procéder à la révision dans les délais.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'document_revision_due',
            'document_id' => $this->document->id,
            'document_code' => $this->document->code,
            'document_nom' => $this->document->nom,
            'days_until_due' => $this->daysUntilDue,
            'due_date' => $this->document->prochaine_revision,
            'urgency' => $this->daysUntilDue <= 7 ? 'high' : 'medium',
            'message' => "Révision du document {$this->document->code} due dans {$this->daysUntilDue} jour(s)",
            'action_url' => "/documents/{$this->document->id}"
        ];
    }
}