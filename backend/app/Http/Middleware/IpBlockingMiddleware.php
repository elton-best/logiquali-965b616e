<?php

namespace App\Http\Middleware;

use App\Models\SecurityAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IpBlockingMiddleware
{
    private array $permanentBlacklist = [
        // Add known malicious IPs here
    ];

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // Check permanent blacklist
        if (in_array($ip, $this->permanentBlacklist)) {
            $this->logBlockedAccess($request, 'permanent_blacklist');
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Check temporary blocks
        if (Cache::has("blocked_ip:{$ip}")) {
            $this->logBlockedAccess($request, 'temporary_block');
            return response()->json(['message' => 'IP temporairement bloquée'], 403);
        }

        // Auto-block based on failed attempts
        $this->checkAutoBlock($request, $ip);

        return $next($request);
    }

    private function checkAutoBlock(Request $request, string $ip): void
    {
        $failedAttempts = Cache::get("failed_attempts:{$ip}", 0);
        
        if ($failedAttempts >= 20) {
            // Block for 1 hour
            Cache::put("blocked_ip:{$ip}", true, 3600);
            
            SecurityAuditLog::logEvent(
                eventType: 'security_block',
                action: 'ip_auto_blocked',
                metadata: [
                    'ip' => $ip,
                    'failed_attempts' => $failedAttempts,
                    'block_duration' => 3600
                ],
                riskLevel: 'high'
            );
        }
    }

    private function logBlockedAccess(Request $request, string $reason): void
    {
        SecurityAuditLog::logEvent(
            eventType: 'security_block',
            action: 'access_blocked',
            metadata: [
                'ip' => $request->ip(),
                'reason' => $reason,
                'route' => $request->route()?->uri(),
                'user_agent' => $request->userAgent()
            ],
            riskLevel: 'critical'
        );
    }

    public static function recordFailedAttempt(string $ip): void
    {
        $key = "failed_attempts:{$ip}";
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, 3600); // Keep for 1 hour
    }
}