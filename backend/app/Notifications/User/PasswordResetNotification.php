<?php

namespace App\Notifications\User;

use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable, LogsNotifications;

    protected string $token;
    protected ?string $resetUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token, ?string $resetUrl = null)
    {
        $this->token = $token;
        $this->resetUrl = $resetUrl;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->resetUrl ?? url('/auth/reset-password?token=' . urlencode($this->token) . '&email=' . urlencode($notifiable->email));

        $this->logNotificationSent($notifiable, 'mail');

        return (new MailMessage)
            ->subject("[BestQHSE] Réinitialisation de votre mot de passe")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.")
            ->action('Réinitialiser mon mot de passe', $url)
            ->line("Ce lien de réinitialisation expirera dans **60 minutes**.")
            ->line("")
            ->line("Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune action n'est requise de votre part.")
            ->salutation("Cordialement,\nL'équipe BestQHSE");
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $safeActionUrl = $this->sanitizeActionUrlForStorage(
            $this->resetUrl ?? url('/auth/reset-password')
        );

        return [
            'type' => 'password_reset',
            'message' => 'Demande de réinitialisation de mot de passe reçue',
            'action_url' => $safeActionUrl,
            'expires_at' => now()->addMinutes(60)->toISOString(),
        ];
    }

    private function sanitizeActionUrlForStorage(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return url('/auth/reset-password');
        }

        $path = $parts['path'] ?? '/auth/reset-password';
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query['token']);
        }

        $safeQuery = http_build_query($query);
        return $safeQuery !== '' ? $path . '?' . $safeQuery : $path;
    }

    /**
     * Handle a notification failure.
     */
    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
