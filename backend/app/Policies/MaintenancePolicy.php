<?php

namespace App\Policies;

use App\Helpers\PermissionHelper;
use App\Models\Maintenance;
use App\Models\User;

class MaintenancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return PermissionHelper::can($user, 'maintenances.read');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Maintenance $maintenance): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!PermissionHelper::can($user, 'maintenances.read')) {
            return false;
        }

        // Enterprise admin can view all in their enterprise
        if ($user->isEnterpriseAdmin()) {
            return $maintenance->equipement->enterprise_id === $user->enterprise_id;
        }

        // Site users can only view their site's maintenances
        return $maintenance->equipement->site_id === $user->site_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return PermissionHelper::can($user, 'maintenances.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Maintenance $maintenance): bool
    {
        if (!PermissionHelper::can($user, 'maintenances.update')) {
            return false;
        }

        // Cannot update if maintenance is completed
        if ($maintenance->statut === 'realise') {
            return false;
        }

        // Same ownership rules as view
        return $this->view($user, $maintenance);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Maintenance $maintenance): bool
    {
        if (!PermissionHelper::can($user, 'maintenances.delete')) {
            return false;
        }

        // Cannot delete if maintenance is in progress or completed
        if (in_array($maintenance->statut, ['en_cours', 'realise'])) {
            return false;
        }

        // Only enterprise admin or super admin can delete
        return ($user->isEnterpriseAdmin() || $user->isSuperAdmin()) &&
               $this->view($user, $maintenance);
    }

    /**
     * Determine whether the user can follow up on maintenance.
     */
    public function suivre(User $user, Maintenance $maintenance): bool
    {
        if (!PermissionHelper::can($user, 'maintenances.update')) {
            return false;
        }

        // Cannot follow up if already completed
        if ($maintenance->statut === 'realise') {
            return false;
        }

        // Must be able to view the maintenance
        return $this->view($user, $maintenance);
    }

    /**
     * Determine whether the user can export maintenances.
     */
    public function export(User $user): bool
    {
        return PermissionHelper::can($user, 'maintenances.manage');
    }

    /**
     * Determine whether the user can import maintenances.
     */
    public function import(User $user): bool
    {
        return PermissionHelper::can($user, 'maintenances.create')
            && ($user->isEnterpriseAdmin() || $user->isSuperAdmin());
    }
}
