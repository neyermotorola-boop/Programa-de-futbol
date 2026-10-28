<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmacionReserva;
use App\Models\Cancha;
use App\Models\CategoriaCancha;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Panel de configuración de cada negocio: marca (logo, nombre, colores),
 * horario de atención, canchas o profesionales, y servicios.
 * Cada negocio solo puede ver y modificar lo suyo.
 */
class ConfiguracionController extends Controller
{
    private function negocio(): User
    {
        $negocio = Auth::user();
        abort_if($negocio->es_superadmin || $negocio->esOperador(), 403);

        return $negocio;
    }

    private function volver(string $seccion, string $mensaje)
    {
        // Un parqueadero no tiene página de configuración: su marca se edita desde su propio panel
        if (Auth::user()->esParqueadero()) {
            return redirect()->to(route('parqueadero.index') . '#' . $seccion)->with('exito', $mensaje);
        }

        return redirect()->to(route('configuracion.index') . '#' . $seccion)->with('exito', $mensaje);
    }

    public function index()
    {
        $negocio = $this->negocio();
        if ($negocio->tipo_negocio === 'canchas') {
            CategoriaCancha::crearPredeterminadas($negocio);   // por si es un negocio antiguo sin categorías
        }
        $negocio->load(['canchas.deporte', 'canchas.superficieCategoria', 'servicios', 'categoriasCancha']);

        // Reserva de ejemplo (nunca se guarda) para la vista previa del correo
        $muestra = Reserva::deMuestra($negocio);

        return view('configuracion.index', [
            'negocio' => $negocio,
            'vistaCorreo' => ConfirmacionReserva::vista($muestra),
            'datosEjemplo' => ConfirmacionReserva::datos($muestra),
            'textosPorDefecto' => [
                'asunto' => User::EMAIL_ASUNTO,
                'mensaje' => User::EMAIL_MENSAJE,
                'pie' => User::EMAIL_PIE,
            ],
            'deportes' => $negocio->categoriasCancha->where('tipo', 'deporte')->values(),
            'superficies' => $negocio->categoriasCancha->where('tipo', 'superficie')->values(),
        ]);
    }

    public function marca(Request $request)
    {
        $negocio = $this->negocio();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'razon_social' => ['nullable', 'string', 'max:150'],
            'eslogan' => ['nullable', 'string', 'max:150'],
            'telefono_contacto' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'color_primario' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_acento' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // SVG no se permite: podría llevar scripts embebidos
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'quitar_logo' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('logo')) {
            $this->borrarLogo($negocio);
            $nombre = $negocio->id . '-' . time() . '.' . $request->file('logo')->extension();
            $request->file('logo')->move(public_path('uploads/logos'), $nombre);
            $negocio->logo_path = 'uploads/logos/' . $nombre;
        } elseif ($request->boolean('quitar_logo')) {
            $this->borrarLogo($negocio);
            $negocio->logo_path = null;
        }

        unset($data['logo'], $data['quitar_logo']);
        $negocio->fill($data)->save();

