<?php

namespace App\Notifications\Subscription;

use App\Models\EnterpriseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public EnterpriseSubscription $subscription
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[BestQHSE] Votre période d\'essai a expiré')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Votre période d\'essai gratuite de 90 jours est maintenant terminée.')
            ->line('')
            ->line('**Pour continuer à utiliser BestQHSE, veuillez valider votre paiement.**')
            ->line('')
            ->line('Votre accès à la plateforme est actuellement suspendu.')
            ->line('Toutes vos données sont conservées en sécurité.')
            ->action('Valider mon paiement maintenant', url('/company/subscription'))
            ->line('')
            ->line('Besoin d\'aide ? Contactez notre équipe support.')
            ->salutation('Cordialement,\nL\'équipe BestQHSE');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_expired',
            'subscription_id' => $this->subscription->id,
            'expired_at' => $this->subscription->trial_ends_at->format('Y-m-d'),
        ];
    }
}
