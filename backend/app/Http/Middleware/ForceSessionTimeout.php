<?php

namespace App\Http\Middleware;

use App\Services\Settings\SuperAdminSettingsService;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceSessionTimeout
{
    private const MAX_SESSION_MINUTES = 480; // 8h

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $token = $user->currentAccessToken();
        if (!$token) {
            return $next($request);
        }

        $issuedAt = $this->resolveTokenDate($token, 'created_at');
        if ($issuedAt && $issuedAt->copy()->addMinutes(self::MAX_SESSION_MINUTES)->isPast()) {
            $this->deleteTokenIfPersisted($token);

            return response()->json([
                'success' => false,
                'message' => 'Session expiree (maximum 8 heures). Veuillez vous reconnecter.',
            ], 401);
        }

        $settings = app(SuperAdminSettingsService::class)->getSecuritySettings();
        $enabled = (bool) ($settings['sessionTimeout'] ?? false);
        $minutes = (int) ($settings['sessionTimeoutMinutes'] ?? 30);
        $minutes = max(1, min($minutes, self::MAX_SESSION_MINUTES));

        if (!$enabled) {
            return $next($request);
        }

        $lastUsedAt = $this->resolveTokenDate($token, 'last_used_at')
            ?? $this->resolveTokenDate($token, 'created_at');
        if (!$lastUsedAt) {
            return $next($request);
        }

        $expiresAt = $lastUsedAt->copy()->addMinutes($minutes);
        if ($expiresAt->isPast()) {
            $this->deleteTokenIfPersisted($token);
            return response()->json([
                'success' => false,
                'message' => 'Session expiree. Veuillez vous reconnecter.',
            ], 401);
        }

        return $next($request);
    }

    private function resolveTokenDate(mixed $token, string $field): ?Carbon
    {
        $value = data_get($token, $field);
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function deleteTokenIfPersisted(mixed $token): void
    {
        if (is_object($token) && method_exists($token, 'delete')) {
            $token->delete();
        }
    }
}
