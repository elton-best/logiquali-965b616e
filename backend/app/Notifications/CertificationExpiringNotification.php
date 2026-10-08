<?php

namespace App\Notifications;

use App\Models\EnterpriseCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificationExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EnterpriseCertification $certification
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $daysRemaining = $this->certification->daysUntilExpiry();

        return (new MailMessage)
            ->subject("Certification expire dans {$daysRemaining} jours")
            ->line("Votre certification {$this->certification->certification->name} expire bientôt.")
            ->line("Date d'expiration: {$this->certification->expiry_date->format('d/m/Y')}")
            ->line("Jours restants: {$daysRemaining}")
            ->action('Gérer les certifications', url('/certifications'))
            ->line('Pensez à planifier le renouvellement.');
    }

    public function toArray($notifiable): array
    {
        return [
            'certification_id' => $this->certification->id,
            'certification_name' => $this->certification->certification->name,
            'expiry_date' => $this->certification->expiry_date,
            'days_remaining' => $this->certification->daysUntilExpiry(),
        ];
    }
}
