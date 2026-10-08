<?php

namespace App\Notifications\User;

use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected string $userType;
    protected string $accessMode;
    protected ?string $temporaryPassword;
    protected ?string $setPasswordUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $userType,
        string $accessMode = 'set_password_link',
        ?string $temporaryPassword = null,
        ?string $setPasswordUrl = null
    ) {
        $this->userType = $userType; // 'collaborator' | 'client'
        $this->accessMode = $accessMode; // 'temporary_password' | 'set_password_link'
        $this->temporaryPassword = $temporaryPassword;
        $this->setPasswordUrl = $setPasswordUrl;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', \App\Channels\UserNotificationChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        $greeting = $this->userType === 'collaborator'
            ? "Bienvenue dans l'équipe BestQHSE !"
            : "Bienvenue sur BestQHSE !";

        $mailMessage = (new MailMessage)
            ->subject($this->formatSubject("Vos identifiants de connexion"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line($greeting)
            ->line("Votre compte a été créé avec succès.")
            ->line("**Identifiant :** {$notifiable->email}");

        if ($this->accessMode === 'temporary_password' && $this->temporaryPassword) {
            $mailMessage->line("**Mot de passe temporaire :** {$this->temporaryPassword}")
                ->line("Pour des raisons de sécurité, vous devrez changer ce mot de passe lors de votre première connexion.")
                ->action('Se connecter', $this->buildActionUrl('/login'));
        } else {
            $mailMessage->line("Pour définir votre mot de passe et activer votre compte, cliquez sur le bouton ci-dessous :")
                ->action('Définir mon mot de passe', $this->setPasswordUrl ?? $this->buildActionUrl('/set-password'))
                ->line("Ce lien est valable pendant 48 heures.");
        }

        $mailMessage->line("Si vous avez des questions, n'hésitez pas à contacter notre équipe.")
            ->salutation($this->formatSalutation());

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'user_type' => $this->userType,
            'access_mode' => $this->accessMode,
            'message' => $this->userType === 'collaborator'
                ? 'Bienvenue dans l\'équipe BestQHSE ! Votre compte collaborateur a été créé.'
                : 'Bienvenue sur BestQHSE ! Votre compte client a été créé.',
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Handle a notification failure.
     */
    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
