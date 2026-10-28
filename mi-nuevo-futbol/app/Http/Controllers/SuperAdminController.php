<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\CategoriaCancha;
use App\Models\Servicio;
use App\Models\TipoVehiculo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
     * Lista todos los negocios (dueños de canchas o salones) que existen.
     */
    public function index()
    {
        $this->verificarAcceso();

        // Los operadores de un parqueadero no son negocios: los administra su dueño
        $negocios = User::where('es_superadmin', false)
            ->whereNull('negocio_id')
            ->withCount(['canchas', 'servicios'])
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
     * Guarda la cuenta del negocio nuevo (nombre, tipo, correo y clave). Las canchas, profesionales,
     * servicios, horarios o tarifas los configura después el propio cliente desde su panel.
     */
    public function guardar(Request $request)
    {
        $this->verificarAcceso();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'slug' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:users,slug'],
            'tipo_negocio' => ['required', 'in:canchas,salon,parqueadero'],
        ]);

        $esParqueadero = $data['tipo_negocio'] === 'parqueadero';

        $negocio = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'slug' => ($data['slug'] ?? null) ?: $this->slugDisponible($data['name']),
            'tipo_negocio' => $data['tipo_negocio'],
            // Un parqueadero atiende todo el día; los demás parten de un horario que ellos ajustan
            'hora_apertura' => $esParqueadero ? '00:00' : '06:00',
            'hora_cierre' => $esParqueadero ? '23:59' : '23:00',
            'max_horas_por_reserva' => 1,
        ] + ($esParqueadero ? [
            // La base los deja en verde por defecto; un parqueadero parte de su azul
            'color_primario' => '#1F4E8C',
            'color_acento' => '#FFC247',
        ] : []));

        if ($esParqueadero) {
            TipoVehiculo::crearIniciales($negocio);
        }

        $aviso = $esParqueadero
            ? "Parqueadero '{$negocio->name}' creado. Ya puede iniciar sesión y definir su tarifa."
            : "Negocio '{$negocio->name}' creado. Ya puede iniciar sesión y configurar su horario y sus " . ($data['tipo_negocio'] === 'salon' ? 'profesionales y servicios' : 'canchas') . ". Su página pública es /reservas/{$negocio->slug}";

        return redirect()->route('superadmin.index')->with('exito', $aviso);
    }

    /** Slug único a partir del nombre (sin tildes ni espacios); agrega un número si ya existe. */
    private function slugDisponible(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'negocio';
        $slug = $base;

        for ($i = 2; User::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Formulario para editar un negocio existente y ver/gestionar sus
     * canchas (o profesionales) y servicios.
     */
    public function editar(User $negocio)
    {
        $this->verificarAcceso();

        $negocio->load(['canchas', 'servicios', 'tiposVehiculo']);

        return view('superadmin.editar', ['negocio' => $negocio]);
    }

    /**
     * Actualiza los datos de un negocio (y su contraseña, si se llenó ese campo).
     * El tipo de negocio (canchas/salón) NO se puede cambiar una vez creado.
     */
    public function actualizar(Request $request, User $negocio)
    {
        $this->verificarAcceso();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email,' . $negocio->id],
            'slug' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,slug,' . $negocio->id],
            // El horario no aplica a un parqueadero (su formulario no lo trae)
            'hora_apertura' => ['nullable', 'date_format:H:i'],
            'hora_cierre' => ['nullable', 'date_format:H:i', 'after:hora_apertura'],
            'max_horas_por_reserva' => ['nullable', 'integer', 'min:1', 'max:12'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $negocio->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'slug' => $data['slug'],
            'hora_apertura' => $data['hora_apertura'] ?? $negocio->hora_apertura,
            'hora_cierre' => $data['hora_cierre'] ?? $negocio->hora_cierre,
            'max_horas_por_reserva' => $data['max_horas_por_reserva'] ?? $negocio->max_horas_por_reserva,
        ]);

        if (! empty($data['password'])) {
            $negocio->update(['password' => Hash::make($data['password'])]);
        }

        return redirect()
            ->route('superadmin.editar', $negocio)
            ->with('exito', 'Negocio actualizado correctamente.');
    }

    /**
     * Elimina un negocio por completo (sus canchas, servicios y reservas
     * se borran en cascada).
     */
    public function eliminar(User $negocio)
    {
        $this->verificarAcceso();

        $this->soloClientes($negocio);

        $nombre = $negocio->name;
        $negocio->delete();

        return redirect()
            ->route('superadmin.index')
            ->with('exito', "Negocio '{$nombre}' eliminado junto con todos sus datos.");
    }

    /** Las acciones de cuenta (eliminar, desactivar, restablecer) solo valen para clientes, nunca para un super admin. */
    private function soloClientes(User $negocio): void
    {
        abort_if($negocio->es_superadmin || $negocio->id === Auth::id(), 403, 'Esta acción no se puede aplicar a una cuenta de administración.');
    }

    /**
     * Activa o desactiva un cliente. Desactivado: no puede iniciar sesión, se le cierra la
     * sesión que tenga abierta y su página pública de reservas queda en pausa.
     */
    public function cambiarEstado(User $negocio)
    {
        $this->verificarAcceso();
        $this->soloClientes($negocio);

        $negocio->update(['activo' => ! $negocio->activo]);

        if (! $negocio->activo) {
            DB::table('sessions')->where('user_id', $negocio->id)->delete();
        }

        return back()->with('exito', $negocio->activo
            ? "'{$negocio->name}' fue activado: ya puede iniciar sesión y recibir reservas."
            : "'{$negocio->name}' fue desactivado: no puede iniciar sesión y su página de reservas quedó en pausa.");
    }

    /**
     * Genera una contraseña temporal nueva para el cliente. Se muestra una sola vez al
     * super admin (en la base solo queda cifrada) y se cierran las sesiones abiertas del cliente.
     */
    public function restablecerClave(User $negocio)
    {
        $this->verificarAcceso();
        $this->soloClientes($negocio);

        // Sin caracteres que se confunden al leerlos o dictarlos (0/O, 1/l/I)
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        do {
            $clave = '';
            for ($i = 0; $i < 10; $i++) {
                $clave .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while (! preg_match('/[A-Z]/', $clave) || ! preg_match('/[a-z]/', $clave) || ! preg_match('/[0-9]/', $clave));

        $negocio->update(['password' => Hash::make($clave), 'remember_token' => null]);
        DB::table('sessions')->where('user_id', $negocio->id)->delete();

        return back()->with('credenciales', [
            'nombre' => $negocio->name,
            'email' => $negocio->email,
            'clave' => $clave,
            'acceso' => route('login'),
        ]);
    }

    /**
     * Agrega una cancha (o profesional, en un salón) nueva a un negocio.
     */
    public function agregarCancha(Request $request, User $negocio)
    {
        $this->verificarAcceso();

        $data = $request->validate(['nombre' => ['required', 'string', 'max:50']]);

        Cancha::create(['user_id' => $negocio->id, 'nombre' => $data['nombre']] + ($negocio->tipo_negocio === 'canchas' ? CategoriaCancha::valoresIniciales($negocio) : []));

        return redirect()
            ->route('superadmin.editar', $negocio)
            ->with('exito', 'Agregado correctamente.');
    }

    /**
     * Elimina una cancha/profesional (y sus reservas, en cascada).
     */
    public function eliminarCancha(Cancha $cancha)
    {
        $this->verificarAcceso();

        $negocioId = $cancha->user_id;
        $cancha->delete();

        return redirect()
            ->route('superadmin.editar', $negocioId)
            ->with('exito', 'Eliminado correctamente.');
    }

    /**
     * Agrega un servicio nuevo a un negocio tipo salón.
     */
    public function agregarServicio(Request $request, User $negocio)
    {
        $this->verificarAcceso();

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:50'],
            'duracion' => ['required', 'integer', 'min:5', 'max:480'],
            'precio' => ['nullable', 'numeric', 'min:0'],
        ]);

        Servicio::create([
            'user_id' => $negocio->id,
            'nombre' => $data['nombre'],
            'duracion_minutos' => $data['duracion'],
            'precio' => $data['precio'] ?? null,
        ]);

        return redirect()
            ->route('superadmin.editar', $negocio)
            ->with('exito', 'Servicio agregado.');
    }

    /**
     * Elimina un servicio (sus reservas quedan, pero sin servicio asociado).
     */
    public function eliminarServicio(Servicio $servicio)
    {
        $this->verificarAcceso();

        $negocioId = $servicio->user_id;
        $servicio->delete();

        return redirect()
            ->route('superadmin.editar', $negocioId)
            ->with('exito', 'Servicio eliminado.');
    }
}