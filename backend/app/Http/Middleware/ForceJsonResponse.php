<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     * Force all API requests to expect JSON responses
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force API routes to accept JSON
        $request->headers->set('Accept', 'application/json');
        
        return $next($request);
    }
}
