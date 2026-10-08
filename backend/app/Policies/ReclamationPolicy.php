<?php

namespace App\Policies;

use App\Models\Reclamation;
use App\Models\User;
use App\Helpers\PermissionHelper;

class ReclamationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Reclamation $reclamation): bool
    {
        return PermissionHelper::can($user, 'reclamations.read', function($user) use ($reclamation) {
            return ($user->site_id && $user->site_id === $reclamation->site_id) ||
                   $reclamation->responsible_id === $user->id;
        });
    }

    public function create(User $user): bool
    {
        return PermissionHelper::canCreate($user, 'reclamations');
    }

    public function update(User $user, Reclamation $reclamation): bool
    {
        if ($reclamation->workflowState?->code === 'closed') {
            return PermissionHelper::isAdmin($user);
        }

        if (PermissionHelper::isAdmin($user)) {
            return true;
        }

        if (PermissionHelper::can($user, 'reclamations.manage')) {
            return true;
        }

        if (PermissionHelper::can($user, 'reclamations.update')) {
            return true;
        }

        return $reclamation->responsible_id === $user->id;
    }

    public function delete(User $user, Reclamation $reclamation): bool
    {
        return PermissionHelper::canDelete($user, 'reclamations');
    }

    public function analyze(User $user, Reclamation $reclamation): bool
    {
        if (PermissionHelper::isAdmin($user)) {
            return true;
        }
        return PermissionHelper::can($user, 'reclamations.update', function($user) use ($reclamation) {
            return $reclamation->responsible_id === $user->id;
        });
    }

    public function respond(User $user, Reclamation $reclamation): bool
    {
        if (PermissionHelper::isAdmin($user)) {
            return true;
        }
        return PermissionHelper::can($user, 'reclamations.update', function($user) use ($reclamation) {
            return $reclamation->responsible_id === $user->id;
        });
    }

    public function close(User $user, Reclamation $reclamation): bool
    {
        return PermissionHelper::can($user, 'reclamations.approve');
    }

    public function viewStatistics(User $user): bool
    {
        return PermissionHelper::can($user, 'reclamations.read');
    }
}
