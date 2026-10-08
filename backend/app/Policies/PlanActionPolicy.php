<?php

namespace App\Policies;

use App\Models\PlanAction;
use App\Models\User;
use App\Helpers\PermissionHelper;

class PlanActionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, PlanAction $planAction): bool
    {
        return PermissionHelper::can($user, 'actions.read');
    }

    public function create(User $user): bool
    {
        return PermissionHelper::canCreate($user, 'actions');
    }

    public function update(User $user, PlanAction $planAction): bool
    {
        if ($planAction->status === 'completed') {
            return PermissionHelper::isAdmin($user);
        }

        return PermissionHelper::can($user, 'actions.update', function($user) use ($planAction) {
            return $planAction->responsible_id === $user->id;
        });
    }

    public function delete(User $user, PlanAction $planAction): bool
    {
        return PermissionHelper::canDelete($user, 'actions');
    }

    public function addAction(User $user, PlanAction $planAction): bool
    {
        return PermissionHelper::can($user, 'actions.update', function($user) use ($planAction) {
            return $planAction->responsible_id === $user->id;
        });
    }
}
