<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EnterpriseAdminPermissionService
{
    public function __construct(
        private readonly RolePermissionBaselineService $baselineService
    ) {
    }

    /**
     * Permissions réservées au périmètre super-admin.
     *
     * @return array<int, string>
     */
    public function superAdminOnlyPermissions(): array
    {
        return $this->baselineService->superAdminOnlyPermissions();
    }

    /**
     * Permissions autorisées pour admin_entreprise.
     *
     * @return array<int, string>
     */
    public function enterprisePermissionNames(): array
    {
        return $this->baselineService->baselinePermissionNamesForRole('admin_entreprise');
    }

    /**
     * Synchronise le rôle admin_entreprise et purge les permissions directes
     * des utilisateurs portant ce rôle.
     *
     * @return array{role_permissions:int,users_cleaned:int}
     */
    public function sync(bool $purgeDirectPermissions = true): array
    {
        $role = Role::firstOrCreate([
            'name' => 'admin_entreprise',
            'guard_name' => 'web',
        ]);

        $permissionNames = collect($this->enterprisePermissionNames())
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->map(function (string $name): string {
                return Permission::query()->firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ])->name;
            })
            ->unique()
            ->values()
            ->all();

        $role->syncPermissions($permissionNames);

        $usersCleaned = 0;
        if ($purgeDirectPermissions) {
            $users = User::role('admin_entreprise')->get();
            foreach ($users as $user) {
                $this->clearDirectPermissionsForUser($user);
                $usersCleaned++;
            }
        }

        return [
            'role_permissions' => $role->permissions()->count(),
            'users_cleaned' => $usersCleaned,
        ];
    }

    public function clearDirectPermissionsForUser(User $user): void
    {
        $user->syncPermissions([]);
    }
}
