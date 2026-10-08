<?php

namespace App\Http\Middleware;

use App\Models\SecurityAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class AdvancedRateLimit
{
    private array $limits = [
        'super_admin' => ['requests' => 1000, 'minutes' => 1],
        'admin_entreprise' => ['requests' => 700, 'minutes' => 1],
        'company' => ['requests' => 300, 'minutes' => 1],
        'clientb' => ['requests' => 100, 'minutes' => 1],
        'guest' => ['requests' => 20, 'minutes' => 1]
    ];

    private array $sensitiveRoutes = [
        'auth/login' => ['requests' => 5, 'minutes' => 1],
        'files/upload' => ['requests' => 10, 'minutes' => 1],
        'users' => ['requests' => 50, 'minutes' => 1]
    ];

    public function handle(Request $request, Closure $next, string $type = 'api')
    {
        $user = $request->user();
        $userType = $user?->user_type ?? 'guest';
        $route = (string) ($request->route()?->uri() ?? '');

        // Évite de cumuler les blocages sur quelques endpoints de lecture
        // très sollicités (dashboard, catalogues, listes).
        // Le throttling global `api` reste actif.
        if ($this->shouldBypassReadOnlyThrottle($request, $user, $route)) {
            return $next($request);
        }

        // Check for suspicious activity
        if ($this->isSuspiciousActivity($request, $user)) {
            $this->logSuspiciousActivity($request, $user);
            return response()->json(['message' => 'Trop de requêtes suspectes'], 429);
        }

        // Apply route-specific limits
        foreach ($this->sensitiveRoutes as $pattern => $limit) {
            if ($route !== '' && str_contains($route, $pattern)) {
                if ($pattern === 'users' && in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $key = "sensitive:{$pattern}:" . ($user?->id ?? $request->ip());

                if (RateLimiter::tooManyAttempts($key, $limit['requests'])) {
                    $this->logRateLimitExceeded($request, $user, $pattern);
                    return response()->json(['message' => 'Limite dépassée'], 429);
                }

                RateLimiter::hit($key, $limit['minutes'] * 60);
            }
        }

        // Apply user-type limits
        $limit = $this->limits[$userType];
        $key = "user:{$userType}:" . ($user?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, $limit['requests'])) {
            $this->logRateLimitExceeded($request, $user, 'general');
            return response()->json(['message' => 'Limite générale dépassée'], 429);
        }

        RateLimiter::hit($key, $limit['minutes'] * 60);

        return $next($request);
    }

    private function isSuspiciousActivity(Request $request, $user): bool
    {
        $identifier = $user?->id ? "user:{$user->id}" : "ip:{$request->ip()}";
        $route = (string) ($request->route()?->uri() ?? '');

        // Les rafales de requêtes GET authentifiées (chargement dashboard/lists)
        // ne doivent pas être classées comme activité suspecte.
        if ($this->shouldBypassReadOnlyThrottle($request, $user, $route)) {
            return false;
        }

        // Check failed login attempts
        $failedLogins = Cache::get("failed_logins:{$request->ip()}", 0);
        if ($failedLogins >= 10) {
            return true;
        }

        // Seuil plus élevé pour les sessions authentifiées.
        $rapidRequestThreshold = $user ? 300 : 100;
        $requestCount = Cache::get("rapid_requests:{$identifier}", 0);
        if ($requestCount >= $rapidRequestThreshold) {
            return true;
        }

        Cache::put("rapid_requests:{$identifier}", $requestCount + 1, 60);

        return false;
    }

    private function shouldBypassReadOnlyThrottle(Request $request, $user, string $route): bool
    {
        if (!$user) {
            return false;
        }

        $isReadOnlyRequest = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        if (!$isReadOnlyRequest) {
            return false;
        }

        $routeOrPath = trim($route !== '' ? $route : $request->path(), '/');
        $bypassPatterns = [
            'dashboard/stats',
            'dashboard/layout',
            'access/catalog',
            'subscription/status',
            'subscription/current',
            'available-roles',
            'available-permissions',
            'notifications',
            'auth/me',
            'sites',
            'processes',
            'users',
            'reclamations',
        ];

        foreach ($bypassPatterns as $pattern) {
            if ($routeOrPath !== '' && str_contains($routeOrPath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function logSuspiciousActivity(Request $request, $user): void
    {
        SecurityAuditLog::logEvent(
            eventType: 'rate_limit',
            action: 'suspicious_activity',
            metadata: [
                'ip' => $request->ip(),
                'route' => $request->route()?->uri(),
                'user_agent' => $request->userAgent()
            ],
            riskLevel: 'high'
        );
    }

    private function logRateLimitExceeded(Request $request, $user, string $limitType): void
    {
        SecurityAuditLog::logEvent(
            eventType: 'rate_limit',
            action: 'limit_exceeded',
            metadata: [
                'limit_type' => $limitType,
                'route' => $request->route()?->uri(),
                'user_type' => $user?->user_type ?? 'guest'
            ],
            riskLevel: 'medium'
        );
    }
}
