<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $ttl = 300): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Don't cache if user is authenticated (personalized data)
        if ($request->user()) {
            return $next($request);
        }

        // Generate cache key from route + query params
        $key = 'route:' . md5($request->url() . serialize($request->query()));

        // Try to return cached response
        if (Cache::has($key)) {
            $cachedResponse = Cache::get($key);
            return response($cachedResponse['content'], $cachedResponse['status'])
                ->withHeaders(array_merge($cachedResponse['headers'], [
                    'X-Cache' => 'HIT',
                ]));
        }

        // Get fresh response
        $response = $next($request);

        // Cache successful responses
        if ($response->isSuccessful()) {
            Cache::put($key, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
            ], $ttl);

            $response->headers->set('X-Cache', 'MISS');
        }

        return $response;
    }
}
