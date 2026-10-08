<?php

namespace App\Policies;

use App\Models\Objective;
use App\Models\User;
use App\Helpers\PermissionHelper;

class ObjectivePolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Objective $objective): bool
    {
        return PermissionHelper::can($user, 'indicateurs.read');
    }

    public function create(User $user): bool
    {
        return PermissionHelper::canCreate($user, 'indicateurs');
    }

    public function update(User $user, Objective $objective): bool
    {
        if ($objective->workflowState?->code === 'achieved') {
            return PermissionHelper::isAdmin($user);
        }

        return PermissionHelper::can($user, 'indicateurs.update', function($user) use ($objective) {
            return $objective->responsible_id === $user->id;
        });
    }

    public function delete(User $user, Objective $objective): bool
    {
        return PermissionHelper::canDelete($user, 'indicateurs');
    }

    public function updateProgress(User $user, Objective $objective): bool
    {
        return PermissionHelper::can($user, 'indicateurs.update', function($user) use ($objective) {
            return $objective->responsible_id === $user->id;
        });
    }

    public function addMilestone(User $user, Objective $objective): bool
    {
        return PermissionHelper::can($user, 'indicateurs.update', function($user) use ($objective) {
            return $objective->responsible_id === $user->id;
        });
    }
}
