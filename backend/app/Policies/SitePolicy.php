<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;
use App\Helpers\PermissionHelper;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Site $site): bool
    {
        return PermissionHelper::can($user, 'sites.read', function($user) use ($site) {
            return $user->enterprise_id === $site->enterprise_id;
        });
    }

    public function create(User $user): bool
    {
        // Company users can create sites only when they are enterprise admins.
        // This prevents role/baseline drifts from accidentally exposing site creation.
        if ($user->isCompanyUser() && !$user->isEnterpriseAdmin()) {
            return false;
        }

        return PermissionHelper::canCreate($user, 'sites');
    }

    public function update(User $user, Site $site): bool
    {
        return PermissionHelper::can($user, 'sites.update', function($user) use ($site) {
            return $user->enterprise_id === $site->enterprise_id;
        });
    }

    public function delete(User $user, Site $site): bool
    {
        return PermissionHelper::can($user, 'sites.delete', function($user) use ($site) {
            return $user->enterprise_id === $site->enterprise_id;
        });
    }
}
