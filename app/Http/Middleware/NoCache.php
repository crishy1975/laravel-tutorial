<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Verhindert Browser-Cache für HTML-Seiten.
 * Damit zeigt der Back-Button immer frische Daten.
 */
class NoCache
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Nur HTML-Responses, keine Assets/APIs
        if ($response->headers->has('Content-Type') &&
            !str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
