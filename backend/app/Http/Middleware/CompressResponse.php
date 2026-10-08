<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if client accepts gzip
        $acceptEncoding = $request->header('Accept-Encoding', '');
        
        if (!str_contains($acceptEncoding, 'gzip')) {
            return $response;
        }

        // Only compress text-based content
        $contentType = $response->headers->get('Content-Type', '');
        $compressibleTypes = ['text/', 'application/json', 'application/javascript', 'application/xml'];
        
        $shouldCompress = false;
        foreach ($compressibleTypes as $type) {
            if (str_contains($contentType, $type)) {
                $shouldCompress = true;
                break;
            }
        }

        if (!$shouldCompress) {
            return $response;
        }

        // Get response content
        $content = $response->getContent();

        // Only compress if content is larger than 1KB
        if (strlen($content) < 1024) {
            return $response;
        }

        // Compress content
        $compressed = gzencode($content, 6); // Level 6 compression (balance speed/ratio)

        if ($compressed === false) {
            return $response;
        }

        // Set compressed content and headers
        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
