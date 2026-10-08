<?php

namespace App\Notifications\Enterprise;

use App\Models\Enterprise;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnterpriseRejectedNotification extends Notification
{
    use Queueable, LogsNotifications;

    public function __construct(
        public Enterprise $enterprise,
        public ?string $reason = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        $mail = (new MailMessage)
            ->subject("[BestQHSE] Votre demande d'inscription n'a pas été approuvée")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Nous avons examiné votre demande d'inscription pour l'entreprise **\"{$this->enterprise->name}\"**.")
            ->line("")
            ->line("Malheureusement, nous ne sommes pas en mesure de valider votre dossier pour le moment.");

        if ($this->reason) {
            $mail->line("")
                ->line("**Motif :** {$this->reason}");
        }

        return $mail
            ->line("")
            ->line("Si vous souhaitez contester cette décision ou compléter votre dossier, n'hésitez pas à contacter notre support.")
            ->line("Email : support@BestQHSE.com")
            ->salutation("Cordialement,\nL'équipe BestQHSE");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'enterprise_rejected',
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
            'reason' => $this->reason,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return [
            'type' => 'enterprise_rejected',
            'title' => 'Inscription non approuvée',
            'message' => "Votre demande d'inscription n'a pas été approuvée",
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
            'reason' => $this->reason,
            'created_at' => now()->toISOString(),
        ];
    }

    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
