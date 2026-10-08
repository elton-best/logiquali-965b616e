<?php

namespace App\Observers;

use App\Models\User;
use App\Traits\NotifiesSiteUsers;

class UserObserver
{
    use NotifiesSiteUsers;

    public function created(User $user): void
    {
        if ($user->site_id) {
            $this->notifySiteUsers($user->site_id, 'user_created', [
                'type' => 'user_created',
                'message' => "Nouveau collaborateur: {$user->name}",
                'urgency' => 'info',
                'action_url' => "/company/leadership/personnel",
            ]);
        }
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('is_active') && $user->site_id) {
            $status = $user->is_active ? 'activé' : 'désactivé';
            
            $this->notifySiteUsers($user->site_id, 'user_status_changed', [
                'type' => 'user_status_changed',
                'message' => "Collaborateur {$user->name} {$status}",
                'urgency' => 'info',
                'action_url' => "/company/leadership/personnel",
            ]);
        }
    }

    public function deleted(User $user): void
    {
        if ($user->site_id) {
            $this->notifySiteUsers($user->site_id, 'user_deleted', [
                'type' => 'user_deleted',
                'message' => "Collaborateur {$user->name} supprimé",
                'urgency' => 'info',
            ]);
        }
    }
}
