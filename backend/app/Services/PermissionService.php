<?php

namespace App\Services;

use App\Models\Module;
use App\Models\User;

class PermissionService
{
    public function grantDefaultPermissions(User $user, array $moduleIds): void
    {
        foreach ($moduleIds as $moduleId) {
            $module = Module::find($moduleId);
            if ($module) {
                $user->givePermissionTo("{$module->code}.read");
            }
        }
    }

    public function grantFullPermissions(User $user, array $moduleIds): void
    {
        $actions = ['read', 'create', 'update', 'delete', 'validate'];
        
        foreach ($moduleIds as $moduleId) {
            $module = Module::find($moduleId);
            if ($module) {
                foreach ($actions as $action) {
                    $user->givePermissionTo("{$module->code}.{$action}");
                }
            }
        }
    }

    public function revokeModulePermissions(User $user, array $moduleIds, array $except = ['read']): void
    {
        $allActions = ['read', 'create', 'update', 'delete', 'validate'];
        $actionsToRevoke = array_diff($allActions, $except);
        
        foreach ($moduleIds as $moduleId) {
            $module = Module::find($moduleId);
            if ($module) {
                foreach ($actionsToRevoke as $action) {
                    $permission = "{$module->code}.{$action}";
                    if ($user->hasPermissionTo($permission)) {
                        $user->revokePermissionTo($permission);
                    }
                }
            }
        }
    }

    public function syncUserPermissions(User $user, array $permissions): void
    {
        $user->syncPermissions($permissions);
    }

    public function getUserModulePermissions(User $user, string $moduleIdentifier): array
    {
        $actions = ['read', 'create', 'update', 'delete', 'validate'];
        $permissions = [];

        foreach ($actions as $action) {
            $permission = "{$moduleIdentifier}.{$action}";
            $permissions[$action] = $user->hasPermissionTo($permission);
        }

        return $permissions;
    }
}
