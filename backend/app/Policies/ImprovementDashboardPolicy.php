<?php

namespace App\Policies;

use App\Helpers\PermissionHelper;
use App\Models\User;

class ImprovementDashboardPolicy
{
    public function viewGlobal(User $user): bool
    {
        return PermissionHelper::can($user, 'actions.read')
            || PermissionHelper::can($user, 'risks.read')
            || PermissionHelper::can($user, 'non_conformities.read')
            || PermissionHelper::can($user, 'objectives.read');
    }

    public function viewTrends(User $user): bool
    {
        return $this->viewGlobal($user);
    }

    public function viewAxesComparison(User $user): bool
    {
        return $this->viewGlobal($user);
    }

    public function viewAlerts(User $user): bool
    {
        return $this->viewGlobal($user);
    }

    public function exportPdf(User $user): bool
    {
        return PermissionHelper::can($user, 'management_reviews.read')
            || PermissionHelper::can($user, 'actions.read');
    }
}
