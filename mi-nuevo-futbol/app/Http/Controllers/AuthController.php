<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function mostrarLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->rutaPanel());
        }

        // Sin ?tipo= se muestra primero el portal para elegir la línea de negocio;
        // con ?tipo=canchas, salon o parqueadero, el login con el fondo de ese negocio.
        $tipo = in_array($request->query('tipo'), ['canchas', 'salon', 'parqueadero'], true) ? $request->query('tipo') : null;

        return view('auth.login', ['tipo' => $tipo]);
    }

    /**
     * Procesa el intento de inicio de sesión.
     */
    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Si la contraseña es correcta pero la cuenta está desactivada, se le dice claro (no "datos incorrectos")
        $usuario = User::where('email', $credenciales['email'])->first();
        if ($usuario && ! $usuario->es_superadmin && ! $usuario->activo && Hash::check($credenciales['password'], $usuario->password)) {
            return back()
                ->withErrors(['email' => 'Tu cuenta está desactivada. Comunícate con el administrador de la plataforma.'])
                ->onlyInput('email');
        }

        if (Auth::attempt($credenciales, $request->boolean('recordar'))) {
            $request->session()->regenerate();

            return redirect()->intended(route(Auth::user()->rutaPanel()));
        }

        return back()
            ->withErrors(['email' => 'Correo o contraseña incorrectos.'])
            ->onlyInput('email');
    }

    /**
     * Cierra la sesión.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}