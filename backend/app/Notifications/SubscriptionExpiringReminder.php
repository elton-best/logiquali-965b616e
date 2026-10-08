<?php

namespace App\Notifications;

use App\Models\EnterpriseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EnterpriseSubscription $subscription,
        public int $daysRemaining
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $urgency = $this->daysRemaining <= 3 ? 'URGENT' : 'Rappel';
        $siteName = $this->subscription->site?->name ?? 'site';
        $expirationDate = $this->subscription->expiration_date?->format('d/m/Y');

        return (new MailMessage)
            ->subject("{$urgency} : Abonnement expire dans {$this->daysRemaining} jours")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("L'abonnement du {$siteName} arrive a expiration.")
            ->line("Date d'expiration : {$expirationDate}")
            ->action('Voir l\'abonnement', url("/subscriptions/{$this->subscription->id}"))
            ->line('Merci de proceder au renouvellement si necessaire.')
            ->salutation("Cordialement, L'equipe BestQHSE");
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'subscription_expiring',
            'subscription_id' => $this->subscription->id,
            'site_id' => $this->subscription->site_id,
            'enterprise_id' => $this->subscription->site?->enterprise_id,
            'expiration_date' => $this->subscription->expiration_date?->format('Y-m-d'),
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
