<?php

namespace App\Policies;

use App\Models\Risk;
use App\Models\User;
use App\Helpers\PermissionHelper;

class RiskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Risk $risk): bool
    {
        return PermissionHelper::can($user, 'risks.read', function($user) use ($risk) {
            return ($user->site_id && $user->site_id === $risk->site_id) ||
                   $risk->responsible_id === $user->id;
        });
    }

    public function create(User $user): bool
    {
        return PermissionHelper::canCreate($user, 'risks');
    }

    public function update(User $user, Risk $risk): bool
    {
        if ($risk->workflowState?->code === 'closed') {
            return PermissionHelper::isAdmin($user);
        }

        return PermissionHelper::can($user, 'risks.update', function($user) use ($risk) {
            return $risk->responsible_id === $user->id;
        });
    }

    public function delete(User $user, Risk $risk): bool
    {
        return PermissionHelper::canDelete($user, 'risks');
    }

    public function assess(User $user, Risk $risk): bool
    {
        return PermissionHelper::can($user, 'risks.update', function($user) use ($risk) {
            return $risk->responsible_id === $user->id;
        });
    }

    public function treat(User $user, Risk $risk): bool
    {
        return PermissionHelper::can($user, 'risks.update', function($user) use ($risk) {
            return $risk->responsible_id === $user->id;
        });
    }

    public function viewMatrix(User $user): bool
    {
        return PermissionHelper::can($user, 'risks.read');
    }

    public function viewStatistics(User $user): bool
    {
        return PermissionHelper::can($user, 'risks.read');
    }
}
