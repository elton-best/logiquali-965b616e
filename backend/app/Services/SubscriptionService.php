<?php

namespace App\Services;

use App\Helpers\SubscriptionHelper;
use App\Models\EnterpriseSubscription;
use Carbon\Carbon;

class SubscriptionService
{
    public const TRIAL_DAYS = 90;

    public function createTrialSubscription($site, $offer, $isPrimary = true): EnterpriseSubscription
    {
        $trialEndsAt = Carbon::now()->addDays(self::TRIAL_DAYS);

        $subscription = EnterpriseSubscription::create([
            'site_id' => $site->id,
            'offer_id' => $offer->id,
            'start_date' => now(),
            'expiration_date' => $trialEndsAt,
            'is_active' => true,
            'is_trial' => true,
            'trial_ends_at' => $trialEndsAt,
            'status' => 'trial',
            'payment_status' => null,
            'subscription_type' => $isPrimary ? 'primary' : 'addon',
        ]);

        return $subscription;
    }

    public function upgradeSubscription(EnterpriseSubscription $subscription, $newOffer): void
    {
        $oldSubModules = $subscription->getAccessibleSubModules()->pluck('id')->toArray();
        
        $subscription->update([
            'offer_id' => $newOffer->id,
            'is_trial' => false,
            'status' => 'active',
            'payment_status' => 'completed',
        ]);

        $newSubModules = $subscription->fresh()->getAccessibleSubModules()->pluck('id')->toArray();
        $addedSubModules = array_diff($newSubModules, $oldSubModules);

        if (!empty($addedSubModules)) {
            $this->syncCollaboratorPermissions($subscription, $addedSubModules, 'add');
        }

        // Dispatch event
        event(new \App\Events\SubscriptionUpdated($subscription, 'upgrade'));
    }

    public function downgradeSubscription(EnterpriseSubscription $subscription, $newOffer): void
    {
        $oldSubModules = $subscription->getAccessibleSubModules()->pluck('id')->toArray();
        
        $subscription->update(['offer_id' => $newOffer->id]);

        $newSubModules = $subscription->fresh()->getAccessibleSubModules()->pluck('id')->toArray();
        $removedSubModules = array_diff($oldSubModules, $newSubModules);

        if (!empty($removedSubModules)) {
            $this->syncCollaboratorPermissions($subscription, $removedSubModules, 'remove');
        }

        // Dispatch event
        event(new \App\Events\SubscriptionUpdated($subscription, 'downgrade'));
    }

    public function syncCollaboratorPermissions(EnterpriseSubscription $subscription, array $subModuleIds, string $action): void
    {
        // Moteur unifié: les permissions effectives sont désormais dérivées dynamiquement
        // (RBAC + abonnement actif), sans écriture de permissions directes implicites.
        // Méthode conservée pour compatibilité des listeners/flows existants.
    }

    public function cancelSubscription(EnterpriseSubscription $subscription): void
    {
        $subscription->update([
            'is_active' => false,
            'status' => 'cancelled',
        ]);
    }

    public function renewSubscription(EnterpriseSubscription $subscription, $offer = null): array
    {
        $offer = $offer ?? $subscription->offer;
        $expiredDate = Carbon::parse($subscription->expiration_date);
        $renewDate = Carbon::now();
        
        // Calculer mois dus si renouvellement en retard
        if ($renewDate->gt($expiredDate)) {
            $calculation = SubscriptionHelper::calculateMonthsDue(
                $expiredDate,
                $renewDate,
                $offer->price / $offer->duration_months
            );
            
            $subscription->update([
                'start_date' => $calculation['new_start_date'],
                'expiration_date' => $calculation['new_expiration_date'],
                'is_active' => true,
                'is_trial' => false,
                'status' => 'active',
                'payment_status' => 'completed',
                'amount_paid' => $calculation['amount'],
            ]);
            
            return $calculation;
        }
        
        // Renouvellement avant expiration
        $subscription->update([
            'expiration_date' => $expiredDate->copy()->addMonths($offer->duration_months),
            'is_active' => true,
            'is_trial' => false,
            'status' => 'active',
            'payment_status' => 'completed',
            'amount_paid' => $offer->price,
        ]);
        
        return [
            'months_due' => $offer->duration_months,
            'amount' => $offer->price,
            'new_expiration_date' => $subscription->expiration_date,
        ];
    }

}
