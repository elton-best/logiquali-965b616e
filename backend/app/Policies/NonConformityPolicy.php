<?php

namespace App\Policies;

use App\Models\NonConformity;
use App\Models\User;
use App\Helpers\PermissionHelper;

class NonConformityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, NonConformity $nonConformity): bool
    {
        return PermissionHelper::can($user, 'non_conformities.read', function($user) use ($nonConformity) {
            return ($user->site_id && $user->site_id === $nonConformity->site_id) ||
                   $nonConformity->responsible_id === $user->id ||
                   $nonConformity->detected_by === $user->id ||
                   in_array($user->id, is_array($nonConformity->investigator_user_ids) ? $nonConformity->investigator_user_ids : [], true);
        });
    }

    public function create(User $user): bool
    {
        return PermissionHelper::canCreate($user, 'non_conformities');
    }

    public function update(User $user, NonConformity $nonConformity): bool
    {
        if ($nonConformity->workflowState?->code === 'closed') {
            return PermissionHelper::isAdmin($user);
        }

        if (PermissionHelper::isAdmin($user)) {
            return true;
        }

        if (PermissionHelper::can($user, 'non_conformities.manage')) {
            return true;
        }

        if (PermissionHelper::can($user, 'non_conformities.update')) {
            return true;
        }

        return $nonConformity->responsible_id === $user->id ||
            in_array($user->id, is_array($nonConformity->investigator_user_ids) ? $nonConformity->investigator_user_ids : [], true);
    }

    public function delete(User $user, NonConformity $nonConformity): bool
    {
        if (in_array($nonConformity->workflowState?->code, ['verified', 'closed'])) {
            return false;
        }

        return PermissionHelper::canDelete($user, 'non_conformities');
    }

    public function analyze(User $user, NonConformity $nonConformity): bool
    {
        return PermissionHelper::can($user, 'non_conformities.manage', function($user) use ($nonConformity) {
            return $nonConformity->responsible_id === $user->id ||
                in_array($user->id, is_array($nonConformity->investigator_user_ids) ? $nonConformity->investigator_user_ids : [], true);
        });
    }

    public function validate(User $user, NonConformity $nonConformity): bool
    {
        return PermissionHelper::can($user, 'non_conformities.manage');
    }

    public function verify(User $user, NonConformity $nonConformity): bool
    {
        return PermissionHelper::can($user, 'non_conformities.manage');
    }

    public function close(User $user, NonConformity $nonConformity): bool
    {
        return PermissionHelper::can($user, 'non_conformities.manage');
    }

    public function addCost(User $user, NonConformity $nonConformity): bool
    {
        return PermissionHelper::can($user, 'non_conformities.update', function($user) use ($nonConformity) {
            return $nonConformity->responsible_id === $user->id ||
                in_array($user->id, is_array($nonConformity->investigator_user_ids) ? $nonConformity->investigator_user_ids : [], true);
        });
    }

    public function viewStatistics(User $user): bool
    {
        return PermissionHelper::can($user, 'non_conformities.read');
    }
}
