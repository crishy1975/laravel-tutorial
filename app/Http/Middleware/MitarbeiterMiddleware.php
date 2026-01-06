<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MitarbeiterMiddleware
{
    /**
     * Erlaubt Zugriff für Mitarbeiter UND Admins.
     * (Admins können alles sehen)
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Admins und Mitarbeiter haben Zugriff
        if (auth()->user()->isAdmin() || auth()->user()->isMitarbeiter()) {
            return $next($request);
        }

        return redirect()->route('login')
            ->with('error', 'Kein Zugriff auf diesen Bereich.');
    }
}
