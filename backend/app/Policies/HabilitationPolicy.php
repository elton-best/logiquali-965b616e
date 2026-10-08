<?php

namespace App\Policies;

use App\Helpers\PermissionHelper;
use App\Models\Habilitation;
use App\Models\User;

class HabilitationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return PermissionHelper::can($user, 'habilitations.read');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Habilitation $habilitation): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!PermissionHelper::can($user, 'habilitations.read')) {
            return false;
        }

        // Enterprise admin can view all in their enterprise
        if ($user->isEnterpriseAdmin()) {
            return $habilitation->enterprise_id === $user->enterprise_id;
        }

        // Users can view their own habilitations
        if ($habilitation->user_id === $user->id) {
            return true;
        }

        // Site managers can view habilitations in their site
        if ($user->isSiteManager()) {
            return $habilitation->site_id === $user->site_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return PermissionHelper::can($user, 'habilitations.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Habilitation $habilitation): bool
    {
        if (!PermissionHelper::can($user, 'habilitations.update')) {
            return false;
        }

        // Cannot update expired habilitations (must renew instead)
        if ($habilitation->status === 'expired') {
            return false;
        }

        // Users cannot update their own habilitations
        if ($habilitation->user_id === $user->id) {
            return false;
        }

        // Same ownership rules as view (excluding self)
        return $this->view($user, $habilitation) && $habilitation->user_id !== $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Habilitation $habilitation): bool
    {
        if (!PermissionHelper::can($user, 'habilitations.delete')) {
            return false;
        }

        // Cannot delete active habilitations
        if ($habilitation->status === 'active') {
            return false;
        }

        // Only enterprise/site managers can delete (within their scope)
        return ($user->isSiteManager() || $user->isEnterpriseAdmin() || $user->isSuperAdmin()) &&
               $this->view($user, $habilitation);
    }

    /**
     * Determine whether the user can renew the habilitation.
     */
    public function renew(User $user, Habilitation $habilitation): bool
    {
        if (!PermissionHelper::can($user, 'habilitations.update')) {
            return false;
        }

        // Can only renew expired or expiring habilitations
        if (!in_array($habilitation->status, ['expired', 'active'])) {
            return false;
        }

        // Must be able to view the habilitation
        return $this->view($user, $habilitation);
    }

    /**
     * Determine whether the user can suspend the habilitation.
     */
    public function suspend(User $user, Habilitation $habilitation): bool
    {
        if (!PermissionHelper::can($user, 'habilitations.update')) {
            return false;
        }

        // Can only suspend active habilitations
        if ($habilitation->status !== 'active') {
            return false;
        }

        // Only managers can suspend
        return ($user->isSiteManager() || $user->isEnterpriseAdmin() || $user->isSuperAdmin()) &&
               $this->view($user, $habilitation);
    }

    /**
     * Determine whether the user can export habilitations.
     */
    public function export(User $user): bool
    {
        return PermissionHelper::can($user, 'habilitations.manage');
    }

    /**
     * Determine whether the user can view expiring alerts.
     */
    public function viewExpiringAlerts(User $user): bool
    {
        return PermissionHelper::can($user, 'habilitations.read')
            && ($user->isSiteManager() || $user->isEnterpriseAdmin() || $user->isSuperAdmin());
    }
}
