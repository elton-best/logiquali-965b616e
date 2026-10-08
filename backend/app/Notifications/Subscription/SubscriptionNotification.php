<?php

namespace App\Notifications\Subscription;

use App\Models\Subscription;
use App\Notifications\Concerns\FormatsNotification;
use App\Notifications\Concerns\LogsNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionNotification extends Notification
{
    use Queueable, FormatsNotification, LogsNotifications;

    protected Subscription $subscription;
    protected string $eventType;
    protected array $additionalData;

    public function __construct(Subscription $subscription, string $eventType, array $additionalData = [])
    {
        $this->subscription = $subscription;
        $this->eventType = $eventType; // 'activated' | 'expiring' | 'expired'
        $this->additionalData = $additionalData;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->logNotificationSent($notifiable, 'mail');

        return match ($this->eventType) {
            'activated' => $this->activatedMail($notifiable),
            'expiring' => $this->expiringMail($notifiable),
            'expired' => $this->expiredMail($notifiable),
        };
    }

    protected function activatedMail(object $notifiable): MailMessage
    {
        $enterprise = $this->subscription->enterprise;

        return (new MailMessage)
            ->subject($this->formatSubject('Abonnement activé'))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("🎉 Votre abonnement BestQHSE a été activé avec succès !")
            ->line("")
            ->line("**Détails de l'abonnement :**")
            ->line("- Offre : " . ($this->subscription->plan_name ?? 'Standard'))
            ->line("- Date d'activation : " . $this->subscription->start_date?->format('d/m/Y'))
            ->line("- Date d'expiration : " . $this->subscription->end_date?->format('d/m/Y'))
            ->line("- Entreprise : " . $enterprise?->name)
            ->line("")
            ->line("Vous avez maintenant accès à toutes les fonctionnalités de votre plan.")
            ->action('Accéder à la plateforme', $this->buildActionUrl('/dashboard'))
            ->line("Merci de votre confiance !")
            ->salutation($this->formatSalutation());
    }

    protected function expiringMail(object $notifiable): MailMessage
    {
        $daysRemaining = $this->additionalData['days_remaining'] ?? 0;
        $enterprise = $this->subscription->enterprise;

        return (new MailMessage)
            ->subject($this->formatSubject("⚠️ Votre abonnement expire dans {$daysRemaining} jour(s)"))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre abonnement BestQHSE arrive bientôt à expiration.")
            ->line("")
            ->line("**Détails :**")
            ->line("- Offre : " . ($this->subscription->plan_name ?? 'Standard'))
            ->line("- Date d'expiration : " . $this->subscription->end_date?->format('d/m/Y'))
            ->line("- Jours restants : **{$daysRemaining}**")
            ->line("")
            ->line("Pour continuer à bénéficier de nos services, pensez à renouveler votre abonnement.")
            ->action('Renouveler mon abonnement', $this->buildActionUrl('/subscription/renew'))
            ->line("Notre équipe reste à votre disposition pour toute question.")
            ->salutation($this->formatSalutation());
    }

    protected function expiredMail(object $notifiable): MailMessage
    {
        $enterprise = $this->subscription->enterprise;

        return (new MailMessage)
            ->subject($this->formatSubject('🔴 Votre abonnement a expiré'))
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre abonnement BestQHSE a expiré.")
            ->line("")
            ->line("**Détails :**")
            ->line("- Offre : " . ($this->subscription->plan_name ?? 'Standard'))
            ->line("- Date d'expiration : " . $this->subscription->end_date?->format('d/m/Y'))
            ->line("")
            ->line("⚠️ **Votre accès aux fonctionnalités est maintenant limité.**")
            ->line("")
            ->line("Pour réactiver votre compte et retrouver l'accès complet :")
            ->action('Renouveler maintenant', $this->buildActionUrl('/subscription/renew'))
            ->line("Notre équipe commerciale est disponible pour vous accompagner.")
            ->line("📧 Email : commercial@BestQHSE.com")
            ->line("📞 Tél : +229 XX XX XX XX")
            ->salutation($this->formatSalutation());
    }

    public function toArray(object $notifiable): array
    {
        $messages = [
            'activated' => "Abonnement activé : {$this->subscription->plan_name}",
            'expiring' => "Abonnement expire bientôt : {$this->subscription->plan_name}",
            'expired' => "Abonnement expiré : {$this->subscription->plan_name}",
        ];

        return $this->formatBaseArray(
            type: 'subscription_notification',
            entityId: $this->subscription->id,
            ref: "SUB-{$this->subscription->id}",
            url: $this->buildActionUrl('/subscription'),
            additionalData: array_merge([
                'event_type' => $this->eventType,
                'plan_name' => $this->subscription->plan_name,
                'start_date' => $this->subscription->start_date?->toISOString(),
                'end_date' => $this->subscription->end_date?->toISOString(),
                'message' => $messages[$this->eventType] ?? 'Notification abonnement',
            ], $this->additionalData)
        );
    }

    public function failed(object $notifiable, \Throwable $exception): void
    {
        $this->logNotificationFailed($notifiable, 'mail', $exception);
    }
}
