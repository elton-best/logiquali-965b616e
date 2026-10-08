<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeClientNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur BestQHSE !')
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Bienvenue sur BestQHSE, votre plateforme de gestion qualité.')
            ->line('Votre compte a été créé avec succès.')
            ->line('Pour activer votre compte, veuillez vérifier votre adresse email en cliquant sur le lien de vérification que nous vous avons envoyé.')
            ->line('Une fois votre email vérifié, vous pourrez vous connecter et profiter de tous nos services :')
            ->line('• Déposer des plaintes et réclamations')
            ->line('• Suivre l\'évolution de vos demandes en temps réel')
            ->line('• Répondre aux enquêtes de satisfaction')
            ->line('• Utiliser le chatbot pour dialoguer avec les entreprises')
            ->action('Se connecter', config('app.frontend_url') . '/auth/login')
            ->line('Merci de nous faire confiance !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