        return $this->volver('marca', $negocio->esParqueadero()
            ? 'Marca actualizada. Ya se ve en tu panel.'
            : 'Marca actualizada. Así la verán tus clientes en tu página de reservas.');
    }

    private function borrarLogo(User $negocio): void
    {
        // Solo se borran logos subidos por el negocio, nunca otros archivos de /public
        if ($negocio->logo_path && str_starts_with($negocio->logo_path, 'uploads/logos/')) {
            File::delete(public_path($negocio->logo_path));
        }
    }

    public function horario(Request $request)
    {
        $negocio = $this->negocio();
        $esSalon = $negocio->tipo_negocio === 'salon';

        $data = $request->validate([
            'hora_apertura' => ['required', 'date_format:H:i'],
            'hora_cierre' => ['required', 'date_format:H:i', 'after:hora_apertura'],
            'dias_atencion' => ['required', 'array', 'min:1'],
            'dias_atencion.*' => ['integer', 'between:1,7'],
            'max_horas_por_reserva' => [$esSalon ? 'nullable' : 'required', 'integer', 'min:1', 'max:12'],
            'numero_puestos' => [$esSalon ? 'required' : 'nullable', 'integer', 'min:1', 'max:50'],
            'tipo_puesto' => [$esSalon ? 'required' : 'nullable', Rule::in(array_keys(User::TIPOS_PUESTO))],
            'intervalo_agenda' => [$esSalon ? 'required' : 'nullable', Rule::in(array_keys(User::INTERVALOS_AGENDA))],
            'margen_minutos' => [$esSalon ? 'required' : 'nullable', 'integer', 'min:0', 'max:120'],
            'anticipacion_minutos' => [$esSalon ? 'required' : 'nullable', Rule::in(array_keys(User::ANTICIPACIONES))],
        ], [
            'dias_atencion.required' => 'Elige al menos un día de atención.',
        ]);

        $data['dias_atencion'] = array_values(array_map('intval', $data['dias_atencion']));
        sort($data['dias_atencion']);
        $negocio->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return $this->volver('horario', 'Horario y capacidad actualizados.');
    }

    /**
     * Ajustes generales: cómo se confirman las reservas y el correo de confirmación.
     */
    public function general(Request $request)
    {
        $negocio = $this->negocio();

        $data = $request->validate([
            'email_asunto' => ['nullable', 'string', 'max:150'],
            'email_mensaje' => ['nullable', 'string', 'max:500'],
            'email_pie' => ['nullable', 'string', 'max:300'],
        ]);

        // Un interruptor apagado no se envía en el formulario: se guarda como false
        $data['confirmacion_automatica'] = $request->boolean('confirmacion_automatica');
        $data['email_confirmacion_activo'] = $request->boolean('email_confirmacion_activo');
        // Vacío = vuelve al texto estándar
        foreach (['email_asunto', 'email_mensaje', 'email_pie'] as $campo) {
            $data[$campo] = trim((string) ($data[$campo] ?? '')) ?: null;
        }

        $negocio->update($data);

        return $this->volver('general', 'Configuración general actualizada.');
    }

    // ---------- Categorías de canchas: deportes y superficies que define el negocio ----------

    private function reglasCategoria(Request $request, User $negocio, ?CategoriaCancha $actual = null): array
    {
        $tipo = $actual?->tipo ?? $request->input('tipo');
        abort_unless(in_array($tipo, ['deporte', 'superficie'], true), 422);

        $unico = Rule::unique('categorias_cancha', 'nombre')->where('user_id', $negocio->id)->where('tipo', $tipo)->ignore($actual?->id);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:40', $unico],
            'base' => ['required', Rule::in(array_keys($tipo === 'deporte' ? CategoriaCancha::BASES_DEPORTE : CategoriaCancha::ESTILOS))],
            'color' => [$tipo === 'superficie' ? 'required' : 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'nombre.unique' => 'Ya tienes ' . ($tipo === 'deporte' ? 'un deporte' : 'una superficie') . ' con ese nombre.',
        ]);

        return ['tipo' => $tipo, 'nombre' => trim($data['nombre']), 'base' => $data['base'], 'color' => $tipo === 'superficie' ? strtoupper($data['color']) : null];
    }

    public function crearCategoria(Request $request)
    {
        $negocio = $this->negocio();
        abort_unless($negocio->tipo_negocio === 'canchas', 404);

        $data = $this->reglasCategoria($request, $negocio);
        CategoriaCancha::create($data + ['user_id' => $negocio->id, 'orden' => (int) $negocio->categoriasCancha()->max('orden') + 1]);

        return $this->volver('categorias', "'{$data['nombre']}' agregado.");
    }

    public function actualizarCategoria(Request $request, CategoriaCancha $categoria)
    {
        $negocio = $this->negocio();
        abort_unless($categoria->user_id === $negocio->id, 403);

        $categoria->update($this->reglasCategoria($request, $negocio, $categoria));

        return $this->volver('categorias', "'{$categoria->nombre}' actualizado.");
    }

    public function eliminarCategoria(CategoriaCancha $categoria)
    {
        $negocio = $this->negocio();
        abort_unless($categoria->user_id === $negocio->id, 403);

        if ($negocio->categoriasCancha()->where('tipo', $categoria->tipo)->count() <= 1) {
            return redirect()->to(route('configuracion.index') . '#categorias')->withErrors(['categoria' => 'Debe quedar al menos ' . ($categoria->tipo === 'deporte' ? 'un deporte' : 'una superficie') . '.']);
        }
        if ($enUso = $categoria->enUso()) {
            return redirect()->to(route('configuracion.index') . '#categorias')->withErrors(['categoria' => "'{$categoria->nombre}' lo usan {$enUso} " . ($enUso === 1 ? 'cancha' : 'canchas') . '. Cámbialas a otra categoría antes de eliminarla.']);
        }

        $categoria->delete();

        return $this->volver('categorias', "'{$categoria->nombre}' eliminado.");
    }

    /**
     * Reglas comunes para crear/editar una cancha o profesional.
     */
    private function validarRecurso(Request $request, User $negocio): array
    {
        $reglas = ['nombre' => ['required', 'string', 'max:50'], 'activa' => ['nullable', 'boolean']];

        if ($negocio->tipo_negocio === 'salon') {
            $reglas += [
                'especialidad' => ['nullable', 'string', 'max:80'],
                'dias_disponibles' => ['required', 'array', 'min:1'],
                'dias_disponibles.*' => ['integer', 'between:1,7'],
                // Horario propio y descanso (vacío = el horario del negocio / sin descanso)
                'hora_inicio_trabajo' => ['nullable', 'date_format:H:i', 'required_with:hora_fin_trabajo'],
                'hora_fin_trabajo' => ['nullable', 'date_format:H:i', 'required_with:hora_inicio_trabajo', 'after:hora_inicio_trabajo'],
                'descanso_inicio' => ['nullable', 'date_format:H:i', 'required_with:descanso_fin'],
                'descanso_fin' => ['nullable', 'date_format:H:i', 'required_with:descanso_inicio', 'after:descanso_inicio'],
            ];
        } else {
            $propia = fn (string $tipo) => Rule::exists('categorias_cancha', 'id')->where('user_id', $negocio->id)->where('tipo', $tipo);
            $reglas += [
                'deporte_id' => ['required', $propia('deporte')],
                'superficie_id' => ['required', $propia('superficie')],
                'tamano' => ['nullable', 'string', 'max:20'],
                'iluminacion' => ['nullable', 'boolean'],
                'techada' => ['nullable', 'boolean'],
                'precio_hora' => ['nullable', 'numeric', 'min:0'],
                // Foto real de la cancha (SVG no: puede llevar scripts)
                'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
                'quitar_foto' => ['nullable', 'boolean'],
                'foto_modo' => ['nullable', Rule::in(['entorno', 'piso'])],
            ];
        }

        $data = $request->validate($reglas);
        $data['activa'] = $request->boolean('activa');

        if ($negocio->tipo_negocio === 'salon') {
            $data['dias_disponibles'] = array_values(array_map('intval', $data['dias_disponibles'] ?? []));
            $this->validarHorarioPropio($data, $negocio);
        } else {
            $data['iluminacion'] = $request->boolean('iluminacion');
            $data['techada'] = $request->boolean('techada');

            // El formato de juego tiene que existir para ese deporte; si no, el primero (o ninguno)
            $base = CategoriaCancha::find($data['deporte_id'])->base;
            $formatos = array_keys(CategoriaCancha::FORMATOS[$base] ?? []);
            $data['tamano'] = in_array($data['tamano'] ?? null, $formatos, true) ? $data['tamano'] : ($formatos[0] ?? null);
            $data['foto_modo'] = $data['foto_modo'] ?? 'entorno';
            unset($data['foto'], $data['quitar_foto']);
        }

        return $data;
    }

    /** Guarda la foto subida (reemplazando la anterior) o la quita si se pidió. */
    private function guardarFoto(Request $request, Cancha $cancha): void
    {
        if ($request->hasFile('foto')) {
            $this->borrarFoto($cancha);
            $archivo = $request->file('foto');
            $nombre = $cancha->id . '-' . time() . '.' . $archivo->extension();
            $archivo->move(public_path('uploads/canchas'), $nombre);
            $cancha->update(['foto_path' => 'uploads/canchas/' . $nombre]);
        } elseif ($request->boolean('quitar_foto')) {
            $this->borrarFoto($cancha);
            $cancha->update(['foto_path' => null]);
        }
    }

    /** Solo se borran archivos de la carpeta de fotos de canchas, nunca otros de /public. */
    private function borrarFoto(Cancha $cancha): void
    {
        if ($cancha->foto_path && str_starts_with($cancha->foto_path, 'uploads/canchas/')) {
            File::delete(public_path($cancha->foto_path));
        }
    }

    public function agregarRecurso(Request $request)
    {
        $negocio = $this->negocio();
        $data = $this->validarRecurso($request, $negocio);
        $cancha = Cancha::create($data + ['user_id' => $negocio->id]);
        $this->guardarFoto($request, $cancha);

        return $this->volver('recursos', "'{$data['nombre']}' agregada.");
    }

    public function actualizarRecurso(Request $request, Cancha $cancha)
    {
        $negocio = $this->negocio();
        abort_unless($cancha->user_id === $negocio->id, 403);

        $cancha->update($this->validarRecurso($request, $negocio));
        $this->guardarFoto($request, $cancha);

        return $this->volver('recurso-' . $cancha->id, "'{$cancha->nombre}' actualizada.");
    }

    public function eliminarRecurso(Cancha $cancha)
    {
        $negocio = $this->negocio();
        abort_unless($cancha->user_id === $negocio->id, 403);

        $this->borrarFoto($cancha);
        $cancha->delete();

        return $this->volver('recursos', "'{$cancha->nombre}' eliminada junto con sus reservas.");
    }

    /**
     * El horario propio de una profesional tiene que caber en el del negocio, y su descanso dentro de su horario.
     * Falla con un mensaje claro en el campo que corresponde.
     */
    private function validarHorarioPropio(array &$data, User $negocio): void
    {
        $errores = [];
        $minutos = fn (string $h) => (int) substr($h, 0, 2) * 60 + (int) substr($h, 3, 2);
        $desde = $data['hora_inicio_trabajo'] ?? null;
        $hasta = $data['hora_fin_trabajo'] ?? null;

        if ($desde && $hasta && ($minutos($desde) < $minutos($negocio->hora_apertura) || $minutos($hasta) > $minutos($negocio->hora_cierre))) {
            $errores['hora_inicio_trabajo'] = "El horario de la profesional debe estar dentro del horario del negocio ({$negocio->hora_apertura} a {$negocio->hora_cierre}).";
        }

        $d1 = $data['descanso_inicio'] ?? null;
        $d2 = $data['descanso_fin'] ?? null;
        if ($d1 && $d2) {
            [$ini, $fin] = [$desde ?: $negocio->hora_apertura, $hasta ?: $negocio->hora_cierre];
            if ($minutos($d1) < $minutos($ini) || $minutos($d2) > $minutos($fin)) {
                $errores['descanso_inicio'] = "El descanso debe estar dentro de su horario de trabajo ({$ini} a {$fin}).";
            }
        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        // Vacío = sin horario propio / sin descanso
        foreach (['hora_inicio_trabajo', 'hora_fin_trabajo', 'descanso_inicio', 'descanso_fin'] as $campo) {
            $data[$campo] = $data[$campo] ?? null;
        }
    }

    private function validarServicio(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'icono' => ['nullable', Rule::in(array_keys(Servicio::ICONOS))],
            'duracion_minutos' => ['required', 'integer', 'min:5', 'max:480'],
            // Minutos libres después de este servicio; vacío = el margen del negocio
            'margen_minutos' => ['nullable', 'integer', 'min:0', 'max:120'],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'confirmacion' => ['nullable', Rule::in(['heredar', 'si', 'no'])],
        ]);
        $data['activo'] = $request->boolean('activo');
        $data['permite_diseno'] = $request->boolean('permite_diseno');
        // heredar = usa la configuración general; si/no = regla propia de este servicio
        $data['confirmacion_automatica'] = match ($data['confirmacion'] ?? 'heredar') {
            'si' => true,
            'no' => false,
            default => null,
        };
        unset($data['confirmacion']);

        return $data;
    }

    public function agregarServicio(Request $request)
    {
        $negocio = $this->negocio();
        abort_unless($negocio->tipo_negocio === 'salon', 404);

        Servicio::create($this->validarServicio($request) + ['user_id' => $negocio->id]);

        return $this->volver('servicios', 'Servicio agregado.');
    }

    public function actualizarServicio(Request $request, Servicio $servicio)
    {
        abort_unless($servicio->user_id === $this->negocio()->id, 403);

        $servicio->update($this->validarServicio($request));

        return $this->volver('servicio-' . $servicio->id, "Servicio '{$servicio->nombre}' actualizado.");
    }

    public function eliminarServicio(Servicio $servicio)
    {
        abort_unless($servicio->user_id === $this->negocio()->id, 403);

        $servicio->delete();

        return $this->volver('servicios', "Servicio '{$servicio->nombre}' eliminado.");
    }
}
