<?php
// app/Http/Middleware/CheckMitarbeiter.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMitarbeiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Prüfen ob User eingeloggt ist
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Prüfen ob User Mitarbeiter ist
        if (!auth()->user()->isMitarbeiter()) {
            abort(403, 'Zugriff verweigert. Nur für Mitarbeiter.');
        }

        return $next($request);
    }
}
