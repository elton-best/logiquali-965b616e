<?php

namespace App\Modules\Enterprise\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionBaselineService
{
    /**
     * @return array<int, string>
     */
    public function superAdminOnlyPermissions(): array
    {
        return (array) config('role_baselines.super_admin_only_permissions', []);
    }

    /**
     * @return array<int, string>
     */
    public function isoExtensionPermissions(): array
    {
        return (array) config('role_baselines.iso_extension_permissions', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function roleDefinitions(): array
    {
        return (array) config('role_baselines.roles', []);
    }

    /**
     * @return array<int, string>
     */
    public function roleNames(): array
    {
        return array_keys($this->roleDefinitions());
    }

    /**
     * @return array<int, string>
     */
    public function baselinePermissionNamesForRole(string $roleName): array
    {
        $definitions = $this->roleDefinitions();
        $definition = $definitions[$roleName] ?? [];
        $strategy = (string) ($definition['strategy'] ?? 'explicit');

        if ($strategy === 'all') {
            return Permission::query()->pluck('name')->all();
        }

        if ($strategy === 'all_except_super_admin_only' || $strategy === 'all_subscribed' || $strategy === 'all_subscribed_for_site') {
            $permissions = Permission::query()
                ->whereNotIn('name', $this->superAdminOnlyPermissions())
                ->pluck('name');

            if ($roleName === 'admin_entreprise') {
                $permissions = $permissions->merge([
                    'roles.create',
                    'roles.update',
                    'roles.delete',
                ]);
            }

            if ($roleName === 'site_manager') {
                $permissions = $permissions->reject(fn($permissionName) => in_array((string) $permissionName, ['roles.create', 'roles.update', 'roles.delete'], true));
            }

            return $permissions->unique()->values()->all();
        }

        if ($strategy === 'read_only_subscribed') {
            $permissions = Permission::query()
                ->whereNotIn('name', $this->superAdminOnlyPermissions())
                ->pluck('name')
                ->filter(function ($name) {
                    return str_ends_with($name, '.read')
                        || in_array($name, ['verify_documents', 'approve_documents', 'sites.read', 'norm_library.read', 'dashboard.read']);
                });

            return $permissions->unique()->values()->all();
        }

        $permissions = collect((array) ($definition['permissions'] ?? []));
        if ((bool) ($definition['include_iso_extensions'] ?? false)) {
            $permissions = $permissions->merge($this->isoExtensionPermissions());
        }

        $prefixes = collect((array) ($definition['include_prefixes'] ?? []))
            ->filter(fn($prefix) => is_string($prefix) && trim($prefix) !== '')
            ->values();
        $actions = collect((array) ($definition['include_actions'] ?? []))
            ->filter(fn($action) => is_string($action) && trim($action) !== '')
            ->map(fn($action) => mb_strtolower(trim($action)))
            ->values();
        $excludePrefixes = collect((array) ($definition['exclude_prefixes'] ?? []))
            ->filter(fn($prefix) => is_string($prefix) && trim($prefix) !== '')
            ->values();
        $excludePermissions = collect((array) ($definition['exclude_permissions'] ?? []))
            ->filter(fn($permission) => is_string($permission) && trim($permission) !== '')
            ->values();

        if ($prefixes->isNotEmpty()) {
            $allPermissionNames = Permission::query()->pluck('name');
            foreach ($prefixes as $prefix) {
                $permissions = $permissions->merge(
                    $allPermissionNames->filter(fn($permission) => str_starts_with((string) $permission, $prefix))
                );
            }
        }

        if ($actions->isNotEmpty()) {
            $permissions = $permissions->filter(function ($permission) use ($actions) {
                $name = mb_strtolower(trim((string) $permission));
                if ($name === '') {
                    return false;
                }

                $parts = explode('.', $name);
                $action = end($parts) ?: '';
                return $actions->contains($action);
            });
        }

        if ($excludePrefixes->isNotEmpty()) {
            $permissions = $permissions->filter(function ($permission) use ($excludePrefixes) {
                $name = (string) $permission;
                foreach ($excludePrefixes as $prefix) {
                    if (str_starts_with($name, $prefix)) {
                        return false;
                    }
                }
                return true;
            });
        }

        if ($excludePermissions->isNotEmpty()) {
            $permissions = $permissions->reject(fn($permission) => $excludePermissions->contains((string) $permission));
        }

        return $permissions
            ->filter(fn($permission) => is_string($permission) && trim($permission) !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{role_permissions:int}
     */
    public function syncRole(string $roleName): array
    {
        $role = $this->resolveCanonicalRole($roleName);

        $permissionNames = $this->baselinePermissionNamesForRole($roleName);
        if (!empty($permissionNames)) {
            $syncedPermissionNames = collect($permissionNames)
                ->map(function (string $permissionName): string {
                    return Permission::query()->firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                    ])->name;
                })
                ->unique()
                ->all();
            $role->syncPermissions($syncedPermissionNames);
        } else {
            $role->syncPermissions([]);
        }

        return [
            'role_permissions' => $role->permissions()->count(),
        ];
    }

    protected function resolveCanonicalRole(string $roleName): Role
    {
        $roles = Role::withoutGlobalScopes()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->orderBy('id')
            ->get();

        if ($roles->isEmpty()) {
            return Role::query()->create([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        $canonical = $roles->first();

        if ($roles->count() > 1) {
            foreach ($roles->slice(1) as $duplicateRole) {
                DB::table('model_has_roles')
                    ->where('role_id', $duplicateRole->id)
                    ->update(['role_id' => $canonical->id]);

                $duplicateRole->permissions()->sync([]);
                $duplicateRole->delete();
            }
        }

        if (array_key_exists('deleted_at', $canonical->getAttributes()) && $canonical->getAttribute('deleted_at') !== null) {
            $canonical->forceFill(['deleted_at' => null])->save();
        }

        return $canonical;
    }

    /**
     * @return array{
     *   roles: array<string, int>,
     *   admin_users_direct_permissions_cleared: int
     * }
     */
    public function syncAll(bool $purgeEnterpriseAdminDirectPermissions = true): array
    {
        $counts = [];
        foreach ($this->roleNames() as $roleName) {
            $result = $this->syncRole($roleName);
            $counts[$roleName] = $result['role_permissions'];
        }

        $usersCleaned = 0;
        if ($purgeEnterpriseAdminDirectPermissions) {
            $users = User::role('admin_entreprise')->get();
            foreach ($users as $user) {
                $user->syncPermissions([]);
                $usersCleaned++;
            }
        }

        return [
            'roles' => $counts,
            'admin_users_direct_permissions_cleared' => $usersCleaned,
        ];
    }
}
