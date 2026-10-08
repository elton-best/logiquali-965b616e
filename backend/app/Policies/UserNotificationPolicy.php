<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Auth\Access\Response;

class UserNotificationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserNotification $userNotification): bool
    {
        return $user->id === $userNotification->user_id;
    }

    public function update(User $user, UserNotification $userNotification): bool
    {
        return $user->id === $userNotification->user_id;
    }

    public function delete(User $user, UserNotification $userNotification): bool
    {
        return $user->id === $userNotification->user_id;
    }
}
