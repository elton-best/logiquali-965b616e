<?php

namespace App\Notifications;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuditDeadlineReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Audit $audit,
        public int $daysRemaining
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $urgency = $this->daysRemaining <= 3 ? '🔴 URGENT' : '⚠️ Rappel';

        return (new MailMessage)
            ->subject("{$urgency} : Audit prévu dans {$this->daysRemaining} jours")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un audit est prévu dans **{$this->daysRemaining} jours** :")
            ->line("**Audit** : {$this->audit->title}")
            ->line("**Réf** : {$this->audit->ref}")
            ->line("**Date prévue** : " . $this->audit->planned_date?->format('d/m/Y'))
            ->line("**Type** : " . ucfirst($this->audit->type))
            ->line("**Périmètre** : " . $this->audit->scope)
            ->when($this->daysRemaining <= 3, function($mail) {
                return $mail->line('⚠️ **Action requise** : Veuillez préparer les documents nécessaires.');
            })
            ->action('Voir le détail', url("/audits/{$this->audit->id}"))
            ->line('Merci de vous assurer que tout est prêt pour cet audit.')
            ->salutation('Cordialement, L\'équipe Qualité');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'audit_id' => $this->audit->id,
            'audit_ref' => $this->audit->ref,
            'audit_title' => $this->audit->title,
            'planned_date' => $this->audit->planned_date?->format('Y-m-d'),
            'days_remaining' => $this->daysRemaining,
            'urgency' => $this->daysRemaining <= 3 ? 'high' : 'medium',
            'type' => 'audit_deadline_reminder'
        ];
    }
}
