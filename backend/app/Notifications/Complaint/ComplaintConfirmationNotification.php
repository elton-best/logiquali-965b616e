<?php

namespace App\Notifications\Complaint;

use App\Models\Reclamation;
use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintConfirmationNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected Reclamation $complaint;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reclamation $complaint)
    {
        $this->complaint = $complaint;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        return (new MailMessage)
            ->subject($this->formatSubject("Réclamation enregistrée - Réf: {$this->complaint->ref}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Nous avons bien reçu votre réclamation et vous en remercions.")
            ->line("**Numéro de référence :** {$this->complaint->ref}")
            ->line("**Titre :** {$this->complaint->title}")
            ->line("")
            ->line("**Résumé de votre réclamation :**")
            ->line(substr($this->complaint->description, 0, 200) . (strlen($this->complaint->description) > 200 ? '...' : ''))
            ->line("")
            ->action('Consulter ma réclamation', $this->buildActionUrl("/complaints/{$this->complaint->id}"))
            ->line("Notre équipe va analyser votre demande et vous contacter dans les plus brefs délais.")
            ->line("Vous pouvez suivre l'évolution de votre réclamation à tout moment via votre espace client.")
            ->salutation($this->formatSalutation());
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->formatBaseArray(
            type: 'complaint_confirmation',
            entityId: $this->complaint->id,
            ref: $this->complaint->ref,
            url: $this->buildActionUrl("/complaints/{$this->complaint->id}"),
            additionalData: [
                'complaint_title' => $this->complaint->title,
                'message' => "Réclamation enregistrée : {$this->complaint->ref} - {$this->complaint->title}",
            ]
        );
    }

    /**
     * Handle a notification failure.
     */
    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
