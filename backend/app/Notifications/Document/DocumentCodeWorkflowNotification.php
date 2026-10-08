<?php

namespace App\Notifications\Document;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentCodeWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $eventType,
        private Document $document,
        private ?User $actor = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $actorName = $this->actor?->name ?? 'Un utilisateur';

        return match ($this->eventType) {
            'code_verification_request' => (new MailMessage)
                ->subject('Nouveau code document à vérifier')
                ->greeting('Bonjour ' . $notifiable->name)
                ->line("{$actorName} a créé un document nécessitant une vérification de code.")
                ->line("**Document:** {$this->document->title}")
                ->line("**Code:** {$this->document->code}")
                ->line("**Type:** {$this->document->type}")
                ->action('Vérifier le code', url("/documents/workflow/verification"))
                ->line('Merci de traiter cette demande rapidement.'),

            'code_verified' => (new MailMessage)
                ->subject('Code document vérifié')
                ->greeting('Bonjour ' . $notifiable->name)
                ->line("{$actorName} a vérifié le code de votre document.")
                ->line("**Document:** {$this->document->title}")
                ->line("**Code:** {$this->document->code}")
                ->line('Le document est maintenant en attente d\'approbation.')
                ->action('Voir le document', url("/documents/{$this->document->id}")),

            'code_approval_request' => (new MailMessage)
                ->subject('Nouveau code document à approuver')
                ->greeting('Bonjour ' . $notifiable->name)
                ->line("{$actorName} a vérifié un document nécessitant votre approbation.")
                ->line("**Document:** {$this->document->title}")
                ->line("**Code:** {$this->document->code}")
                ->line("**Type:** {$this->document->type}")
                ->action('Approuver le code', url("/documents/workflow/approval"))
                ->line('Merci de traiter cette demande rapidement.'),

            'code_approved' => (new MailMessage)
                ->subject('Code document approuvé')
                ->greeting('Bonjour ' . $notifiable->name)
                ->line("{$actorName} a approuvé le code de votre document.")
                ->line("**Document:** {$this->document->title}")
                ->line("**Code:** {$this->document->code}")
                ->line('Le code est maintenant actif et définitif.')
                ->action('Voir le document', url("/documents/{$this->document->id}"))
                ->success(),

            'code_rejected' => (new MailMessage)
                ->subject('Code document rejeté')
                ->greeting('Bonjour ' . $notifiable->name)
                ->line("{$actorName} a rejeté le code de votre document.")
                ->line("**Document:** {$this->document->title}")
                ->line("**Code:** {$this->document->code}")
                ->line('Le code a été libéré et sera recyclé.')
                ->action('Voir le document', url("/documents/{$this->document->id}"))
                ->error(),

            default => (new MailMessage)
                ->subject('Notification document')
                ->line('Une action a été effectuée sur un document.'),
        };
    }

    public function toArray($notifiable): array
    {
        return [
            'event_type' => $this->eventType,
            'document_id' => $this->document->id,
            'document_title' => $this->document->title,
            'document_code' => $this->document->code,
            'document_type' => $this->document->type,
            'actor_id' => $this->actor?->id,
            'actor_name' => $this->actor?->name,
            'created_at' => now()->toISOString(),
        ];
    }
}
