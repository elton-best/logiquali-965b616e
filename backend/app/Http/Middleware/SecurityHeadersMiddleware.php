<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $isPreview = str_ends_with(rtrim($request->path(), '/'), '/preview')
            || str_contains($request->path(), '/preview?');

        if ($isPreview) {
            $frontendOrigin = config('app.frontend_url', 'http://localhost:3000');
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'none'; frame-ancestors 'self' {$frontendOrigin}; base-uri 'none'; form-action 'self'"
            );
        } else {
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'"
            );
        }

        $forwardedProto = strtolower((string) $request->header('X-Forwarded-Proto', ''));
        $httpsServerFlag = strtolower((string) $request->server('HTTPS', ''));
        $isHttps = $request->isSecure() || $forwardedProto === 'https' || in_array($httpsServerFlag, ['on', '1', 'true'], true);

        if ($isHttps) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
