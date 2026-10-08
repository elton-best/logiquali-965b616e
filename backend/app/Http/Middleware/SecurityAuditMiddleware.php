<?php

namespace App\Http\Middleware;

use App\Models\SecurityAuditLog;
use Closure;
use Illuminate\Http\Request;

class SecurityAuditMiddleware
{
    private array $sensitiveRoutes = [
        'auth/login' => 'high',
        'auth/logout' => 'medium',
        'users' => 'medium',
        'permissions' => 'high',
        'roles' => 'high',
        'files/upload' => 'medium'
    ];

    public function handle(Request $request, Closure $next)
    {
        $startedAt = microtime(true);
        $response = $next($request);

        // Log after request processing
        $this->logRequest($request, $response, $startedAt);

        return $response;
    }

    private function logRequest(Request $request, $response, float $startedAt): void
    {
        $route = $request->route()?->uri() ?? $request->path();
        $method = $request->method();
        $statusCode = $response->getStatusCode();

        // Determine risk level
        $riskLevel = $this->getRiskLevel($route, $statusCode);
        
        if ($riskLevel) {
            $action = $this->getAction($method, $statusCode);
            
            SecurityAuditLog::logEvent(
                eventType: 'api_access',
                action: $action,
                resourceType: 'route',
                resourceId: null,
                metadata: [
                    'route' => $route,
                    'method' => $method,
                    'status_code' => $statusCode,
                    'response_time' => microtime(true) - $startedAt,
                ],
                riskLevel: $riskLevel
            );
        }
    }

    private function getRiskLevel(?string $route, int $statusCode): ?string
    {
        $route = (string) $route;

        // Failed authentication/authorization
        if (in_array($statusCode, [401, 403])) {
            return 'high';
        }

        // Check sensitive routes
        foreach ($this->sensitiveRoutes as $pattern => $level) {
            if (str_contains($route, $pattern)) {
                return $level;
            }
        }

        return null;
    }

    private function getAction(string $method, int $statusCode): string
    {
        if ($statusCode >= 400) {
            return $method . '_failed';
        }

        return strtolower($method);
    }
}
