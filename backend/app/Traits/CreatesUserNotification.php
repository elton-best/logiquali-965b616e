<?php

namespace App\Traits;

use App\Models\User;
use App\Notifications\SiteEventNotification;

trait CreatesUserNotification
{
    protected function createUserNotification(User $user, string $type, array $data): void
    {
        $user->notify(new SiteEventNotification($type, $data, $data['actor_id'] ?? null));
    }

    protected function notifyUsers(array $users, string $type, array $data): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                $this->createUserNotification($user, $type, $data);
            }
        }
    }
}
