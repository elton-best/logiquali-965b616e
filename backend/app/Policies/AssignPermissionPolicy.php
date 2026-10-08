<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionNormMappingService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Policy to authorize permission assignments
 * 
 * Rules:
 * 1. Only admins (admin_entreprise role) can assign permissions
 * 2. Can only assign permissions from subscribed norms
 * 3. Can only assign permissions for their own enterprise
 */
class AssignPermissionPolicy
{
    public function __construct(
        protected PermissionNormMappingService $permissionService
    ) {}

    /**
     * Check if user can assign a permission to a role
     */
    public function assign(User $user, Permission $permission, Role $role, int $normId): bool
    {
        // Only admin_entreprise can assign permissions
        if (!$user->hasRole('admin_entreprise')) {
            return false;
        }

        // Role must belong to user's enterprise
        if ($role->enterprise_id !== $user->site->enterprise_id) {
            return false;
        }

        // Permission must exist for the specified norm
        $result = $this->permissionService->validatePermissionAssignment($user, $permission->name, $normId);
        
        return $result['valid'];
    }

    /**
     * Check if user can revoke a permission from a role
     */
    public function revoke(User $user, Permission $permission, Role $role): bool
    {
        // Only admin_entreprise can revoke permissions
        if (!$user->hasRole('admin_entreprise')) {
            return false;
        }

        // Role must belong to user's enterprise
        if ($role->enterprise_id !== $user->site->enterprise_id) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can update a role (including permissions)
     */
    public function update(User $user, Role $role): bool
    {
        // Only admin_entreprise can update roles
        if (!$user->hasRole('admin_entreprise')) {
            return false;
        }

        // Role must belong to user's enterprise
        if ($role->enterprise_id !== $user->site->enterprise_id) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can view role's permissions
     */
    public function view(User $user, Role $role): bool
    {
        // Admin can view their own enterprise's roles
        if ($user->hasRole('admin_entreprise') && $role->enterprise_id === $user->site->enterprise_id) {
            return true;
        }

        return false;
    }
}
