<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleScopeService
{
    public function applyRoleVisibilityScopeForUser(Builder $query, ?User $user): Builder
    {
        if ($user?->isSuperAdmin()) {
            return $query;
        }

        if ($user?->isCompanyUser() && (int) ($user->enterprise_id ?? 0) > 0) {
            $enterpriseId = (int) $user->enterprise_id;
            return $query->where(function ($roleQuery) use ($enterpriseId) {
                $roleQuery->where(function ($globalRoles) {
                    $globalRoles->whereNull('enterprise_id');
                })
                    ->orWhere('enterprise_id', $enterpriseId);
            });
        }

        return $query->whereNull('enterprise_id');
    }

    public function customRolePrefixForEnterprise(int $enterpriseId): string
    {
        return $enterpriseId > 0
            ? sprintf('custom_enterprise_%d_', $enterpriseId)
            : 'custom_enterprise_';
    }

    public function isAnyCustomEnterpriseRoleName(string $roleName): bool
    {
        return (bool) preg_match('/^custom_enterprise_\d+_/i', trim($roleName));
    }

    public function extractEnterpriseIdFromCustomRoleName(string $roleName): ?int
    {
        if (!preg_match('/^custom_enterprise_(\d+)_/i', trim($roleName), $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    public function isRoleScopedToEnterprise(?SpatieRole $role, string $roleName, int $enterpriseId): bool
    {
        if ($this->isRoleStrictlyScopedToEnterprise($role, $enterpriseId)) {
            return true;
        }

        if ($role !== null || $enterpriseId <= 0) {
            return false;
        }

        $roleModel = SpatieRole::query()
            ->where('name', trim($roleName))
            ->first();
        if (!$roleModel) {
            return false;
        }

        return $this->isRoleStrictlyScopedToEnterprise($roleModel, $enterpriseId);
    }

    public function isRoleStrictlyScopedToEnterprise(?SpatieRole $role, int $enterpriseId): bool
    {
        if ($enterpriseId <= 0) {
            return false;
        }

        $roleEnterpriseId = (int) ($role?->enterprise_id ?? 0);
        return $roleEnterpriseId > 0 && $roleEnterpriseId === $enterpriseId;
    }

    public function isRoleLegacyScopedToEnterprise(string $roleName, int $enterpriseId): bool
    {
        if ($enterpriseId <= 0) {
            return false;
        }

        $fromNameEnterpriseId = (int) ($this->extractEnterpriseIdFromCustomRoleName($roleName) ?? 0);
        if ($fromNameEnterpriseId > 0) {
            return $fromNameEnterpriseId === $enterpriseId;
        }

        return str_starts_with(
            mb_strtolower(trim($roleName)),
            mb_strtolower($this->customRolePrefixForEnterprise($enterpriseId))
        );
    }

    public function isLegacyCustomRoleWithoutEnterpriseScope(?SpatieRole $role, ?string $roleName = null): bool
    {
        $resolvedRoleName = (string) ($roleName ?? $role?->name ?? '');
        if (!$this->isAnyCustomEnterpriseRoleName($resolvedRoleName)) {
            return false;
        }

        return (int) ($role?->enterprise_id ?? 0) <= 0;
    }

    public function resolveRoleEnterpriseScope(?User $actor, string $roleName): ?int
    {
        if ($actor?->isCompanyUser()) {
            $actorEnterpriseId = (int) ($actor->enterprise_id ?? 0);
            return $actorEnterpriseId > 0 ? $actorEnterpriseId : null;
        }

        return null;
    }
}
