<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:security_audit.read');
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'blocked_ips_count' => $this->getBlockedIpsCount(),
            'rate_limit_hits_today' => $this->getRateLimitHitsToday(),
            'top_blocked_routes' => $this->getTopBlockedRoutes(),
            'suspicious_activity_count' => $this->getSuspiciousActivityCount()
        ];

        return response()->json($stats);
    }

    public function blockedIps(): JsonResponse
    {
        $blockedIps = [];
        $keys = Cache::getRedis()->keys('*blocked_ip:*');
        
        foreach ($keys as $key) {
            $ip = str_replace('blocked_ip:', '', $key);
            $ttl = Cache::getRedis()->ttl($key);
            $blockedIps[] = [
                'ip' => $ip,
                'expires_in' => $ttl > 0 ? $ttl : 0
            ];
        }

        return response()->json($blockedIps);
    }

    public function unblockIp(Request $request): JsonResponse
    {
        $request->validate(['ip' => 'required|ip']);
        
        Cache::forget("blocked_ip:{$request->ip}");
        Cache::forget("failed_attempts:{$request->ip}");
        
        return response()->json(['message' => 'IP débloquée']);
    }

    public function blockIp(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => 'required|ip',
            'duration' => 'required|integer|min:60|max:86400'
        ]);
        
        Cache::put("blocked_ip:{$request->ip}", true, $request->duration);
        
        return response()->json(['message' => 'IP bloquée']);
    }

    private function getBlockedIpsCount(): int
    {
        return count(Cache::getRedis()->keys('*blocked_ip:*'));
    }

    private function getRateLimitHitsToday(): int
    {
        return \App\Models\SecurityAuditLog::where('event_type', 'rate_limit')
            ->whereDate('created_at', today())
            ->count();
    }

    private function getTopBlockedRoutes(): array
    {
        return \App\Models\SecurityAuditLog::where('event_type', 'rate_limit')
            ->where('action', 'limit_exceeded')
            ->whereDate('created_at', today())
            ->selectRaw('JSON_EXTRACT(metadata, "$.route") as route, COUNT(*) as count')
            ->groupBy('route')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getSuspiciousActivityCount(): int
    {
        return \App\Models\SecurityAuditLog::where('event_type', 'rate_limit')
            ->where('action', 'suspicious_activity')
            ->whereDate('created_at', today())
            ->count();
    }
}