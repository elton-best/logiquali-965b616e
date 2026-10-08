<?php

namespace App\Notifications\Subscription;

use App\Models\EnterpriseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRequiredNotification extends Notification
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
            ->subject('[BestQHSE] Action requise : Paiement en attente')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Votre abonnement BestQHSE nécessite une validation de paiement.')
            ->line('')
            ->line('**Détails de l\'abonnement :**')
            ->line('• Offre : ' . $this->subscription->offer->name)
            ->line('• Montant : ' . number_format($this->subscription->offer->price, 0, ',', ' ') . ' XOF')
            ->line('')
            ->action('Valider mon paiement', url('/company/subscription'))
            ->line('')
            ->line('Une fois le paiement validé, votre accès sera immédiatement rétabli.')
            ->salutation('Cordialement,\nL\'équipe BestQHSE');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_required',
            'subscription_id' => $this->subscription->id,
        ];
    }
}
