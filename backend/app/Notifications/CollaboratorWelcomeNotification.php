<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollaboratorWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $password,
        private ?User $createdBy
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur ' . env("APP_NAME"))
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Vous avez été ajouté(e) à la plateforme ' . env("APP_NAME") . ' par ' . ($this->createdBy?->name ?? 'un administrateur') . '.')
            ->line('**Email :** ' . $notifiable->email)
            ->line('Pour des raisons de sécurité, aucun mot de passe n’est transmis par email.')
            ->line('Veuillez définir votre mot de passe depuis la page "Mot de passe oublié" si nécessaire.')
            ->action('Définir mon mot de passe', url('/auth/forgot-password'))
            ->line('Si vous avez des questions, n\'hésitez pas à contacter votre administrateur.')
            ->salutation('Cordialement, L\'équipe ' . env("APP_NAME"));
    }
}
