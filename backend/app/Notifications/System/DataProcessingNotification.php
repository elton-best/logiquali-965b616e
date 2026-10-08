<?php

namespace App\Notifications\System;

use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataProcessingNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected string $processType;
    protected string $entityType;
    protected bool $success;
    protected ?string $message;
    protected ?string $filePath;

    public function __construct(
        string $processType,
        string $entityType,
        bool $success = true,
        ?string $message = null,
        ?string $filePath = null
    ) {
        $this->processType = $processType; // 'export' | 'import'
        $this->entityType = $entityType;
        $this->success = $success;
        $this->message = $message;
        $this->filePath = $filePath;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        $subject = $this->success
            ? ucfirst($this->processType) . " terminé : {$this->entityType}"
            : ucfirst($this->processType) . " échoué : {$this->entityType}";

        $mail = (new MailMessage)
            ->subject($this->formatSubject($subject))
            ->greeting("Bonjour {$notifiable->name},");

        if ($this->success) {
            $mail->line("Votre " . $this->processType . " de **{$this->entityType}** s'est terminé avec succès.");
            
            if ($this->processType === 'export' && $this->filePath) {
                $mail->action('Télécharger le fichier', $this->buildActionUrl($this->filePath));
            }
        } else {
            $mail->line("Votre " . $this->processType . " de **{$this->entityType}** a échoué.")
                 ->line("**Erreur :** " . ($this->message ?? 'Erreur inconnue'));
        }

        return $mail->salutation($this->formatSalutation());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'data_processing',
            'process_type' => $this->processType,
            'entity_type' => $this->entityType,
            'success' => $this->success,
            'message' => $this->message,
            'file_path' => $this->filePath,
            'created_at' => now()->toISOString(),
        ];
    }

    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
