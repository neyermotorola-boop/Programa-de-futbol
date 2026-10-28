<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los paneles de reservas (canchas y salón) no aplican a un parqueadero:
 * si entra por una de sus URLs, se le lleva a su propio panel.
 */
class SoloReservas
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->esParqueadero()) {
            return redirect()->route('parqueadero.index');
        }

        return $next($request);
    }
}
