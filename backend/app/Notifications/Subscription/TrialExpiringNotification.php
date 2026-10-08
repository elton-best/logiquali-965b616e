<?php

namespace App\Notifications\Subscription;

use App\Models\EnterpriseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public EnterpriseSubscription $subscription,
        public int $daysRemaining
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("[BestQHSE] Votre période d'essai expire dans {$this->daysRemaining} jour(s)")
            ->greeting("Bonjour {$notifiable->name},");

        if ($this->daysRemaining === 1) {
            $message->line('⏰ Votre période d\'essai gratuite expire **demain**.');
        } else {
            $message->line("⏰ Votre période d'essai gratuite expire dans **{$this->daysRemaining} jours**.");
        }

        return $message
            ->line('')
            ->line('Pour continuer à profiter de BestQHSE sans interruption, veuillez valider votre paiement dès maintenant.')
            ->line('')
            ->line('**Pourquoi choisir BestQHSE ?**')
            ->line('✓ Conformité ISO garantie')
            ->line('✓ Gain de temps considérable')
            ->line('✓ Support technique réactif')
            ->line('✓ Mises à jour régulières')
            ->action('Valider mon paiement', url('/company/subscription'))
            ->line('')
            ->line('Des questions ? Notre équipe est là pour vous aider.')
            ->salutation('Cordialement,\nL\'équipe BestQHSE');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_expiring',
            'subscription_id' => $this->subscription->id,
            'days_remaining' => $this->daysRemaining,
            'trial_ends_at' => $this->subscription->trial_ends_at->format('Y-m-d'),
        ];
    }
}
