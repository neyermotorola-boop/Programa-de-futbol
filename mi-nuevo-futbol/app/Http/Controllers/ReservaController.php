<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use App\Services\AgendaSalon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReservaController extends Controller
{
    /**
     * Página pública de reservas de un negocio. Si es tipo 'salon' también
     * carga sus servicios, para que el cliente elija qué se va a hacer.
     */
    public function index(string $slug)
    {
        $negocio = User::where('slug', $slug)->firstOrFail();

        // Un parqueadero no recibe reservas: no tiene página pública
        abort_if($negocio->esParqueadero(), 404);

        if (! $negocio->activo) {
            return response()->view('reservas.inactivo', ['negocio' => $negocio], 503);
        }
        $canchas = Cancha::with(['deporte', 'superficieCategoria'])->where('user_id', $negocio->id)->where('activa', true)->get();
        $servicios = Servicio::where('user_id', $negocio->id)->where('activo', true)->get();

        return view('reservas.index', [
            'negocio' => $negocio,
            'canchas' => $canchas,
            'servicios' => $servicios,
            'horaApertura' => $negocio->hora_apertura,
            'horaCierre' => $negocio->hora_cierre,
            'maxHoras' => $negocio->max_horas_por_reserva,
            // "Hoy" en la zona del negocio: de ahí en adelante se puede reservar
            'hoy' => User::ahora()->toDateString(),
        ]);
    }

    /**
     * (Negocios tipo 'canchas') Rangos ya ocupados para una cancha y fecha dadas.
     */
    public function disponibilidad(string $slug, Request $request)
    {
        $negocio = $this->negocioActivo($slug);

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'cancha_id' => ['required', 'exists:canchas,id'],
        ]);

        $cancha = Cancha::where('id', $data['cancha_id'])
            ->where('user_id', $negocio->id)
            ->firstOrFail();

        if (! $negocio->atiendeEl($data['fecha'])) {
            return response()->json(['ocupadas' => [], 'cerrado' => true]);
        }

        $ocupadas = Reserva::enFecha($data['fecha'])
            ->where('cancha_id', $cancha->id)
            ->where('estado', '!=', 'cancelada')
            ->orderBy('hora_inicio')
            ->get(['hora_inicio', 'hora_fin'])
            ->map(fn ($r) => [
                'inicio' => substr($r->hora_inicio, 0, 5),
                'fin' => substr($r->hora_fin, 0, 5),
            ]);

        // Las horas anteriores a "minimo" ya pasaron (o no cumplen la anticipación del negocio)
        return response()->json(['ocupadas' => $ocupadas, 'minimo' => $negocio->horaMinimaReservable($data['fecha'])]);
    }

    /**
     * (Negocios tipo 'salon') Horas disponibles para uno o varios servicios en una fecha dada, con una
     * profesional específica o con "cualquiera". La duración la determinan los servicios (se suman),
     * no el cliente. Las reglas viven en AgendaSalon, las mismas que se aplican al guardar la cita.
     */
    public function disponibilidadServicio(string $slug, Request $request)
    {
        $negocio = $this->negocioActivo($slug);

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'servicio_ids' => ['required', 'array', 'min:1', 'max:6'],
            'servicio_ids.*' => ['integer'],
            'profesional_id' => ['nullable', 'string'],
        ]);

        $combo = AgendaSalon::combinar($negocio, $data['servicio_ids']);
        if (! $combo) {
            return response()->json(['horas' => [], 'error' => 'servicio'], 422);
        }

        if (! $negocio->atiendeEl($data['fecha'])) {
            return response()->json(['horas' => [], 'cerrado' => true]);
        }

        $agenda = new AgendaSalon($negocio);
        $candidatas = $agenda->profesionales($data['fecha'], $data['profesional_id'] ?? 'cualquiera');

        return response()->json([
            'horas' => $candidatas->isEmpty() ? [] : $agenda->horas($data['fecha'], $combo['duracion'], $combo['margen'], $candidatas),
            'duracion' => $combo['duracion'],
        ]);
    }

    /**
     * Crea una reserva nueva. Se comporta distinto según el tipo de negocio:
     * 'canchas' = el cliente elige duración libre; 'salon' = la duración
     * la fija el servicio elegido, y puede pedir "cualquier" profesional.
     */
    public function store(string $slug, Request $request)
    {
        $negocio = $this->negocioActivo($slug);

        return $negocio->tipo_negocio === 'salon'
            ? $this->storeSalon($negocio, $slug, $request)
            : $this->storeCanchas($negocio, $slug, $request);
    }

    /** Negocio de una ruta pública; si el super admin lo desactivó, la ruta deja de existir para el público. */
    private function negocioActivo(string $slug): User
    {
        $negocio = User::where('slug', $slug)->firstOrFail();
        abort_unless($negocio->activo, 404);

        return $negocio;
    }

    private function storeCanchas(User $negocio, string $slug, Request $request)
    {
        $horaApertura = $negocio->hora_apertura;
        $horaCierre = $negocio->hora_cierre;
        $maxHoras = $negocio->max_horas_por_reserva;

        $data = $request->validate([
            'nombre_cliente' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[0-9]{7,15}$/'],
            'email_cliente' => ['nullable', 'email', 'max:120'],
            'cancha_id' => ['required', 'exists:canchas,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'duracion_horas' => ['required', 'integer', 'min:1', "max:{$maxHoras}"],
        ]);

        $cancha = Cancha::where('id', $data['cancha_id'])
            ->where('user_id', $negocio->id)
            ->first();

        if (! $cancha) {
            return back()->withErrors(['cancha_id' => 'Esa cancha no existe.'])->withInput();
        }

        if ($data['fecha'] < User::ahora()->toDateString()) {
            return back()->withErrors(['fecha' => 'Esa fecha ya pasó. Por favor elige otra.'])->withInput();
        }

        if (! $negocio->atiendeEl($data['fecha'])) {
            return back()->withErrors(['fecha' => 'Ese día no hay atención. Por favor elige otra fecha.'])->withInput();
        }

        $minimo = $negocio->horaMinimaReservable($data['fecha']);
        if ($minimo !== null && $data['hora_inicio'] < $minimo) {
            return back()->withErrors(['hora_inicio' => 'Esa hora ya pasó. Por favor elige una hora más adelante.'])->withInput();
        }

        $inicio = Carbon::createFromFormat('H:i', $data['hora_inicio']);
        $fin = $inicio->copy()->addHours((int) $data['duracion_horas']);

        if ($inicio->format('H:i') < $horaApertura || $fin->format('H:i') > $horaCierre) {
            return back()->withErrors([
                'hora_inicio' => "El horario de atención es de {$horaApertura} a {$horaCierre}.",
            ])->withInput();
        }

        return DB::transaction(function () use ($data, $inicio, $fin, $cancha, $negocio, $slug) {
            $cruce = Reserva::enFecha($data['fecha'])
                ->where('cancha_id', $cancha->id)
                ->where('estado', '!=', 'cancelada')
                ->where('hora_inicio', '<', $fin->format('H:i:s'))
                ->where('hora_fin', '>', $inicio->format('H:i:s'))
                ->lockForUpdate()
                ->exists();

            if ($cruce) {
                return back()->withErrors([
                    'hora_inicio' => 'Ese horario ya fue reservado. Por favor elige otra hora.',
                ])->withInput();
            }

            $reserva = Reserva::create([
                'user_id' => $negocio->id,
                'cancha_id' => $cancha->id,
                'nombre_cliente' => $data['nombre_cliente'],
                'telefono' => $data['telefono'],
                'email_cliente' => $data['email_cliente'] ?? null,
                'fecha' => $data['fecha'],
                'hora_inicio' => $inicio->format('H:i:s'),
                'hora_fin' => $fin->format('H:i:s'),
                'duracion_horas' => $data['duracion_horas'],
                'precio' => $cancha->precio_hora ? $cancha->precio_hora * (int) $data['duracion_horas'] : null,
                'estado' => $negocio->confirmaSola() ? 'confirmada' : 'pendiente',
            ]);
            $reserva->update(['radicado' => Reserva::radicadoPara($negocio, $reserva->id)]);

            if ($reserva->estado === 'confirmada') {
                $reserva->notificarConfirmacion();
            }

            return redirect()
                ->route('reservas.index', $slug)
                ->with('reserva_ok', $this->datosExito($reserva, ['etiqueta' => 'Cancha', 'recurso' => $cancha->nombre]))
                ->with('exito', ($reserva->estado === 'confirmada' ? "¡Reserva confirmada! " : "¡Reserva recibida! ")
                    . "{$reserva->nombre_cliente}, tu cancha " . ($reserva->estado === 'confirmada' ? "está confirmada" : "quedó separada, pendiente de confirmación") . " para el "
                    . $reserva->fecha->format('d/m/Y') . " de " . $inicio->format('H:i') . " a " . $fin->format('H:i') . ".");
        });
    }

    /**
     * Resumen que se muestra en el pop-up de agradecimiento tras reservar.
     */
    private function datosExito(Reserva $r, array $extra): array
    {
        $r->loadMissing('usuario');

        return $extra + [
            'radicado' => $r->radicado,
            'cliente' => $r->nombre_cliente,
            'confirmada' => $r->estado === 'confirmada',
            'fecha' => ucfirst($r->fecha->locale('es')->translatedFormat('l j \d\e F \d\e Y')),
            'hora' => date('g:i A', strtotime($r->hora_inicio)) . ' – ' . date('g:i A', strtotime($r->hora_fin)),
            'correo' => $r->email_cliente && $r->usuario?->email_confirmacion_activo && $r->estado === 'confirmada' ? $r->email_cliente : null,
        ];
    }

    private function storeSalon(User $negocio, string $slug, Request $request)
    {
        $data = $request->validate([
            'nombre_cliente' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[0-9]{7,15}$/'],
            'email_cliente' => ['nullable', 'email', 'max:120'],
            'servicio_ids' => ['required', 'array', 'min:1', 'max:6'],
            'servicio_ids.*' => ['integer'],
            'profesional_id' => ['nullable', 'string'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            // Foto del diseño de uñas: solo imágenes, y SVG no (puede llevar scripts)
            'diseno' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'diseno_forma' => ['nullable', 'in:almendra,cuadrada,redonda,ballerina'],
        ]);

        $combo = AgendaSalon::combinar($negocio, $data['servicio_ids']);
        if (! $combo) {
            return back()->withErrors(['servicio_ids' => 'Alguno de los servicios elegidos ya no está disponible.'])->withInput();
        }

        if ($data['fecha'] < User::ahora()->toDateString()) {
            return back()->withErrors(['fecha' => 'Esa fecha ya pasó. Por favor elige otra.'])->withInput();
        }

        if (! $negocio->atiendeEl($data['fecha'])) {
            return back()->withErrors(['fecha' => 'Ese día no hay atención. Por favor elige otra fecha.'])->withInput();
        }

        $agenda = new AgendaSalon($negocio);
        $profesionalId = $data['profesional_id'] ?? 'cualquiera';
        $inicioMin = AgendaSalon::minutos($data['hora_inicio']);
        $finMin = $inicioMin + $combo['duracion'];

        return DB::transaction(function () use ($data, $combo, $agenda, $negocio, $slug, $profesionalId, $inicioMin, $finMin, $request) {
            // Se bloquea el negocio: dos clientes que reservan a la vez no pueden tomar el mismo cupo ni la misma silla
            User::whereKey($negocio->id)->lockForUpdate()->first();

            $candidatas = $agenda->profesionales($data['fecha'], $profesionalId);
            if ($candidatas->isEmpty()) {
                return back()->withErrors(['profesional_id' => 'La profesional elegida no atiende ese día.'])->withInput();
            }

            [$profesional, $motivo] = $agenda->evaluar($data['fecha'], $inicioMin, $combo['duracion'], $combo['margen'], $candidatas, $agenda->citasDelDia($data['fecha']));
            if (! $profesional) {
                return back()->withErrors(['hora_inicio' => $this->mensajeAgenda($motivo, $negocio)])->withInput();
            }

            // La foto solo se guarda si alguno de los servicios la admite
            $disenoPath = null;
            if ($combo['servicios']->contains('permite_diseno', true) && $request->hasFile('diseno')) {
                $archivo = $request->file('diseno');
                $nombreArchivo = Str::uuid() . '.' . $archivo->extension();
                $archivo->move(public_path('uploads/disenos'), $nombreArchivo);
                $disenoPath = 'uploads/disenos/' . $nombreArchivo;
            }

            $primero = $combo['servicios']->first();
            $confirmada = $combo['servicios']->every(fn (Servicio $s) => $negocio->confirmaSola($s));

            $reserva = Reserva::create([
                'user_id' => $negocio->id,
                'cancha_id' => $profesional->id,
                'servicio_id' => $primero->id,
                'nombre_cliente' => $data['nombre_cliente'],
                'telefono' => $data['telefono'],
                'email_cliente' => $data['email_cliente'] ?? null,
                'diseno_path' => $disenoPath,
                'diseno_forma' => $disenoPath ? ($data['diseno_forma'] ?? null) : null,
                'fecha' => $data['fecha'],
                'hora_inicio' => AgendaSalon::hora($inicioMin) . ':00',
                'hora_fin' => AgendaSalon::hora($finMin) . ':00',
                'duracion_horas' => max(1, (int) ceil($combo['duracion'] / 60)),
                'margen_minutos' => $combo['margen'],
                'precio' => $combo['precio'],
                'estado' => $confirmada ? 'confirmada' : 'pendiente',
            ]);
            $reserva->update(['radicado' => Reserva::radicadoPara($negocio, $reserva->id)]);

            foreach ($combo['servicios'] as $i => $servicio) {
                $reserva->servicios()->create([
                    'servicio_id' => $servicio->id,
                    'nombre' => $servicio->nombre,
                    'duracion_minutos' => $servicio->duracion_minutos,
                    'precio' => $servicio->precio,
                    'orden' => $i,
                ]);
            }

            if ($reserva->estado === 'confirmada') {
                $reserva->notificarConfirmacion();
            }

            $nombres = $combo['servicios']->pluck('nombre')->implode(' + ');

            return redirect()
                ->route('reservas.index', $slug)
                ->with('reserva_ok', $this->datosExito($reserva, ['etiqueta' => 'Servicio', 'recurso' => $nombres, 'profesional' => $profesional->nombre]))
                ->with('exito', ($reserva->estado === 'confirmada' ? '¡Cita confirmada! ' : '¡Cita recibida, pendiente de confirmación! ') . "{$reserva->nombre_cliente}, tu cita de \"{$nombres}\" con {$profesional->nombre} es el "
                    . $reserva->fecha->format('d/m/Y') . ' de ' . substr($reserva->hora_inicio, 0, 5) . ' a ' . substr($reserva->hora_fin, 0, 5) . '.');
        });
    }

    /** Mensaje para el cliente según el motivo por el que una hora no se puede reservar. */
    private function mensajeAgenda(?string $motivo, User $negocio): string
    {
        return match ($motivo) {
            AgendaSalon::HORA_PASADA => $negocio->anticipacion_minutos > 0
                ? 'Esa hora ya no está disponible: se reserva con al menos ' . mb_strtolower(User::ANTICIPACIONES[$negocio->anticipacion_minutos] ?? ($negocio->anticipacion_minutos . ' minutos')) . ' de anticipación.'
                : 'Esa hora ya pasó. Por favor elige una hora más adelante.',
            AgendaSalon::FUERA_HORARIO => "El horario de atención es de {$negocio->hora_apertura} a {$negocio->hora_cierre}, y la cita completa debe caber en él.",
            AgendaSalon::SIN_PUESTO => 'Todas las sillas están ocupadas a esa hora. Por favor elige otra.',
            AgendaSalon::NO_ATIENDE => 'La profesional elegida no atiende a esa hora. Por favor elige otra.',
            default => 'Ese horario ya se ocupó. Por favor elige otra hora.',
        };
    }

    /**
     * Panel privado (requiere login): solo muestra las reservas del
     * negocio que inició sesión.
     */
    public function admin(Request $request)
    {
        $negocio = Auth::user();

        if ($negocio->es_superadmin) {
            return redirect()->route('superadmin.index');
        }

        $fecha = $request->query('fecha', User::ahora()->format('Y-m-d'));

        $reservas = Reserva::with(['cancha', 'servicio', 'servicios'])
            ->where('user_id', $negocio->id)
            ->enFecha($fecha)
            ->where('estado', '!=', 'cancelada')
            ->orderBy('cancha_id')
            ->orderBy('hora_inicio')
            ->get();

        return view('reservas.admin', [
            'reservas' => $reservas,
            'fecha' => $fecha,
            'negocio' => $negocio,
        ]);
    }

    public function confirmar(Reserva $reserva)
    {
        abort_unless($reserva->user_id === Auth::id(), 403);

        $reserva->update(['estado' => 'confirmada']);
        $reserva->notificarConfirmacion();

        return redirect()
            ->route('reservas.admin', ['fecha' => $reserva->fecha->format('Y-m-d')])
            ->with('exito', "Reserva de {$reserva->nombre_cliente} confirmada.");
    }

    public function cancelar(Reserva $reserva)
    {
        abort_unless($reserva->user_id === Auth::id(), 403);

        $reserva->update(['estado' => 'cancelada']);

        return redirect()
            ->route('reservas.admin', ['fecha' => $reserva->fecha->format('Y-m-d')])
            ->with('exito', "Reserva de {$reserva->nombre_cliente} cancelada. El horario quedó libre de nuevo.");
    }

    public function semana(Request $request)
    {
        $negocio = Auth::user();

        if ($negocio->es_superadmin) {
            return redirect()->route('superadmin.index');
        }

        $inicioSemana = $request->query('semana')
            ? Carbon::parse($request->query('semana'))->startOfWeek(Carbon::MONDAY)
            : User::ahora()->startOfWeek(Carbon::MONDAY);

        $canchas = Cancha::where('user_id', $negocio->id)->where('activa', true)->get();
        $horas = $this->generarHoras($negocio);
        $nombresDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $fecha = $inicioSemana->copy()->addDays($i);

            $reservasDia = Reserva::with(['cancha', 'servicio', 'servicios'])
                ->where('user_id', $negocio->id)
                ->enFecha($fecha->format('Y-m-d'))
                ->where('estado', '!=', 'cancelada')
                ->get();

            $matriz = [];
            foreach ($canchas as $cancha) {
                foreach ($horas as $hora) {
                    $matriz[$cancha->id][$hora] = $reservasDia->first(
                        fn ($r) => $r->cancha_id === $cancha->id
                            // La fila de cada hora muestra la cita si se cruza con esa hora (también citas de 9:45, 10:20...)
                            && AgendaSalon::minutos($hora) + 60 > AgendaSalon::minutos($r->hora_inicio)
                            && AgendaSalon::minutos($hora) < AgendaSalon::minutos($r->hora_fin)
                    );
                }
            }

            $dias[] = [
                'fecha' => $fecha,
                'nombre' => $nombresDias[$i],
                'matriz' => $matriz,
            ];
        }

        return view('reservas.semana', [
            'dias' => $dias,
            'canchas' => $canchas,
            'horas' => $horas,
            'inicioSemana' => $inicioSemana,
            'negocio' => $negocio,
        ]);
    }

    private function generarHoras(User $negocio): array
    {
        [$h] = explode(':', $negocio->hora_apertura);
        [$hCierre] = explode(':', $negocio->hora_cierre);
        $h = (int) $h;
        $hCierre = (int) $hCierre;

        $horas = [];
        while ($h < $hCierre) {
            $horas[] = sprintf('%02d:00', $h);
            $h++;
        }

        return $horas;
    }
}