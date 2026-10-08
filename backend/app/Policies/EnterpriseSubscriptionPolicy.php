<?php

namespace App\Policies;

use App\Helpers\PermissionHelper;
use App\Models\EnterpriseSubscription;
use App\Models\User;

class EnterpriseSubscriptionPolicy
{
    /**
     * Determine if the user can view any subscriptions.
     * - Admin entreprise: peut voir les abonnements de son entreprise
     * - Collaborateur: seulement si permission 'subscriptions.read'
     */
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isEnterpriseAdmin()) {
            return true;
        }

        return PermissionHelper::can($user, 'subscriptions.read');
    }

    /**
     * Determine if the user can view the subscription.
     */
    public function view(User $user, EnterpriseSubscription $subscription): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->enterprise_id === (int) $subscription->enterprise_id
            && PermissionHelper::can($user, 'subscriptions.read');
    }

    /**
     * Determine if the user can create subscriptions.
     * Seuls les super admins peuvent créer des abonnements.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can update the subscription.
     * Seuls les super admins peuvent modifier des abonnements.
     */
    public function update(User $user, EnterpriseSubscription $subscription): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can delete the subscription.
     * Seuls les super admins peuvent supprimer des abonnements.
     */
    public function delete(User $user, EnterpriseSubscription $subscription): bool
    {
        return $user->isSuperAdmin();
    }
}
