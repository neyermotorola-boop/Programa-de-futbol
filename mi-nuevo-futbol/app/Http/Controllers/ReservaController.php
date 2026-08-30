<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
    /**
     * Página pública donde el cliente elige cancha, fecha y hora,
     * para UN negocio en particular (identificado por su slug en la URL).
     */
    public function index(string $slug)
    {
        $negocio = User::where('slug', $slug)->firstOrFail();
        $canchas = Cancha::where('user_id', $negocio->id)->where('activa', true)->get();

        return view('reservas.index', [
            'negocio' => $negocio,
            'canchas' => $canchas,
            'horaApertura' => $negocio->hora_apertura,
            'horaCierre' => $negocio->hora_cierre,
            'maxHoras' => $negocio->max_horas_por_reserva,
        ]);
    }

    /**
     * Devuelve en JSON los rangos ya ocupados para una cancha y fecha dadas,
     * validando que esa cancha sea realmente de este negocio.
     */
    public function disponibilidad(string $slug, Request $request)
    {
        $negocio = User::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'cancha_id' => ['required', 'exists:canchas,id'],
        ]);

        $cancha = Cancha::where('id', $data['cancha_id'])
            ->where('user_id', $negocio->id)
            ->firstOrFail();

        $ocupadas = Reserva::enFecha($data['fecha'])
            ->where('cancha_id', $cancha->id)
            ->where('estado', '!=', 'cancelada')
            ->orderBy('hora_inicio')
            ->get(['hora_inicio', 'hora_fin'])
            ->map(fn ($r) => [
                'inicio' => substr($r->hora_inicio, 0, 5),
                'fin' => substr($r->hora_fin, 0, 5),
            ]);

        return response()->json(['ocupadas' => $ocupadas]);
    }

    /**
     * Crea una nueva reserva para un negocio en particular.
     */
    public function store(string $slug, Request $request)
    {
        $negocio = User::where('slug', $slug)->firstOrFail();
        $horaApertura = $negocio->hora_apertura;
        $horaCierre = $negocio->hora_cierre;
        $maxHoras = $negocio->max_horas_por_reserva;

        $data = $request->validate([
            'nombre_cliente' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[0-9]{7,15}$/'],
            'cancha_id' => ['required', 'exists:canchas,id'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'duracion_horas' => ['required', 'integer', 'min:1', "max:{$maxHoras}"],
        ]);

        // Seguridad: la cancha elegida debe pertenecer a este negocio.
        $cancha = Cancha::where('id', $data['cancha_id'])
            ->where('user_id', $negocio->id)
            ->first();

        if (! $cancha) {
            return back()->withErrors(['cancha_id' => 'Esa cancha no existe.'])->withInput();
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
                'fecha' => $data['fecha'],
                'hora_inicio' => $inicio->format('H:i:s'),
                'hora_fin' => $fin->format('H:i:s'),
                'duracion_horas' => $data['duracion_horas'],
                'estado' => 'pendiente',
            ]);

            return redirect()
                ->route('reservas.index', $slug)
                ->with('exito', "¡Reserva creada! {$reserva->nombre_cliente}, tu cancha está separada para el "
                    . $reserva->fecha->format('d/m/Y') . " de " . $inicio->format('H:i') . " a " . $fin->format('H:i') . ".");
        });
    }

    /**
     * Panel privado (requiere login): solo muestra las reservas del
     * negocio que inició sesión.
     */
    public function admin(Request $request)
    {
        $negocio = Auth::user();
        $fecha = $request->query('fecha', now()->format('Y-m-d'));

        $reservas = Reserva::with('cancha')
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

    /**
     * Marca una reserva como confirmada (solo si es del negocio que la pide).
     */
    public function confirmar(Reserva $reserva)
    {
        abort_unless($reserva->user_id === Auth::id(), 403);

        $reserva->update(['estado' => 'confirmada']);

        return redirect()
            ->route('reservas.admin', ['fecha' => $reserva->fecha->format('Y-m-d')])
            ->with('exito', "Reserva de {$reserva->nombre_cliente} confirmada.");
    }

    /**
     * Cancela una reserva (solo si es del negocio que la pide).
     */
    public function cancelar(Reserva $reserva)
    {
        abort_unless($reserva->user_id === Auth::id(), 403);

        $reserva->update(['estado' => 'cancelada']);

        return redirect()
            ->route('reservas.admin', ['fecha' => $reserva->fecha->format('Y-m-d')])
            ->with('exito', "Reserva de {$reserva->nombre_cliente} cancelada. El horario quedó libre de nuevo.");
    }

    /**
     * Agenda semanal del negocio que inició sesión.
     */
    public function semana(Request $request)
    {
        $negocio = Auth::user();

        $inicioSemana = $request->query('semana')
            ? Carbon::parse($request->query('semana'))->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);

        $canchas = Cancha::where('user_id', $negocio->id)->where('activa', true)->get();
        $horas = $this->generarHoras($negocio);
        $nombresDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $fecha = $inicioSemana->copy()->addDays($i);

            $reservasDia = Reserva::with('cancha')
                ->where('user_id', $negocio->id)
                ->enFecha($fecha->format('Y-m-d'))
                ->where('estado', '!=', 'cancelada')
                ->get();

            $matriz = [];
            foreach ($canchas as $cancha) {
                foreach ($horas as $hora) {
                    $matriz[$cancha->id][$hora] = $reservasDia->first(
                        fn ($r) => $r->cancha_id === $cancha->id
                            && $hora >= substr($r->hora_inicio, 0, 5)
                            && $hora < substr($r->hora_fin, 0, 5)
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

    /**
     * Genera la lista de horas del día (06:00, 07:00, ...) según el
     * horario propio de este negocio.
     */
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