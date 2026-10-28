<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * El dueño del parqueadero crea y administra a sus operadores: empleados que solo
 * registran ingresos y salidas, sin acceso a tarifas, métricas ni historial.
 */
class ParqueaderoEquipoController extends Controller
{
    /** Solo el dueño de un parqueadero (no un operador) administra el equipo. */
    private function dueno(): User
    {
        $usuario = Auth::user();
        abort_unless($usuario && $usuario->esParqueadero() && ! $usuario->esOperador(), 403);

        return $usuario;
    }

    private function delEquipo(User $operador): void
    {
        abort_unless($operador->negocio_id === $this->dueno()->id, 404);
    }

    public function crear(Request $request)
    {
        $dueno = $this->dueno();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'tipo_negocio' => 'parqueadero',
            'negocio_id' => $dueno->id,
        ]);

        return redirect()->to(route('parqueadero.index') . '#equipo')
            ->with('exito', "Operador {$data['name']} creado. Ya puede entrar con {$data['email']} para registrar ingresos y salidas.");
    }

    /** Activa o desactiva a un operador. Desactivado: no entra y se le cierra la sesión abierta. */
    public function estado(User $operador)
    {
        $this->delEquipo($operador);

        $operador->update(['activo' => ! $operador->activo]);

        if (! $operador->activo) {
            DB::table('sessions')->where('user_id', $operador->id)->delete();
        }

        return redirect()->to(route('parqueadero.index') . '#equipo')
            ->with('exito', $operador->activo ? "{$operador->name} fue activado." : "{$operador->name} fue desactivado y ya no puede entrar. Su historial se conserva.");
    }

    public function clave(Request $request, User $operador)
    {
        $this->delEquipo($operador);

        $data = $request->validate(['password' => ['required', 'string', 'min:6']]);

        $operador->update(['password' => Hash::make($data['password']), 'remember_token' => null]);
        DB::table('sessions')->where('user_id', $operador->id)->delete();

        return redirect()->to(route('parqueadero.index') . '#equipo')
            ->with('exito', "Se cambió la contraseña de {$operador->name}.");
    }
}
