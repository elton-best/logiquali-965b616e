<?php

namespace App\Notifications;

use App\Models\DocumentSignatureWorkflow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DocumentSignatureWorkflow $workflow,
        public bool $isReminder = false
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = $this->isReminder 
            ? 'Rappel: Signature requise' 
            : 'Signature requise pour un document';

        return (new MailMessage)
            ->subject($subject)
            ->line('Un document nécessite votre signature.')
            ->line("Type: {$this->workflow->document_type}")
            ->line("Étape: {$this->workflow->current_step}/{$this->workflow->total_steps}")
            ->line("Expire le: {$this->workflow->expires_at->format('d/m/Y à H:i')}")
            ->action('Signer le document', url("/workflows/{$this->workflow->id}"))
            ->line('Merci de traiter cette demande dans les meilleurs délais.');
    }

    public function toArray($notifiable): array
    {
        return [
            'workflow_id' => $this->workflow->id,
            'document_type' => $this->workflow->document_type,
            'document_id' => $this->workflow->document_id,
            'current_step' => $this->workflow->current_step,
            'total_steps' => $this->workflow->total_steps,
            'expires_at' => $this->workflow->expires_at,
            'is_reminder' => $this->isReminder,
        ];
    }
}
