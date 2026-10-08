<?php

namespace App\Policies;

use App\Models\Indicateur;
use App\Models\User;
use App\Helpers\PermissionHelper;

class IndicateurPolicy
{
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Indicateur $indicateur): bool
    {
        return PermissionHelper::can($user, 'indicateurs.read');
    }

    public function create(User $user): bool
    {
        return PermissionHelper::canCreate($user, 'indicateurs');
    }

    public function update(User $user, Indicateur $indicateur): bool
    {
        return PermissionHelper::can($user, 'indicateurs.update', function($user) use ($indicateur) {
            return $indicateur->responsible_id === $user->id;
        });
    }

    public function delete(User $user, Indicateur $indicateur): bool
    {
        return PermissionHelper::canDelete($user, 'indicateurs');
    }

    public function addValue(User $user, Indicateur $indicateur): bool
    {
        return PermissionHelper::can($user, 'indicateurs.update', function($user) use ($indicateur) {
            return $indicateur->responsible_id === $user->id;
        });
    }

    public function viewChartData(User $user): bool
    {
        return PermissionHelper::can($user, 'indicateurs.read');
    }

    public function checkThresholds(User $user): bool
    {
        return PermissionHelper::can($user, 'indicateurs.update');
    }
}
