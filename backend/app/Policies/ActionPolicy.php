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
}
