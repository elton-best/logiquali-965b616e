<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyEndpointDeprecation
{
    public function handle(Request $request, Closure $next, ?string $replacement = null, ?string $sunsetDate = null): Response
    {
        if ((bool) config('api.hard_deprecate_legacy_endpoints', false)) {
            $payload = [
                'message' => 'Cet endpoint legacy n’est plus disponible. Utilisez l’endpoint de remplacement.',
                'deprecated' => true,
            ];
            if ($replacement) {
                $payload['replacement'] = url('/api/v1/' . ltrim($replacement, '/'));
            }
            if ($sunsetDate) {
                $payload['sunset_date'] = $sunsetDate;
            }

            return response()->json($payload, 410);
        }

        $response = $next($request);

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('X-API-Deprecated', 'true');

        if ($replacement) {
            $normalizedReplacement = ltrim($replacement, '/');
            $replacementUrl = url("/api/v1/{$normalizedReplacement}");
            $response->headers->set('Link', "<{$replacementUrl}>; rel=\"successor-version\"");
            $response->headers->set('X-API-Replacement', $replacementUrl);
        }

        if ($sunsetDate) {
            $timestamp = strtotime($sunsetDate);
            if ($timestamp !== false) {
                $response->headers->set('Sunset', gmdate(DATE_RFC7231, $timestamp));
            }
        }

        return $response;
    }
}
