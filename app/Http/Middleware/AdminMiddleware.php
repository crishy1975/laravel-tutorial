<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Nur Admins dürfen passieren.
     * Mitarbeiter werden zu ihrer Startseite weitergeleitet.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->isAdmin()) {
            // Mitarbeiter zur Mitarbeiter-Startseite weiterleiten
            if (auth()->user()->isMitarbeiter()) {
                return redirect()->route('mitarbeiter.dashboard')
                    ->with('error', 'Kein Zugriff auf diesen Bereich.');
            }
            
            return redirect()->route('login');
        }

        return $next($request);
    }
}
