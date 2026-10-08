<?php

namespace App\Notifications;

use App\Models\Enterprise;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Enterprise $enterprise,
        public string $reason
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre demande d\'inscription - BestQHSE')
            ->greeting('Bonjour,')
            ->line("Nous avons examiné votre demande d'inscription pour l'entreprise **{$this->enterprise->name}**.")
            ->line('Malheureusement, nous ne pouvons pas valider votre demande pour la raison suivante :')
            ->line("> {$this->reason}")
            ->line('Si vous pensez qu\'il s\'agit d\'une erreur ou si vous souhaitez plus d\'informations, n\'hésitez pas à nous contacter.')
            ->action('Nous contacter', url('/contact'))
            ->salutation('Cordialement,\nL\'équipe BestQHSE');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
            'type' => 'company_rejected',
            'reason' => $this->reason,
            'message' => "Votre demande pour {$this->enterprise->name} a été rejetée",
        ];
    }
}
