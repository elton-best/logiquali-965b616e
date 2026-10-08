<?php

namespace App\Policies;

use App\Helpers\PermissionHelper;
use App\Models\Enterprise;
use App\Models\User;

class EnterprisePolicy
{
    public function view(User $user, Enterprise $enterprise): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isCompanyUser()
            && (int) $user->enterprise_id === (int) $enterprise->id;
    }

    public function update(User $user, Enterprise $enterprise): bool
    {
        return $this->view($user, $enterprise)
            && PermissionHelper::can($user, 'enterprises.update');
    }
}
