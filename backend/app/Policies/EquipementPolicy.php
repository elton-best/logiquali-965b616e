<?php

namespace App\Policies;

use App\Helpers\PermissionHelper;
use App\Models\Equipement;
use App\Models\User;

class EquipementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return PermissionHelper::can($user, 'equipements.read');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Equipement $equipement): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!PermissionHelper::can($user, 'equipements.read')) {
            return false;
        }

        // Enterprise admin can view all in their enterprise
        if ($user->isEnterpriseAdmin()) {
            return $equipement->enterprise_id === $user->enterprise_id;
        }

        // Site users can only view their site's equipment
        return $equipement->site_id === $user->site_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return PermissionHelper::can($user, 'equipements.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Equipement $equipement): bool
    {
        if (!PermissionHelper::can($user, 'equipements.update')) {
            return false;
        }

        // Cannot update if equipment is under maintenance
        if ($equipement->statut === 'en_maintenance') {
            return false;
        }

        // Same ownership rules as view
        return $this->view($user, $equipement);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Equipement $equipement): bool
    {
        if (!PermissionHelper::can($user, 'equipements.delete')) {
            return false;
        }

        // Cannot delete if has active maintenances
        if ($equipement->maintenances()->where('statut', '!=', 'terminee')->exists()) {
            return false;
        }

        // Only enterprise admin or super admin can delete
        return ($user->isEnterpriseAdmin() || $user->isSuperAdmin()) &&
               $this->view($user, $equipement);
    }

    /**
     * Determine whether the user can manage maintenance for the equipment.
     */
    public function manageMaintenance(User $user, Equipement $equipement): bool
    {
        return PermissionHelper::can($user, 'maintenances.create') &&
               $this->view($user, $equipement);
    }
}
