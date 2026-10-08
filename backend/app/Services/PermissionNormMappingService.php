<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Norm;
use App\Models\PermissionNormMapping;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Spatie\Permission\Models\Permission;

/**
 * Service to query and manage permission-norm mappings.
 * Centralizes all logic for understanding which permissions belong to which norms/modules.
 */
class PermissionNormMappingService
{
    /**
     * Resolve subscribed norm IDs for a user's current site context.
     */
    public function getSubscribedNormIdsForUser(User $user, ?int $siteId = null): array
    {
        $site = $this->resolveScopedSiteForUser($user, $siteId);
        if (!$site) {
            return [];
        }

        return $site->getActiveSubscriptions()
            ->loadMissing('offer.norms:id')
            ->flatMap(fn ($subscription) => $subscription->offer?->norms ?? collect())
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get all permissions available for a specific norm
     */
    public function getPermissionsForNorm(int $normId): Collection
    {
        $mappingIds = PermissionNormMapping::where('norm_id', $normId)
            ->pluck('permission_id')
            ->toArray();

        return Permission::whereIn('id', $mappingIds)
            ->get();
    }

    /**
     * Get all permissions available for a specific module
     */
    public function getPermissionsForModule(int $moduleId): Collection
    {
        $mappingIds = PermissionNormMapping::where('module_id', $moduleId)
            ->pluck('permission_id')
            ->toArray();

        return Permission::whereIn('id', $mappingIds)
            ->get();
    }

    /**
     * Get permissions for a specific norm + module combination
     */
    public function getPermissionsForNormModule(int $normId, int $moduleId): Collection
    {
        $mappingIds = PermissionNormMapping::where('norm_id', $normId)
            ->where('module_id', $moduleId)
            ->pluck('permission_id')
            ->toArray();

        return Permission::whereIn('id', $mappingIds)
            ->get();
    }

    /**
     * P1 start: get permissions for norm + sub-module.
     */
    public function getPermissionsForNormSubModule(int $normId, int $subModuleId): Collection
    {
        $mappingIds = PermissionNormMapping::where('norm_id', $normId)
            ->where('sub_module_id', $subModuleId)
            ->pluck('permission_id')
            ->toArray();

        return Permission::whereIn('id', $mappingIds)->get();
    }

    /**
     * P1 start: get permissions for norm + section.
     */
    public function getPermissionsForNormSection(int $normId, int $sectionId): Collection
    {
        $mappingIds = PermissionNormMapping::where('norm_id', $normId)
            ->where('section_id', $sectionId)
            ->pluck('permission_id')
            ->toArray();

        return Permission::whereIn('id', $mappingIds)->get();
    }

    /**
     * Check if a permission is shared across multiple norms
     */
    public function isPermissionShared(int $permissionId): bool
    {
        return PermissionNormMapping::where('permission_id', $permissionId)
            ->where('is_shared', true)
            ->exists();
    }

    /**
     * Get all norms where a permission exists
     */
    public function getNormsForPermission(int $permissionId): Collection
    {
        $normIds = PermissionNormMapping::where('permission_id', $permissionId)
            ->pluck('norm_id')
            ->toArray();

        return Norm::whereIn('id', $normIds)->get();
    }

    /**
     * Validate that a user can be assigned a permission for a specific norm
     * 
     * Rules:
     * 1. The permission must exist in the norm
     * 2. If the permission is shared, it applies to ALL subscribed norms
     * 3. If the permission is norm-specific, it can only be assigned if that norm is subscribed
     */
    public function validatePermissionAssignment(User $user, string $permissionName, int $normId): array
    {
        $permission = Permission::where('name', $permissionName)
            ->where('guard_name', 'web')
            ->first();

        if (!$permission) {
            return [
                'valid' => false,
                'error' => "Permission '{$permissionName}' does not exist",
            ];
        }

        $mapping = PermissionNormMapping::where('permission_id', $permission->id)
            ->where('norm_id', $normId)
            ->first();

        if (!$mapping) {
            return [
                'valid' => false,
                'error' => "Permission '{$permissionName}' does not exist for norm ID {$normId}",
            ];
        }

        $subscribedNormIds = $this->getSubscribedNormIdsForUser($user);
        if (!in_array($normId, $subscribedNormIds, true)) {
            $message = $mapping->is_shared
                ? "Your site does not subscribe to norm ID {$normId}"
                : "Your site does not subscribe to norm ID {$normId}. Permission '{$permissionName}' is specific to this norm.";

            return [
                'valid' => false,
                'error' => $message,
            ];
        }

        return [
            'valid' => true,
            'permission' => $permission,
            'mapping' => $mapping,
        ];
    }

    /**
     * Get all VALID permissions for a user based on their site's subscriptions
     * 
     * This is the PRIMARY method for filtering permissions shown in the UI
     */
    public function getActivePermissionsForUser(User $user, ?int $siteId = null): Collection
    {
        $subscribedNormIds = $this->getSubscribedNormIdsForUser($user, $siteId);

        if (empty($subscribedNormIds)) {
            return new Collection();
        }

        // Get permissions that exist for ANY of these norms
        $permissionIds = PermissionNormMapping::whereIn('norm_id', $subscribedNormIds)
            ->pluck('permission_id')
            ->unique()
            ->toArray();

        return Permission::whereIn('id', $permissionIds)->get();
    }

    /**
     * Return active permissions plus scoped mapping metadata for UI/API projection.
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function getActivePermissionsWithMappingsForUser(User $user, ?int $siteId = null): SupportCollection
    {
        $subscribedNormIds = $this->getSubscribedNormIdsForUser($user, $siteId);
        if (empty($subscribedNormIds)) {
            return collect();
        }

        $permissions = $this->getActivePermissionsForUser($user, $siteId)->keyBy('id');
        if ($permissions->isEmpty()) {
            return collect();
        }

        $mappings = PermissionNormMapping::query()
            ->whereIn('permission_id', $permissions->keys())
            ->whereIn('norm_id', $subscribedNormIds)
            ->with([
                'module:id,code,name',
                'subModule:id,module_id,code,name',
                'section:id,sub_module_id,code,name',
                'norm:id,code,name',
            ])
            ->get()
            ->groupBy('permission_id');

        return $permissions->map(function (Permission $permission) use ($mappings) {
            $rows = $mappings->get($permission->id, collect());

            return [
                'permission' => $permission,
                'mappings' => $rows->values(),
                'is_shared' => $rows->contains(fn ($row) => (bool) $row->is_shared),
            ];
        })->values();
    }

    /**
     * Get shared permissions (those that apply to ALL norms)
     */
    public function getSharedPermissions(): Collection
    {
        $permissionIds = PermissionNormMapping::where('is_shared', true)
            ->pluck('permission_id')
            ->unique()
            ->toArray();

        return Permission::whereIn('id', $permissionIds)->get();
    }

    /**
     * Get norm-specific permissions (those that apply to only one norm)
     */
    public function getSpecificPermissions(): Collection
    {
        $permissionIds = PermissionNormMapping::where('is_shared', false)
            ->pluck('permission_id')
            ->unique()
            ->toArray();

        return Permission::whereIn('id', $permissionIds)->get();
    }

    /**
     * Get the mapping information for a permission + norm combo
     */
    public function getMapping(int $permissionId, int $normId): ?PermissionNormMapping
    {
        return PermissionNormMapping::where('permission_id', $permissionId)
            ->where('norm_id', $normId)
            ->with(['permission', 'norm', 'module'])
            ->first();
    }

    private function resolveScopedSiteForUser(User $user, ?int $siteId = null): ?Site
    {
        if ($siteId && $siteId > 0) {
            $query = Site::query()->whereKey($siteId);

            if (!$user->isSuperAdmin()) {
                $query->where('enterprise_id', (int) $user->enterprise_id);
            }

            $scopedSite = $query->first();
            if ($scopedSite) {
                return $scopedSite;
            }
        }

        return $user->site;
    }
}
