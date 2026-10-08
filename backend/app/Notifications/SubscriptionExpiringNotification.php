<?php

namespace App\Notifications;

use App\Models\EnterpriseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $subscription;
    protected $daysRemaining;

    public function __construct(EnterpriseSubscription $subscription, int $daysRemaining)
    {
        $this->subscription = $subscription;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Canaux de notification (database + mail)
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Email de notification
     */
    public function toMail($notifiable): MailMessage
    {
        $normNames = $this->subscription->offer->norms->pluck('name')->implode(', ');
        $expirationDate = \Carbon\Carbon::parse($this->subscription->expiration_date)
            ->format('d/m/Y');

        $urgency = $this->getUrgencyLevel();

        return (new MailMessage)
            ->subject($this->getSubject())
            ->greeting("Bonjour {$notifiable->name},")
            ->line($this->getMessage())
            ->line("**Norme(s) concernée(s)**: {$normNames}")
            ->line("**Date d'expiration**: {$expirationDate}")
            ->line("**Jours restants**: {$this->daysRemaining}")
            ->action('Renouveler maintenant', url('/subscriptions/dashboard'))
            ->line($this->getFooterMessage($urgency))
            ->salutation('L\'équipe BestQHSE');
    }

    /**
     * Notification en base de données
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'subscription_expiring',
            'subscription_id' => $this->subscription->id,
            'subscription_ref' => $this->subscription->ref,
            'norms' => $this->subscription->offer->norms->pluck('name')->toArray(),
            'days_remaining' => $this->daysRemaining,
            'expiration_date' => $this->subscription->expiration_date,
            'urgency' => $this->getUrgencyLevel(),
            'message' => $this->getMessage(),
            'action_url' => url('/subscriptions/dashboard'),
        ];
    }

    protected function getSubject(): string
    {
        if ($this->daysRemaining <= 3) {
            return "🚨 URGENT: Votre abonnement expire dans {$this->daysRemaining} jour(s)";
        }

        if ($this->daysRemaining <= 7) {
            return "⚠️ Votre abonnement expire dans {$this->daysRemaining} jours";
        }

        return "📅 Rappel: Votre abonnement expire dans {$this->daysRemaining} jours";
    }

    protected function getMessage(): string
    {
        if ($this->daysRemaining <= 3) {
            return "Votre abonnement expire très bientôt ! Renouvelez-le dès maintenant pour éviter toute interruption de service.";
        }

        if ($this->daysRemaining <= 7) {
            return "Votre abonnement arrive à expiration. Pensez à le renouveler pour continuer à accéder à tous vos modules.";
        }

        return "Votre abonnement arrive à expiration dans {$this->daysRemaining} jours. Anticipez le renouvellement pour assurer la continuité de vos services.";
    }

    protected function getFooterMessage(string $urgency): string
    {
        if ($urgency === 'critical') {
            return "⚠️ **ATTENTION**: L'accès à la plateforme sera bloqué dès l'expiration si l'abonnement n'est pas renouvelé.";
        }

        return "💡 **Astuce**: Renouvelez dès maintenant, votre nouvel abonnement commencera automatiquement à la fin de l'actuel.";
    }

    protected function getUrgencyLevel(): string
    {
        if ($this->daysRemaining <= 3) {
            return 'critical';
        }

        if ($this->daysRemaining <= 7) {
            return 'high';
        }

        return 'medium';
    }
}
