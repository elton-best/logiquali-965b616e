<?php

namespace App\Http\Middleware;

use App\Services\Context\SiteContextService;
use App\Support\BlockingAccessResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 403);
        }

        // Super admin and clientB bypass subscription check
        if ($user->isSuperAdmin() || $user->isClientB()) {
            return $next($request);
        }

        if ($this->canBypassSubscriptionCheck($request)) {
            return $next($request);
        }

        /** @var SiteContextService $siteContextService */
        $siteContextService = app(SiteContextService::class);
        $site = $siteContextService->resolveForRequest($request);

        if (!$site) {
            return response()->json(['error' => 'Site non trouvé'], 403);
        }

        $coverage = $site->getNormCoverageStatus();

        // Vérifier la couverture globale des normes souscrites
        if (!$coverage['can_access_dashboard']) {
            if ($request->expectsJson()) {
                return BlockingAccessResponse::make(
                    'SUBSCRIPTION_REQUIRED_FOR_SITE',
                    'Abonnement requis pour accéder à cette ressource',
                    '/company/subscription',
                    [
                        'site_id' => $site->id,
                        'blocking_reasons' => $coverage['blocking_reasons'],
                        'expired_norms' => $coverage['expired_norms'],
                    ],
                );
            }
            return redirect('/company/subscription');
        }

        // Alerte si trial proche expiration (< 7 jours)
        $subscriptions = $site->subscriptions()
            ->where('is_trial', true)
            ->where('status', 'trial')
            ->where('is_active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            if ($subscription->daysRemaining() <= 7) {
                $request->attributes->set('trial_alert', [
                    'days_remaining' => $subscription->daysRemaining(),
                    'trial_ends_at' => $subscription->trial_ends_at,
                ]);
                break;
            }
        }

        return $next($request);
    }

    private function canBypassSubscriptionCheck(Request $request): bool
    {
        $path = trim($request->path(), '/');
        $method = strtoupper($request->method());

        $exactRules = [
            'GET' => [
                'api/v1/auth/me',
                'api/v1/subscription/status',
                'api/v1/subscription/current',
                'api/v1/subscription/onboarding/status',
                'api/v1/subscription/alerts',
                'api/v1/subscription/dashboard',
                'api/v1/subscription/quick-actions',
                'api/v1/subscription/accessible-modules',
                'api/v1/payments/history',
                'api/v1/public/offers',
                'api/v1/offers',
                'api/v1/enterprise-subscriptions',
            ],
            'POST' => [
                'api/v1/auth/logout',
                'api/v1/subscription/subscribe',
                'api/v1/subscription/add-norm',
                'api/v1/subscription/renew-norm',
                'api/v1/subscription/onboarding',
                'api/v1/payments/request',
                'api/v1/payments/check-subscription',
                'api/v1/payments/send-otp',
                'api/v1/payments/simulate',
            ],
            'PUT' => [
                'api/v1/auth/profile',
            ],
        ];

        if (in_array($path, $exactRules[$method] ?? [], true)) {
            return true;
        }

        $patternRules = [
            'GET' => [
                'api/v1/sites/*/subscription',
                'api/v1/sites/*/subscription/check',
                'api/v1/sites/*/subscriptions',
                'api/v1/enterprise-subscriptions/*',
            ],
            'POST' => [
                'api/v1/enterprise-subscriptions/*/renew',
            ],
        ];

        foreach ($patternRules[$method] ?? [] as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
