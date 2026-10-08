<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentWorkflowRateLimit
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next): Response|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Skip rate limiting in tests
        if (app()->environment('testing')) {
            return $next($request);
        }

        // Clé du limiter: user_id + action
        $action = $this->extractAction($request);
        $key = "workflow:{$user->id}:{$action}";

        // Limites : 20 req/min par utilisateur pour lecture, 10 req/min pour écriture critique
        $limit = $this->isReadAction($request) ? 20 : 10;
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $limit, $decayMinutes)) {
            $retryAfter = $this->limiter->availableIn($key);
            return response()->json([
                'message' => 'Trop de requêtes. Réessayez après ' . $retryAfter . ' secondes.',
            ], 429)->header('Retry-After', $retryAfter);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }

    private function extractAction(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, '/verify')) return 'verify';
        if (str_contains($path, '/approve')) return 'approve';
        if (str_contains($path, '/reject')) return 'reject';
        if (str_contains($path, '/confirm-code')) return 'confirm-code';
        if (str_contains($path, '/confirm-rejection-decision')) return 'confirm-rejection';
        
        return 'read';
    }

    private function isReadAction(Request $request): bool
    {
        return $request->isMethod('GET');
    }
}

