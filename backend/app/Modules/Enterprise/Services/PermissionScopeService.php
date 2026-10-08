<?php

namespace App\Modules\Enterprise\Services;

use App\Models\PermissionNormMapping;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionScopeService
{
    /**
     * Retourne toutes les permissions disponibles pour un site selon ses souscriptions
     */
    public function getAccessiblePermissionsForSite(int $siteId): Collection
    {
        $site = Site::find($siteId);
        if (!$site) {
            return collect();
        }

        $subscriptions = $site->getActiveSubscriptions();
        if ($subscriptions->isEmpty()) {
            return collect();
        }

        $subscribedNormIds = $subscriptions
            ->loadMissing('offer.norms')
            ->flatMap(fn($subscription) => $subscription->offer?->norms ?? collect())
            ->pluck('id')
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $activeCodes = collect();
        foreach ($subscriptions as $subscription) {
            $activeCodes = $activeCodes
                ->merge($subscription->getAccessibleModules()->pluck('code'))
                ->merge($subscription->getAccessibleSubModules()->pluck('code'))
                ->merge($subscription->getAccessibleSections()->pluck('code'));
        }

        $activeCodes = $activeCodes
            ->map(fn($code) => mb_strtolower(trim((string) $code)))
            ->filter()
            ->unique()
            ->values();

        $systemPrefixes = User::getSystemPermissionPrefixes();
        $allowedCodes = $activeCodes->merge($systemPrefixes)->unique()->values();

        $permissionIdsForSubscribedNorms = PermissionNormMapping::query()
            ->when(!empty($subscribedNormIds), fn($query) => $query->whereIn('norm_id', $subscribedNormIds))
            ->pluck('permission_id')
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $allPermissions = Permission::where('guard_name', 'web')->get();

        return $allPermissions->filter(function ($permission) use ($allowedCodes, $permissionIdsForSubscribedNorms) {
            $name = mb_strtolower(trim((string) $permission->name));
            if ($name === '') {
                return false;
            }

            $rootPermissions = ['sites.read', 'sites.create', 'sites.update', 'sites.delete', 'sites.manage', 'verify_documents', 'approve_documents'];
            if (in_array($name, $rootPermissions, true)) {
                return true;
            }

            if (!empty($permissionIdsForSubscribedNorms) && in_array((int) $permission->id, $permissionIdsForSubscribedNorms, true)) {
                return true;
            }

            $segments = explode('.', $name);
            $code = $segments[0] ?? '';

            if ($code === '') {
                return false;
            }

            if ($allowedCodes->contains($code)) {
                return true;
            }

            return false;
        })->values();
    }

    /**
     * Retourne les permissions effectives filtrées par souscription
     */
    public function getEffectivePermissions(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return Permission::where('guard_name', 'web')->get();
        }

        // Les permissions assignées via les rôles + directes
        $assignedPermissions = $user->getAllPermissions();

        if ($assignedPermissions->isEmpty()) {
            return collect();
        }

        // Si l'utilisateur est admin d'entreprise, on filtre par l'union des accès de tous ses sites
        if ($user->isEnterpriseAdmin()) {
            $siteIds = $user->enterprise->sites()->pluck('id');
            $accessibleForEnterprise = collect();
            foreach ($siteIds as $siteId) {
                $accessibleForEnterprise = $accessibleForEnterprise->merge($this->getAccessiblePermissionsForSite($siteId));
            }
            $accessibleForEnterprise = $accessibleForEnterprise->unique('id');

            return $assignedPermissions->filter(function ($permission) use ($accessibleForEnterprise) {
                return $accessibleForEnterprise->contains('id', $permission->id);
            })->values();
        }

        // Pour les autres utilisateurs (site_manager, lecteur, etc.), on filtre par leur site
        if ($user->site_id) {
            $accessibleForSite = $this->getAccessiblePermissionsForSite($user->site_id);

            return $assignedPermissions->filter(function ($permission) use ($accessibleForSite) {
                return $accessibleForSite->contains('id', $permission->id);
            })->values();
        }

        // S'il n'a pas de site et n'est pas admin, il n'a accès à rien
        return collect();
    }

    /**
     * Vérifie si un utilisateur a une permission (en tenant compte de la souscription)
     */
    public function canAccess(User $user, string $permissionName): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->getEffectivePermissions($user)->contains('name', $permissionName);
    }

    /**
     * Retourne les permissions qu'un admin peut attribuer (limité à ses souscriptions)
     */
    public function getAssignablePermissionsForAdmin(User $admin): Collection
    {
        if ($admin->isSuperAdmin()) {
            return Permission::where('guard_name', 'web')->get();
        }

        if ($admin->isEnterpriseAdmin()) {
            $siteIds = $admin->enterprise->sites()->pluck('id');
            $assignable = collect();
            foreach ($siteIds as $siteId) {
                $assignable = $assignable->merge($this->getAccessiblePermissionsForSite($siteId));
            }
            return $assignable->unique('id')->values();
        }

        if ($admin->isSiteManager() && $admin->site_id) {
            return $this->getAccessiblePermissionsForSite($admin->site_id);
        }

        return collect();
    }
}
