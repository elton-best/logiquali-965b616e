<?php

namespace App\Http\Middleware;

use App\Support\BlockingAccessResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $path = $request->path();
        $allow = [
            'api/v1/auth/me',
            'api/v1/auth/logout',
            'api/v1/auth/profile',
            'api/v1/auth/refresh-token',
        ];

        if ($user->must_change_password && !$this->isAllowedWhilePasswordChange($request, $user->id, $path, $allow)) {
            return BlockingAccessResponse::make(
                'PASSWORD_CHANGE_REQUIRED',
                'Vous devez changer votre mot de passe avant de continuer.',
                $this->resolveRedirectPath($user),
            );
        }

        return $next($request);
    }

    private function isAllowedWhilePasswordChange(Request $request, int $userId, string $path, array $allow): bool
    {
        if (in_array($path, $allow, true)) {
            return true;
        }

        // Autoriser uniquement les actions self nécessaires pour se débloquer.
        if (preg_match('#^api/v1/users/(\d+)$#', $path, $matches)) {
            $targetId = (int) ($matches[1] ?? 0);
            if ($targetId === $userId && in_array($request->method(), ['PUT', 'PATCH'], true)) {
                return true;
            }
        }

        if (preg_match('#^api/v1/users/(\d+)/signature$#', $path, $matches)) {
            $targetId = (int) ($matches[1] ?? 0);
            if ($targetId === $userId && $request->method() === 'POST') {
                return true;
            }
        }

        return false;
    }

    private function resolveRedirectPath($user): string
    {
        if ($user->isSuperAdmin()) {
            return '/superadmin/profile?blocking=PASSWORD_CHANGE_REQUIRED';
        }

        return '/auth/force-password-change?blocking=PASSWORD_CHANGE_REQUIRED';
    }
}
