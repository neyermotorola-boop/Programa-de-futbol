<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Agenda de un salón: calcula qué horas se pueden reservar y qué profesional queda libre.
 *
 * Es la única fuente de verdad: la lista de horas que ve el cliente y la validación al guardar la cita
 * usan estas mismas reglas, así que nunca pueden contradecirse. Reglas que aplica:
 *  - la cita completa (suma de sus servicios) cabe dentro del horario del negocio y de la profesional;
 *  - la profesional trabaja ese día, no está en su descanso y no tiene otra cita encima;
 *  - cada cita ocupa su duración MÁS el margen (limpieza/preparación) que se definió para ella;
 *  - nunca hay más citas a la vez que sillas, consultorios o cabinas;
 *  - no se puede reservar en horas que ya pasaron ni sin la anticipación mínima del negocio.
 */
class AgendaSalon
{
    public const HORA_PASADA = 'pasada';
    public const FUERA_HORARIO = 'fuera_horario';
    public const SIN_PUESTO = 'sin_puesto';
    public const OCUPADA = 'ocupada';
    public const NO_ATIENDE = 'no_atiende';

    public function __construct(private User $negocio)
    {
    }

    // ---------------------------------------------------------------- utilidades

    /** 'HH:MM' o 'HH:MM:SS' → minutos desde la medianoche. */
    public static function minutos(string $hora): int
    {
        return (int) substr($hora, 0, 2) * 60 + (int) substr($hora, 3, 2);
    }

    /** Minutos desde la medianoche → 'HH:MM'. */
    public static function hora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    /**
     * Junta los servicios elegidos para una cita: duración total, margen (el mayor de los servicios)
     * y precio total (null si ninguno tiene precio). Devuelve null si alguno no existe o no está activo.
     *
     * @param  array<int|string>  $ids
     * @return array{servicios: Collection<int, Servicio>, duracion: int, margen: int, precio: float|null}|null
     */
    public static function combinar(User $negocio, array $ids): ?array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (! $ids) {
            return null;
        }

        $encontrados = Servicio::where('user_id', $negocio->id)->where('activo', true)->whereIn('id', $ids)->get()->keyBy('id');
        if ($encontrados->count() !== count($ids)) {
            return null;
        }

        // Se conserva el orden en que el cliente los eligió
        $servicios = collect($ids)->map(fn ($id) => $encontrados[$id]);
        $conPrecio = $servicios->filter(fn ($s) => $s->precio !== null);

