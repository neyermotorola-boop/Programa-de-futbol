<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el super admin desactiva una cuenta mientras esa persona tiene la sesión abierta,
 * en su siguiente clic se le cierra la sesión.
 */
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        // Un operador también queda fuera si su dueño es desactivado
        $apagado = $usuario && ! $usuario->es_superadmin
            && (! $usuario->activo || ($usuario->esOperador() && ! $usuario->dueno?->activo));

        if ($apagado) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está desactivada. Comunícate con el administrador de la plataforma.',
            ]);
        }

        return $next($request);
    }
}
