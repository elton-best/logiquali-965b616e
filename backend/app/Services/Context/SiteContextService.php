<?php

namespace App\Services\Context;

use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

class SiteContextService
{
    public function extractSiteId(Request $request): ?int
    {
        $querySiteId = $request->integer('site_id');
        if ($querySiteId > 0) {
            return $querySiteId;
        }

        $headerSiteId = (int) $request->header('X-Site-ID');
        if ($headerSiteId > 0) {
            return $headerSiteId;
        }

        return null;
    }

    public function resolveForRequest(Request $request, ?int $fallbackSiteId = null): ?Site
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        $siteId = $this->extractSiteId($request) ?? $fallbackSiteId;

        return $this->resolveForUser($user, $siteId);
    }

    public function resolveForUser(User $user, ?int $siteId = null): ?Site
    {
        if ($siteId) {
            $site = Site::find($siteId);
            if (!$site) {
                return null;
            }

            if ($user->isSuperAdmin()) {
                return $site;
            }

            if ($user->enterprise_id && (int) $site->enterprise_id === (int) $user->enterprise_id) {
                return $site;
            }

            if ($user->site_id && (int) $user->site_id === (int) $site->id) {
                return $site;
            }

            return null;
        }

        if ($user->site) {
            return $user->site;
        }

        if ($user->enterprise_id) {
            // Priorite au premier site de l'entreprise qui a une souscription active
            // pour eviter un catalogue vide au premier chargement.
            $siteWithActiveSubscription = Site::query()
                ->where('enterprise_id', $user->enterprise_id)
                ->whereHas('subscriptions', function ($query) {
                    $query->where('is_active', true)
                        ->where('start_date', '<=', now())
                        ->where(function ($q) {
                            $q->where(function ($paid) {
                                $paid->where(function ($paidStatus) {
                                    $paidStatus->whereNull('is_trial')
                                        ->orWhere('is_trial', false);
                                })->whereNotNull('expiration_date')
                                    ->where('expiration_date', '>', now());
                            })->orWhere(function ($trial) {
                                $trial->where('is_trial', true)
                                    ->whereNotNull('trial_ends_at')
                                    ->where('trial_ends_at', '>', now());
                            });
                        });
                })
                ->orderByDesc('is_headquarter')
                ->orderBy('id')
                ->first();

            if ($siteWithActiveSubscription) {
                return $siteWithActiveSubscription;
            }

            // Fallback: premier site de l'entreprise.
            return Site::query()
                ->where('enterprise_id', $user->enterprise_id)
                ->orderByDesc('is_headquarter')
                ->orderBy('id')
                ->first();
        }

        return null;
    }
}
