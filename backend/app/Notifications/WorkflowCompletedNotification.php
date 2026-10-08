<?php

namespace App\Notifications;

use App\Models\DocumentSignatureWorkflow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DocumentSignatureWorkflow $workflow
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document validé - Toutes les signatures complétées')
            ->line('Le workflow de signatures est terminé.')
            ->line("Type: {$this->workflow->document_type}")
            ->line("Complété le: {$this->workflow->completed_at->format('d/m/Y à H:i')}")
            ->action('Voir le document', url("/documents/{$this->workflow->document_type}/{$this->workflow->document_id}"))
            ->line('Le document est maintenant validé et figé.');
    }

    public function toArray($notifiable): array
    {
        return [
            'workflow_id' => $this->workflow->id,
            'document_type' => $this->workflow->document_type,
            'document_id' => $this->workflow->document_id,
            'completed_at' => $this->workflow->completed_at,
        ];
    }
}
