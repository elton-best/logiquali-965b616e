<?php

namespace App\Services\Access;

use App\Models\User;

class AccessDecisionService
{
    /**
     * @return array<string, bool>
     */
    public function modulePermissions(User $user, string $moduleCode): array
    {
        if ($this->hasSystemWideAccess($user)) {
            return $this->fullPermissionSet();
        }

        return $this->buildPermissionSet(
            fn (string $action): bool => $this->safeDecision(
                fn (): bool => (bool) $user->canAccessModule($moduleCode, $action)
            )
        );
    }

    /**
     * @return array<string, bool>
     */
    public function subModulePermissions(User $user, string $subModuleCode): array
    {
        if ($this->hasSystemWideAccess($user)) {
            return $this->fullPermissionSet();
        }

        return $this->buildPermissionSet(
            fn (string $action): bool => $this->safeDecision(
                fn (): bool => (bool) $user->canAccessSubModule($subModuleCode, $action)
            )
        );
    }

    /**
     * @return array<string, bool>
     */
    public function sectionPermissions(User $user, string $sectionCode): array
    {
        if ($this->hasSystemWideAccess($user)) {
            return $this->fullPermissionSet();
        }

        return $this->buildPermissionSet(
            fn (string $action): bool => $this->safeDecision(
                fn (): bool => (bool) $user->canAccessSection($sectionCode, $action)
            )
        );
    }

    private function hasSystemWideAccess(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isEnterpriseAdmin() || $user->isSiteManager();
    }

    /**
     * @return array<string, bool>
     */
    private function fullPermissionSet(): array
    {
        return [
            'read' => true,
            'create' => true,
            'update' => true,
            'delete' => true,
            'manage' => true,
        ];
    }

    /**
     * @param callable(string): bool $resolver
     * @return array<string, bool>
     */
    private function buildPermissionSet(callable $resolver): array
    {
        return [
            'read' => $resolver('read'),
            'create' => $resolver('create'),
            'update' => $resolver('update'),
            'delete' => $resolver('delete'),
            'manage' => $resolver('manage'),
        ];
    }

    /**
     * @param callable(): bool $resolver
     */
    private function safeDecision(callable $resolver): bool
    {
        try {
            return (bool) $resolver();
        } catch (\Throwable) {
            return false;
        }
    }
}

