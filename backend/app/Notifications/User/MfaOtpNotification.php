<?php

namespace App\Notifications\User;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MfaOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $expiresInMinutes,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Votre code de verification')
            ->greeting('Bonjour,')
            ->line('Voici votre code de verification pour vous connecter:')
            ->line($this->code)
            ->line('Ce code est valable ' . $this->expiresInMinutes . ' minutes.')
            ->line('Si vous n\'etes pas a l\'origine de cette demande, ignorez cet email.');
    }
}
