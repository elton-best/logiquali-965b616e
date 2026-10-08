<?php

namespace App\Console\Commands;

use App\Models\EnterpriseSubscription;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckSubscriptionExpiry extends Command
{
    protected $signature = 'subscriptions:check-expiry';
    protected $description = 'Check subscription expiry and send notifications';

    public function handle(): int
    {
        $this->info('Vérification des expirations d\'abonnements...');

        $now = Carbon::now();
        
        // Abonnements expirant dans 14, 7, 3 ou 1 jour
        $expiringDays = [14, 7, 3, 1];
        
        $totalNotifications = 0;
        
        foreach ($expiringDays as $days) {
            $expiringDate = $now->copy()->addDays($days)->startOfDay();
            
            $subscriptions = EnterpriseSubscription::where('is_active', true)
                ->whereDate('expiration_date', $expiringDate)
                ->with(['site.users', 'offer.norms'])
                ->get();

            foreach ($subscriptions as $subscription) {
                // Envoyer notification à tous les utilisateurs du site
                $users = $subscription->site->users;
                
                foreach ($users as $user) {
                    $user->notify(new SubscriptionExpiringNotification($subscription, $days));
                    $totalNotifications++;
                }
                
                $normNames = $subscription->offer->norms->pluck('name')->implode(', ');
                $this->line("⚠️  Expire dans {$days} jours: {$subscription->ref} ({$normNames})");
            }
        }

        // Abonnements expirés (désactiver)
        $expiredSubscriptions = EnterpriseSubscription::where('is_active', true)
            ->whereDate('expiration_date', '<', $now)
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update([
                'is_active' => false,
                'status' => 'expired',
            ]);
            
            $this->line("🔴 Expiré: {$subscription->ref}");
        }

        $this->info("✅ {$totalNotifications} notification(s) envoyée(s)");
        $this->info("✅ {$expiredSubscriptions->count()} abonnement(s) expiré(s)");

        return Command::SUCCESS;
    }
}

