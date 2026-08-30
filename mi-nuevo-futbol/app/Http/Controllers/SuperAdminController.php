<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    /**
     * Solo el super admin puede usar cualquier método de este controlador.
     */
    private function verificarAcceso(): void
    {
        abort_unless(Auth::user()?->es_superadmin, 403);
    }

    /**
     * Lista todos los negocios (dueños de canchas) que existen.
     */
    public function index()
    {
        $this->verificarAcceso();

        $negocios = User::where('es_superadmin', false)
            ->withCount('canchas')
            ->orderBy('name')
            ->get();

        return view('superadmin.index', ['negocios' => $negocios]);
    }

    /**
     * Formulario para crear un negocio nuevo.
     */
    public function crear()
    {
        $this->verificarAcceso();

        return view('superadmin.crear');
    }

    /**
     * Guarda el negocio nuevo junto con sus canchas iniciales.
     */
    public function guardar(Request $request)
    {
        $this->verificarAcceso();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'slug' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,slug'],
            'hora_apertura' => ['required', 'date_format:H:i'],
            'hora_cierre' => ['required', 'date_format:H:i', 'after:hora_apertura'],
            'max_horas_por_reserva' => ['required', 'integer', 'min:1', 'max:12'],
            'canchas' => ['required', 'array', 'min:1'],
            'canchas.*' => ['required', 'string', 'max:50'],
        ]);

        $negocio = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'slug' => $data['slug'],
            'hora_apertura' => $data['hora_apertura'],
            'hora_cierre' => $data['hora_cierre'],
            'max_horas_por_reserva' => $data['max_horas_por_reserva'],
        ]);

        foreach ($data['canchas'] as $nombreCancha) {
            if (trim($nombreCancha) !== '') {
                Cancha::create(['user_id' => $negocio->id, 'nombre' => $nombreCancha]);
            }
        }

        return redirect()
            ->route('superadmin.index')
            ->with('exito', "Negocio '{$negocio->name}' creado. Su página pública es /reservas/{$negocio->slug}");
    }

    /**
     * Formulario para editar un negocio existente y ver/gestionar sus canchas.
     */
    public function editar(User $negocio)
    {
        $this->verificarAcceso();

        $negocio->load('canchas');

        return view('superadmin.editar', ['negocio' => $negocio]);
    }

    /**
     * Actualiza los datos de un negocio (y su contraseña, si se llenó ese campo).
     */
    public function actualizar(Request $request, User $negocio)
    {
        $this->verificarAcceso();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email,' . $negocio->id],
            'slug' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,slug,' . $negocio->id],
            'hora_apertura' => ['required', 'date_format:H:i'],
            'hora_cierre' => ['required', 'date_format:H:i', 'after:hora_apertura'],
            'max_horas_por_reserva' => ['required', 'integer', 'min:1', 'max:12'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $negocio->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'slug' => $data['slug'],
            'hora_apertura' => $data['hora_apertura'],
            'hora_cierre' => $data['hora_cierre'],
            'max_horas_por_reserva' => $data['max_horas_por_reserva'],
        ]);

        if (! empty($data['password'])) {
            $negocio->update(['password' => Hash::make($data['password'])]);
        }

        return redirect()
            ->route('superadmin.editar', $negocio)
            ->with('exito', 'Negocio actualizado correctamente.');
    }

    /**
     * Elimina un negocio por completo (sus canchas y reservas se borran en cascada).
     */
    public function eliminar(User $negocio)
    {
        $this->verificarAcceso();

        $nombre = $negocio->name;
        $negocio->delete();

        return redirect()
            ->route('superadmin.index')
            ->with('exito', "Negocio '{$nombre}' eliminado junto con sus canchas y reservas.");
    }

    /**
     * Agrega una cancha nueva a un negocio existente.
     */
    public function agregarCancha(Request $request, User $negocio)
    {
        $this->verificarAcceso();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:50'],
        ]);

        Cancha::create(['user_id' => $negocio->id, 'nombre' => $data['nombre']]);

        return redirect()
            ->route('superadmin.editar', $negocio)
            ->with('exito', 'Cancha agregada.');
    }

    /**
     * Elimina una cancha (y sus reservas, en cascada).
     */
    public function eliminarCancha(Cancha $cancha)
    {
        $this->verificarAcceso();

        $negocioId = $cancha->user_id;
        $cancha->delete();

        return redirect()
            ->route('superadmin.editar', $negocioId)
            ->with('exito', 'Cancha eliminada.');
    }
}