        return [
            'servicios' => $servicios,
            'duracion' => (int) $servicios->sum('duracion_minutos'),
            'margen' => (int) $servicios->max(fn ($s) => $s->margenPara($negocio)),
            'precio' => $conPrecio->isEmpty() ? null : (float) $conPrecio->sum('precio'),
        ];
    }

    /** Profesionales activas del negocio que atienden ese día de la semana (todas, o solo la elegida). */
    public function profesionales(string $fecha, ?string $profesionalId): Collection
    {
        $dia = Carbon::parse($fecha)->dayOfWeekIso;

        $consulta = Cancha::where('user_id', $this->negocio->id)->where('activa', true);
        if ($profesionalId !== null && $profesionalId !== 'cualquiera') {
            $consulta->where('id', (int) $profesionalId);
        }

        return $consulta->get()->filter(fn (Cancha $p) => $p->disponibleEl($dia))->values();
    }

    /** Citas vigentes (no canceladas) del día, con lo que realmente ocupan cada una. */
    public function citasDelDia(string $fecha): Collection
    {
        return Reserva::where('user_id', $this->negocio->id)
            ->enFecha($fecha)
            ->where('estado', '!=', 'cancelada')
            ->get(['id', 'cancha_id', 'hora_inicio', 'hora_fin', 'margen_minutos'])
            ->map(fn (Reserva $r) => [
                'profesional' => (int) $r->cancha_id,
                'inicio' => self::minutos($r->hora_inicio),
                // Lo que ocupa de verdad: la cita más su margen
                'fin' => self::minutos($r->hora_fin) + (int) $r->margen_minutos,
            ]);
    }

    // ---------------------------------------------------------------- reglas

    /**
     * ¿Se puede dar la cita? Devuelve la profesional que la atendería o, si no se puede, el motivo.
     *
     * @param  Collection<int, Cancha>  $candidatas
     * @param  Collection<int, array{profesional:int, inicio:int, fin:int}>  $citas
     * @return array{0: Cancha|null, 1: string|null}
     */
    public function evaluar(string $fecha, int $inicio, int $duracion, int $margen, Collection $candidatas, Collection $citas): array
    {
        $fin = $inicio + $duracion;
        $ocupaHasta = $fin + $margen;

        // Reglas del negocio: horario, horas pasadas y anticipación
        $minimo = $this->negocio->horaMinimaReservable($fecha);
        if ($minimo !== null && $inicio < self::minutos($minimo)) {
            return [null, self::HORA_PASADA];
        }
        if ($inicio < self::minutos($this->negocio->hora_apertura) || $fin > self::minutos($this->negocio->hora_cierre)) {
            return [null, self::FUERA_HORARIO];
        }

        // Capacidad: sillas, consultorios o cabinas ocupados a la vez
        $enUso = $citas->filter(fn ($c) => $inicio < $c['fin'] && $ocupaHasta > $c['inicio'])->count();
        if ($enUso >= max(1, (int) $this->negocio->numero_puestos)) {
            return [null, self::SIN_PUESTO];
        }

        // Profesionales libres: la de menos citas ese día primero, para repartir el trabajo
        $libres = $candidatas
            ->filter(fn (Cancha $p) => $this->trabaja($p, $inicio, $fin) && ! $this->tieneCruce($p, $citas, $inicio, $ocupaHasta))
            ->sortBy([
                fn (Cancha $a, Cancha $b) => $citas->where('profesional', $a->id)->count() <=> $citas->where('profesional', $b->id)->count(),
                fn (Cancha $a, Cancha $b) => $a->id <=> $b->id,
            ]);

        if ($libres->isNotEmpty()) {
            return [$libres->first(), null];
        }

        return [null, $candidatas->contains(fn (Cancha $p) => $this->trabaja($p, $inicio, $fin)) ? self::OCUPADA : self::NO_ATIENDE];
    }

    /** ¿La profesional está en su horario y fuera de su descanso durante toda la cita? */
    private function trabaja(Cancha $p, int $inicio, int $fin): bool
    {
        [$desde, $hasta] = $p->ventanaTrabajo($this->negocio);
        if ($inicio < self::minutos($desde) || $fin > self::minutos($hasta)) {
            return false;
        }

        if ($descanso = $p->descanso()) {
            if ($inicio < self::minutos($descanso[1]) && $fin > self::minutos($descanso[0])) {
                return false;
            }
        }

        return true;
    }

    private function tieneCruce(Cancha $p, Collection $citas, int $inicio, int $ocupaHasta): bool
    {
        return $citas->contains(fn ($c) => $c['profesional'] === $p->id && $inicio < $c['fin'] && $ocupaHasta > $c['inicio']);
    }

    // ---------------------------------------------------------------- horas ofrecidas

    /**
     * Horas de inicio que se muestran al cliente para ese día y esa duración.
     *
     * Salen cada `intervalo_agenda` minutos desde la apertura. Además se ofrecen las horas "pegadas"
     * al final de otra cita (o al inicio del turno / fin del descanso de una profesional) cuando esa
     * hora cae fuera de la cuadrícula, para no dejar huecos que nadie puede usar.
     *
     * @param  Collection<int, Cancha>  $candidatas
     * @return list<array{hora: string, disponible: bool}>
     */
    public function horas(string $fecha, int $duracion, int $margen, Collection $candidatas): array
    {
        $apertura = self::minutos($this->negocio->hora_apertura);
        $cierre = self::minutos($this->negocio->hora_cierre);
        $paso = max(5, (int) ($this->negocio->intervalo_agenda ?: 30));
        $citas = $this->citasDelDia($fecha);

        $cuadricula = [];
        for ($m = $apertura; $m + $duracion <= $cierre; $m += $paso) {
            $cuadricula[$m] = true;
        }

        // Horas "pegadas": solo se ofrecen si realmente se pueden reservar
        $extras = [];
        foreach ($candidatas as $p) {
            [$desde] = $p->ventanaTrabajo($this->negocio);
            $extras[] = self::minutos($desde);
            if ($descanso = $p->descanso()) {
                $extras[] = self::minutos($descanso[1]);
            }
        }
        foreach ($citas as $c) {
            $extras[] = $c['fin'];
        }

        $resultado = [];
        foreach ($cuadricula as $m => $_) {
            [$libre] = $this->evaluar($fecha, $m, $duracion, $margen, $candidatas, $citas);
            $resultado[$m] = $libre !== null;
        }
        foreach ($extras as $m) {
            $m = (int) (ceil($m / 5) * 5);   // a múltiplos de 5 minutos, para horas legibles
            if (isset($resultado[$m]) || $m < $apertura || $m + $duracion > $cierre) {
                continue;
            }
            [$libre] = $this->evaluar($fecha, $m, $duracion, $margen, $candidatas, $citas);
            if ($libre !== null) {
                $resultado[$m] = true;
            }
        }

        ksort($resultado);

        return collect($resultado)->map(fn ($disponible, $m) => ['hora' => self::hora($m), 'disponible' => $disponible])->values()->all();
    }
}
