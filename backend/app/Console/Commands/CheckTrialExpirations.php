<?php

namespace App\Console\Commands;

use App\Models\EnterpriseSubscription;
use App\Models\SubscriptionAlert;
use App\Notifications\Subscription\TrialExpiringNotification;
use App\Notifications\Subscription\TrialExpiredNotification;
use Illuminate\Console\Command;

class CheckTrialExpirations extends Command
{
    protected $signature = 'trial:check';
    protected $description = 'Vérifie les expirations de période d\'essai et envoie des alertes';

    public function handle(): int
    {
        $subscriptions = EnterpriseSubscription::where('is_trial', true)
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->get();

        $alertsSent = 0;

        foreach ($subscriptions as $subscription) {
            $daysRemaining = $subscription->daysRemaining();

            // Vérifier si une alerte doit être envoyée
            $alertType = $this->getAlertType($daysRemaining);
            
            if ($alertType) {
                // Vérifier si l'alerte n'a pas déjà été envoyée
                $alreadySent = SubscriptionAlert::where('subscription_id', $subscription->id)
                    ->where('alert_type', $alertType)
                    ->exists();

                if (!$alreadySent) {
                    $this->sendAlert($subscription, $alertType, $daysRemaining);
                    $alertsSent++;
                }
            }

            // Expirer la souscription si nécessaire
            if ($daysRemaining <= 0 && $subscription->status === 'trial') {
                $subscription->update(['status' => 'expired', 'is_active' => false]);
                $this->info("Souscription {$subscription->id} expirée");
            }
        }

        $this->info("Alertes envoyées: {$alertsSent}");
        return Command::SUCCESS;
    }

    private function getAlertType(int $daysRemaining): ?string
    {
        return match($daysRemaining) {
            30 => 'trial_30',
            15 => 'trial_15',
            7 => 'trial_7',
            3 => 'trial_3',
            1 => 'trial_1',
            0 => 'expired',
            default => null,
        };
    }

    private function sendAlert(EnterpriseSubscription $subscription, string $alertType, int $daysRemaining): void
    {
        $site = $subscription->site;
        $enterprise = $site->enterprise;

        // Récupérer admin entreprise et collaborateurs
        $users = $site->users()->get();

        foreach ($users as $user) {
            if ($alertType === 'expired') {
                $user->notify(new TrialExpiredNotification($subscription));
            } else {
                $user->notify(new TrialExpiringNotification($subscription, $daysRemaining));
            }
        }

        // Enregistrer l'alerte
        SubscriptionAlert::create([
            'subscription_id' => $subscription->id,
            'alert_type' => $alertType,
            'sent_at' => now(),
        ]);

        $this->info("Alerte {$alertType} envoyée pour souscription {$subscription->id}");
    }
}
