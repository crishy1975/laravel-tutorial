<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Rollen-Middleware registrieren
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'mitarbeiter' => \App\Http\Middleware\MitarbeiterMiddleware::class,
        ]);

        // Kein Browser-Cache für HTML-Seiten (Back-Button zeigt frische Daten)
        $middleware->web(append: [
            \App\Http\Middleware\NoCache::class,
        ]);
    })


    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
