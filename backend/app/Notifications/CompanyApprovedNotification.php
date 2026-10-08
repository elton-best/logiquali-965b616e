<?php

namespace App\Notifications;

use App\Models\Enterprise;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Enterprise $enterprise
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Votre entreprise a été approuvée - BestQHSE')
            ->greeting('Félicitations !')
            ->line("Nous avons le plaisir de vous informer que votre entreprise **{$this->enterprise->name}** a été approuvée.")
            ->line('Vous pouvez maintenant accéder à toutes les fonctionnalités de la plateforme BestQHSE.')
            ->action('Accéder à mon espace', url('/login'))
            ->line('Merci de votre confiance !')
            ->salutation('L\'équipe BestQHSE');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'enterprise_id' => $this->enterprise->id,
            'enterprise_name' => $this->enterprise->name,
            'type' => 'company_approved',
            'message' => "Votre entreprise {$this->enterprise->name} a été approuvée",
        ];
    }
}
