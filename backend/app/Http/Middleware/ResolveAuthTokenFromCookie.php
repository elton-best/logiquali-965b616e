<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResolveAuthTokenFromCookie
{
    public function handle(Request $request, Closure $next)
    {
        $hasAuthorizationHeader = $request->headers->has('Authorization');
        if (!$hasAuthorizationHeader) {
            $cookieName = (string) env('AUTH_TOKEN_COOKIE_NAME', 'auth_token');
            $cookieToken = (string) $request->cookie($cookieName, '');
            if ($cookieToken !== '') {
                $request->headers->set('Authorization', "Bearer {$cookieToken}");
            }
        }

        return $next($request);
    }
}

