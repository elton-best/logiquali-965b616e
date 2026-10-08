<?php

namespace App\Notifications\Subscription;

use App\Models\EnterpriseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingCompletedNotification extends Notification
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
            ->subject('[BestQHSE] Bienvenue ! Votre période d\'essai a commencé')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('🎉 Félicitations ! Votre compte BestQHSE est maintenant actif.')
            ->line('')
            ->line('**Votre période d\'essai gratuite de 90 jours a démarré.**')
            ->line('')
            ->line('Vous avez accès à toutes les fonctionnalités de votre abonnement jusqu\'au ' . $this->subscription->trial_ends_at->format('d/m/Y') . '.')
            ->line('')
            ->line('**Prochaines étapes :**')
            ->line('• Explorez votre tableau de bord')
            ->line('• Configurez votre premier site')
            ->line('• Invitez vos collaborateurs')
            ->line('• Découvrez nos modules ISO')
            ->action('Accéder au tableau de bord', url('/company/dashboard'))
            ->line('')
            ->line('Notre équipe reste à votre disposition pour vous accompagner.')
            ->salutation('Cordialement,\nL\'équipe BestQHSE');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'onboarding_completed',
            'subscription_id' => $this->subscription->id,
            'trial_ends_at' => $this->subscription->trial_ends_at->format('Y-m-d'),
        ];
    }
}
