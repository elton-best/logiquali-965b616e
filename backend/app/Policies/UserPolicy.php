<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users (collaborators).
     * - Admin entreprise: peut voir tous les collaborateurs de son entreprise
     * - Collaborateur: seulement si permission 'personnel.read'
     */
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isCompanyUser()) {
            return false;
        }

        // Admin d'entreprise: accès complet aux utilisateurs de son entreprise
        if ($user->isEnterpriseAdmin()) {
            return true;
        }

        // Responsable de site: accès à la liste (scope site appliqué dans le contrôleur)
        if ($user->isSiteManager()) {
            return true;
        }

        // Autres profils QHSE: permission explicite requise
        return $user->can('personnel.read');
    }

    /**
     * Determine if the user can view the other user.
     */
    public function view(User $user, User $targetUser): bool
    {
        // Un utilisateur peut toujours se voir lui-même
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Super admin peut tout voir
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isCompanyUser()) {
            return false;
        }

        // Isolation multi-tenant stricte
        if ($user->enterprise_id !== $targetUser->enterprise_id) {
            return false;
        }

        // Admin entreprise: accès complet à son entreprise
        if ($user->isEnterpriseAdmin()) {
            return true;
        }

        // Responsable de site: accès uniquement aux utilisateurs de son site
        if ($user->isSiteManager()) {
            return $user->site_id && $user->site_id === $targetUser->site_id;
        }

        // Autres profils QHSE: même entreprise + permission explicite
        return $user->can('personnel.read');
    }

    /**
     * Determine if the user can create users (collaborators).
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isCompanyUser()) {
            return false;
        }

        if ($user->isEnterpriseAdmin()) {
            return true;
        }

        return $user->can('personnel.create');
    }

    /**
     * Determine if the user can update the other user.
     */
    public function update(User $user, User $targetUser): bool
    {
        // Un utilisateur peut se modifier lui-même (profil)
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Super admin peut tout modifier
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isCompanyUser()) {
            return false;
        }

        // Isolation multi-tenant stricte
        if ($user->enterprise_id !== $targetUser->enterprise_id) {
            return false;
        }

        // Admin entreprise: peut modifier n'importe quel utilisateur de son entreprise
        if ($user->isEnterpriseAdmin()) {
            return true;
        }

        // Non-admin ne peut pas modifier un admin entreprise
        if ($targetUser->isEnterpriseAdmin()) {
            return false;
        }

        // Responsable de site: uniquement utilisateurs de son site + permission explicite
        if ($user->isSiteManager()) {
            return $user->can('personnel.update')
                && $user->site_id
                && $user->site_id === $targetUser->site_id;
        }

        // Autres profils QHSE: permission explicite + même entreprise
        return $user->can('personnel.update');
    }

    /**
     * Determine if the user can delete the other user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // On ne peut pas se supprimer soi-même
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Super admin peut tout supprimer
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isCompanyUser()) {
            return false;
        }

        // Isolation multi-tenant stricte
        if ($user->enterprise_id !== $targetUser->enterprise_id) {
            return false;
        }

        // Admin entreprise: peut supprimer n'importe quel utilisateur de son entreprise (sauf lui-même)
        if ($user->isEnterpriseAdmin()) {
            return true;
        }

        // Non-admin ne peut pas supprimer un admin entreprise
        if ($targetUser->isEnterpriseAdmin()) {
            return false;
        }

        // Responsable de site: uniquement utilisateurs de son site + permission explicite
        if ($user->isSiteManager()) {
            return $user->can('personnel.delete')
                && $user->site_id
                && $user->site_id === $targetUser->site_id;
        }

        // Autres profils QHSE: permission explicite + même entreprise
        return $user->can('personnel.delete');
    }

    /**
     * Determine if the user can assign permissions to other users.
     * Seuls les admins entreprise peuvent assigner des permissions aux collaborateurs.
     */
    public function assignPermissions(User $user, User $targetUser): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->isCompanyUser()) {
            return false;
        }

        return $user->isEnterpriseAdmin()
            && $user->enterprise_id === $targetUser->enterprise_id;
    }
}
