<?php

namespace App\Notifications\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemporaryPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $temporaryPassword)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur BestQHSE - Accès temporaire')
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Votre compte a été créé avec succès.')
            ->line('Mot de passe temporaire : ' . $this->temporaryPassword)
            ->line('Pour des raisons de sécurité, vous devrez le modifier lors de votre première connexion.')
            ->action('Se connecter', config('app.frontend_url') . '/auth/login')
            ->line('Merci de votre confiance.');
    }
}
