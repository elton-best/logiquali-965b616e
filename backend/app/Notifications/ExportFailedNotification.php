<?php

namespace App\Notifications;

use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportFailedNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected string $exportType;
    protected string $errorMessage;

    public function __construct(string $exportType, string $errorMessage)
    {
        $this->exportType = $exportType;
        $this->errorMessage = $errorMessage;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        return (new MailMessage)
            ->subject($this->formatSubject("Échec d'export : {$this->exportType}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre export de **{$this->exportType}** a échoué.")
            ->line("**Erreur :** {$this->errorMessage}")
            ->line("Veuillez réessayer ou contacter le support si le problème persiste.")
            ->salutation($this->formatSalutation());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'export_failed',
            'export_type' => $this->exportType,
            'error_message' => $this->errorMessage,
            'created_at' => now()->toISOString(),
        ];
    }

    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
