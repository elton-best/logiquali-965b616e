<?php

namespace App\Policies;

use App\Models\Action;
use App\Models\User;
use App\Helpers\PermissionHelper;

class ActionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Action $action): bool
    {
        if ($this->canManageAllActions($user)) {
            return $this->isInSameScope($user, $action)
                && (
                    PermissionHelper::canWithinScope($user, 'actions.read')
                    || PermissionHelper::canWithinScope($user, 'actions.manage')
                );
        }

        return $this->isInSameScope($user, $action)
            && $this->isResponsible($user, $action);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::canCreate($user, 'actions');
    }

    public function update(User $user, Action $action): bool
    {
        if ($action->workflowState?->code === 'closed') {
            return $this->canManageAllActions($user);
        }

        if ($this->canManageAllActions($user)) {
            return $this->isInSameScope($user, $action)
                && (
                    PermissionHelper::canWithinScope($user, 'actions.update')
                    || PermissionHelper::canWithinScope($user, 'actions.manage')
                );
        }

        return $this->isInSameScope($user, $action)
            && $this->isResponsible($user, $action);
    }

    public function delete(User $user, Action $action): bool
    {
        if ($action->workflowState?->code === 'closed') {
            return false;
        }

        return PermissionHelper::canDelete($user, 'actions');
    }

    public function updateProgress(User $user, Action $action): bool
    {
        if ($this->canManageAllActions($user)) {
            return $this->isInSameScope($user, $action)
                && (
                    PermissionHelper::canWithinScope($user, 'actions.update')
                    || PermissionHelper::canWithinScope($user, 'actions.manage')
                );
        }

        return $this->isInSameScope($user, $action)
            && $this->isResponsible($user, $action);
    }

    public function verify(User $user, Action $action): bool
    {
        return PermissionHelper::can($user, 'actions.manage');
    }

    public function close(User $user, Action $action): bool
    {
        return PermissionHelper::can($user, 'actions.manage');
    }

    public function reschedule(User $user, Action $action): bool
    {
        if (!$this->isInSameScope($user, $action) || in_array($action->status, ['completed', 'verified', 'cancelled'], true)) {
            return false;
        }

        if (PermissionHelper::isAdmin($user) || PermissionHelper::can($user, 'actions.manage')) {
            return true;
        }

        if ((int) $action->responsible_id === (int) $user->id) {
            return true;
        }

        if ($this->hasRole($user, ['rq', 'responsable_qualite', 'quality_manager', 'hse_manager', 'ceo', 'directeur_general'])) {
            return true;
        }

        return $user->site_id
            && $user->site?->manager_id
            && (int) $user->site->manager_id === (int) $user->id;
    }

    private function canManageAllActions(User $user): bool
    {
        return PermissionHelper::isAdmin($user)
            || PermissionHelper::can($user, 'actions.manage');
    }

    private function isInSameScope(User $user, Action $action): bool
    {
        return ($user->site_id && $user->site_id === $action->site_id)
            || ($user->enterprise_id === $action->enterprise_id);
    }

    private function isResponsible(User $user, Action $action): bool
    {
        return (int) $action->responsible_id === (int) $user->id;
    }

    private function hasRole(User $user, array $roles): bool
    {
        $roles = array_map('mb_strtolower', $roles);
        $assigned = $user->roles->pluck('name')->map(fn ($role) => mb_strtolower((string) $role))->all();
        $legacy = mb_strtolower((string) $user->role);

        return collect(array_merge($assigned, [$legacy]))
            ->contains(fn (string $role): bool => in_array($role, $roles, true));
    }
}
