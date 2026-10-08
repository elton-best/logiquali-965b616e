<?php

namespace App\Modules\Enterprise\Services;

use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationRecipientService
{
    public function getSiteRecipients(Site $site): Collection
    {
        $collaborators = User::query()
            ->where('site_id', $site->id)
            ->where('user_type', 'company')
            ->where('is_active', true)
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'admin_entreprise');
            })
            ->get();

        $enterpriseAdmins = User::query()
            ->where('enterprise_id', $site->enterprise_id)
            ->where('user_type', 'company')
            ->where('is_active', true)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin_entreprise');
            })
            ->get();

        return $collaborators->merge($enterpriseAdmins)->unique('id')->values();
    }
}
