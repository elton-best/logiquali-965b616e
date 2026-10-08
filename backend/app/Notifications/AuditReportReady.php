<?php

namespace App\Notifications;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuditReportReady extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Audit $audit,
        public string $format = 'pdf'
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
        $formatLabel = strtoupper($this->format);

        return (new MailMessage)
            ->subject("Rapport d'audit disponible : {$this->audit->ref}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le rapport d'audit suivant est maintenant disponible :")
            ->line("**Audit** : {$this->audit->title}")
            ->line("**Réf** : {$this->audit->ref}")
            ->line("**Date de réalisation** : " . $this->audit->actual_date?->format('d/m/Y'))
            ->line("**Taux de conformité** : {$this->audit->conformity_rate}%")
            ->line("**Format** : {$formatLabel}")
            ->action('Télécharger le rapport', url("/api/v1/audits/{$this->audit->id}/download-report?format={$this->format}"))
            ->line('Vous pouvez consulter le rapport complet depuis votre espace.')
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
            'format' => $this->format,
            'conformity_rate' => $this->audit->conformity_rate,
            'type' => 'audit_report_ready'
        ];
    }
}
