<?php

namespace App\Notifications;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuditInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Audit $audit,
        public string $role = 'auditee' // 'auditor' | 'auditee'
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
        $subject = $this->role === 'auditor' 
            ? "Invitation : Audit {$this->audit->title}"
            : "Audit prévu : {$this->audit->title}";

        $intro = $this->role === 'auditor'
            ? "Vous avez été désigné(e) comme auditeur pour l'audit suivant :"
            : "Un audit est prévu dans votre périmètre :";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Bonjour {$notifiable->name},")
            ->line($intro)
            ->line("**Audit** : {$this->audit->title}")
            ->line("**Type** : " . ucfirst($this->audit->type))
            ->line("**Date prévue** : " . $this->audit->planned_date?->format('d/m/Y'))
            ->line("**Responsable** : " . $this->audit->leadAuditor?->name)
            ->line("**Périmètre** : " . $this->audit->scope)
            ->action('Voir le détail', url("/audits/{$this->audit->id}"))
            ->line('Merci de votre collaboration.')
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
            'role' => $this->role,
            'type' => 'audit_invitation'
        ];
    }
}
