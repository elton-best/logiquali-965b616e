<?php

namespace App\Http\Resources;

use App\Helpers\PermissionHelper;
use App\Http\Resources\Base\JsonApiResource;
use Illuminate\Http\Request;

class UserResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        // RBAC pur : pas de permissions directes sur les utilisateurs.
        // Toutes les permissions viennent des rôles assignés.
        $directPermissionNames = collect();
        $rolePermissionNames   = $this->getRolePermissionNames()->values();

        // Les listes complètes de permissions ne sont retournées que :
        // - sur le profil propre de l'utilisateur connecté
        // - pour les admins (super_admin ou admin_entreprise)
        // Cela évite d'exposer la surface d'attaque complète dans les listes.
        $request        = request();
        $currentUser    = $request?->user();
        $isOwnProfile   = $currentUser && (int) $currentUser->id === (int) $this->id;
        $isAdmin        = $currentUser && ($currentUser->isSuperAdmin() || $currentUser->isEnterpriseAdmin());
        $includeFullPermissions = $isOwnProfile || $isAdmin;

        $effectivePermissionNamesAll = $this->getEffectivePermissionNames()->values();
        $activeScopedPermissionNamesAll = $this->getActiveScopedPermissionNames()->values();
        $effectivePermissionNames = $includeFullPermissions
            ? $effectivePermissionNamesAll
            : collect();
        $activeScopedPermissionNames = $includeFullPermissions
            ? $activeScopedPermissionNamesAll
            : collect();

        $directPermissionsCount      = 0;
        $rolePermissionsCount        = $rolePermissionNames->count();
        $effectivePermissionsCount   = $effectivePermissionNames->count();
        $activeScopedPermissionsCount = $activeScopedPermissionNames->count();
        $assignedActionsCount = $this->isCompanyUser() ? $this->getAssignedActionsCount() : 0;

        return [
            'ref' => $this->ref,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'position' => $this->job_title,
            'job_title' => $this->job_title,
            'start_date' => $this->start_date?->toDateString(),
            'photo_path' => $this->photo_path,
            'photo_url' => $this->photo_url,
            'signature_path' => $this->signature_path,
            'signature_url' => $this->signature_url,
            'signature_uploaded_at' => $this->signature_uploaded_at?->toISOString(),
            'user_type' => $this->user_type,
            'role' => $this->role,
            'role_names' => $this->getRoleNames()->values(),
            'is_active' => $this->is_active,
            'collaborator_approval_status' => $this->collaborator_approval_status,
            'collaborator_requested_by' => $this->collaborator_requested_by,
            'collaborator_requested_by_name' => $this->relationLoaded('collaboratorRequestedBy')
                ? $this->collaboratorRequestedBy?->name
                : null,
            'collaborator_requested_by_email' => $this->relationLoaded('collaboratorRequestedBy')
                ? $this->collaboratorRequestedBy?->email
                : null,
            'collaborator_requested_at' => $this->collaborator_requested_at?->toISOString(),
            'collaborator_approved_by' => $this->collaborator_approved_by,
            'collaborator_approved_by_name' => $this->relationLoaded('collaboratorApprovedBy')
                ? $this->collaboratorApprovedBy?->name
                : null,
            'collaborator_approved_at' => $this->collaborator_approved_at?->toISOString(),
            'collaborator_rejected_by' => $this->collaborator_rejected_by,
            'collaborator_rejected_by_name' => $this->relationLoaded('collaboratorRejectedBy')
                ? $this->collaboratorRejectedBy?->name
                : null,
            'collaborator_rejected_at' => $this->collaborator_rejected_at?->toISOString(),
            'collaborator_rejection_reason' => $this->collaborator_rejection_reason,
            'must_change_password' => $this->must_change_password,
            'password_changed_at' => $this->password_changed_at?->toISOString(),
            'site_id' => $this->site_id,
            'role_permissions' => $includeFullPermissions ? $rolePermissionNames->all() : [],
            'direct_permissions' => $directPermissionNames->all(),
            'effective_permissions' => $includeFullPermissions ? $effectivePermissionNames->all() : [],
            'active_scoped_permissions' => $includeFullPermissions ? $activeScopedPermissionNames->all() : [],
            'direct_permissions_count' => $directPermissionsCount,
            'role_permissions_count' => $rolePermissionsCount,
            'effective_permissions_count' => $effectivePermissionsCount,
            'active_scoped_permissions_count' => $activeScopedPermissionsCount,
            'assigned_actions_count' => $assignedActionsCount,
            'has_assigned_actions' => $assignedActionsCount > 0,
            'authz_enforce_active_scope' => PermissionHelper::isActiveScopeEnforcedForUser($this->resource),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'enterprise' => $this->when(
                $this->relationLoaded('enterprise') && $this->enterprise,
                new EnterpriseResource($this->enterprise),
            ),
            'site' => $this->when(
                $this->relationLoaded('site') && $this->site,
                new SiteResource($this->site),
            ),
            'roles' => $this->whenLoaded('roles', fn() => RoleResource::collection($this->roles)),
            // La relation permissions custom (table user_permissions) a été supprimée — RBAC pur.
        ];
    }
}
