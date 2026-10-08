<?php

namespace App\Notifications;

use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportReadyNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected string $exportType;
    protected string $filePath;
    protected ?string $fileName;

    public function __construct(string $exportType, string $filePath, ?string $fileName = null)
    {
        $this->exportType = $exportType;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        return (new MailMessage)
            ->subject($this->formatSubject("Export terminé : {$this->exportType}"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre export de **{$this->exportType}** est prêt.")
            ->action('Télécharger le fichier', url($this->filePath))
            ->line("Le fichier restera disponible pendant 24 heures.")
            ->salutation($this->formatSalutation());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'export_ready',
            'export_type' => $this->exportType,
            'file_path' => $this->filePath,
            'file_name' => $this->fileName,
            'action_url' => url($this->filePath),
            'created_at' => now()->toISOString(),
        ];
    }

    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
