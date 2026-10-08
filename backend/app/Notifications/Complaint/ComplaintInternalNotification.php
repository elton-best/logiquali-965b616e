<?php

namespace App\Notifications\Complaint;

use App\Models\Reclamation;
use App\Models\User;
use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ComplaintInternalNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected string $eventType;
    protected Reclamation $complaint;
    protected ?User $assignedTo;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $eventType, Reclamation $complaint, ?User $assignedTo = null)
    {
        $this->eventType = $eventType; // 'created' | 'assigned'
        $this->complaint = $complaint;
        $this->assignedTo = $assignedTo;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        if ($this->eventType === 'created') {
            return $this->createdMail($notifiable);
        }

        return $this->assignedMail($notifiable);
    }

    /**
     * Mail pour nouvelle réclamation
     */
    protected function createdMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->formatSubject("Nouvelle réclamation - Réf: {$this->complaint->ref}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle réclamation a été enregistrée et nécessite votre attention.")
            ->line("**Référence :** {$this->complaint->ref}")
            ->line("**Titre :** {$this->complaint->title}")
            ->line("**Client :** {$this->complaint->client_name}")
            ->line("**Catégorie :** " . ($this->complaint->category ?? 'Non définie'))
            ->line("**Sévérité :** " . ($this->complaint->severity ?? 'Non définie'))
            ->line("")
            ->line("**Description :**")
            ->line(substr($this->complaint->description, 0, 300) . (strlen($this->complaint->description) > 300 ? '...' : ''))
            ->action('Traiter la réclamation', $this->buildActionUrl("/complaints/{$this->complaint->id}"))
            ->line("Merci de prendre en charge cette réclamation rapidement.")
            ->salutation($this->formatSalutation());
    }

    /**
     * Mail pour assignation
     */
    protected function assignedMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->formatSubject("Réclamation assignée - Réf: {$this->complaint->ref}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une réclamation vous a été assignée.")
            ->line("**Référence :** {$this->complaint->ref}")
            ->line("**Titre :** {$this->complaint->title}")
            ->line("**Client :** {$this->complaint->client_name}")
            ->line("")
            ->action('Consulter la réclamation', $this->buildActionUrl("/complaints/{$this->complaint->id}"))
            ->line("Merci de traiter cette réclamation dans les meilleurs délais.")
            ->salutation($this->formatSalutation());
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $baseData = $this->formatBaseArray(
            type: 'complaint_internal',
            entityId: $this->complaint->id,
            ref: $this->complaint->ref,
            url: $this->buildActionUrl("/complaints/{$this->complaint->id}"),
            additionalData: [
                'event_type' => $this->eventType,
                'complaint_title' => $this->complaint->title,
            ]
        );

        if ($this->eventType === 'created') {
            $baseData['message'] = "Nouvelle réclamation : {$this->complaint->ref} - {$this->complaint->title}";
        } else {
            $baseData['message'] = "Réclamation assignée : {$this->complaint->ref}";
            if ($this->assignedTo) {
                $baseData['assigned_to_name'] = $this->assignedTo->name;
            }
        }

        return $baseData;
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'complaint_internal',
            'event_type' => $this->eventType,
            'entity_id' => $this->complaint->id,
            'entity_ref' => $this->complaint->ref,
            'message' => $this->eventType === 'created'
                ? "Nouvelle réclamation : {$this->complaint->ref}"
                : "Réclamation assignée : {$this->complaint->ref}",
        ]);
    }

    /**
     * Handle a notification failure.
     */
    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
