<?php

namespace App\Notifications\Enterprise;

use App\Models\Enterprise;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnterpriseApprovedNotification extends Notification
{
    use Queueable, LogsNotifications;

    public function __construct(
        public Enterprise $enterprise
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        return (new MailMessage)
            ->subject("[BestQHSE] Votre entreprise a été approuvée")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Excellente nouvelle ! 🎉")
            ->line("")
            ->line("Votre entreprise **\"{$this->enterprise->name}\"** a été approuvée et votre compte est maintenant actif.")
            ->line("")
            ->line("Vous pouvez dès à présent accéder à l'ensemble des fonctionnalités de la plateforme BestQHSE.")
            ->action('Se connecter', url('/login'))
            ->line("Nous vous souhaitons une excellente expérience sur notre plateforme.")
            ->salutation("Cordialement,\nL'équipe BestQHSE");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'enterprise_approved',
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'type' => 'enterprise_approved',
            'title' => 'Entreprise approuvée',
            'message' => "Votre entreprise {$this->enterprise->name} a été approuvée !",
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
            'action_url' => url('/login'),
            'created_at' => now()->toISOString(),
        ];
    }

    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
