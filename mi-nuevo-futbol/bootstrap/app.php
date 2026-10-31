<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Detrás del proxy de Railway (u otro hosting) la conexión llega por HTTP: se confía en sus
        // cabeceras para que los enlaces, los formularios y las cookies salgan en https.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'cuenta.activa' => \App\Http\Middleware\CuentaActiva::class,
            'solo.reservas' => \App\Http\Middleware\SoloReservas::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
