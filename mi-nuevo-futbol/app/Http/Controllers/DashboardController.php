<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Dashboard de métricas de un negocio: citas confirmadas / pendientes / rechazadas,
 * ingresos por servicio (o cancha) y desempeño por profesional (o cancha).
 * Cada negocio solo ve (y exporta) sus propios números.
 */
class DashboardController extends Controller
{
    public const RANGOS = [
        'hoy' => 'Hoy',
        '7' => '7 días',
        '30' => '30 días',
        'mes' => 'Este mes',
        'mes_anterior' => 'Mes anterior',
    ];

    public function index(Request $request)
    {
        return view('dashboard.index', $this->datos($request));
    }

    /**
     * Descarga las mismas métricas de la pantalla en un libro de Excel (.xlsx),
     * con una hoja por cada sección y otra con el detalle de las citas.
     */
    public function excel(Request $request)
    {
        $d = $this->datos($request);
        $esSalon = $d['esSalon'];
        $negocio = $d['negocio'];

        $ruta = tempnam(sys_get_temp_dir(), 'metricas');
        $writer = new Writer();
        $writer->openToFile($ruta);

        $titulo = (new Style())->withFontBold(true)->withFontSize(16);
        $cabecera = (new Style())->withFontBold(true)->withFontColor('FFFFFF')->withBackgroundColor($this->colorExcel($negocio->color_primario, '2F6B3C'))->withCellAlignment(CellAlignment::LEFT);
        $negrita = (new Style())->withFontBold(true);
        $dinero = (new Style())->withFormat('$#,##0');
        $dineroNegrita = (new Style())->withFormat('$#,##0')->withFontBold(true);
        $porcentaje = (new Style())->withFormat('0.0"%"');
        $porcentajeEntero = (new Style())->withFormat('0"%"');

        $fila = fn (array $valores, ?Style $estilo = null) => $estilo ? Row::fromValuesWithStyle($valores, $estilo) : Row::fromValues($valores);
        $periodo = $d['desde']->isSameDay($d['hasta'])
            ? $d['desde']->format('d/m/Y')
            : $d['desde']->format('d/m/Y') . ' al ' . $d['hasta']->format('d/m/Y');

        // ---- Hoja 1: Resumen ----
        $hoja = $writer->getCurrentSheet();
        $hoja->setName('Resumen');
        $hoja->setColumnWidth(34, 1);
        $hoja->setColumnWidth(24, 2);
        $writer->addRow($fila([$negocio->name], $titulo));
        $writer->addRow($fila(['Métricas del período', $periodo]));
        $writer->addRow($fila(['Generado el', \App\Models\User::ahora()->format('d/m/Y H:i')]));
        $writer->addRow($fila([]));
        $writer->addRow($fila(['Indicador', 'Valor'], $cabecera));
        $writer->addRow($fila(['Citas totales', $d['total']]));
        $writer->addRow($fila(['Citas confirmadas', $d['conf']]));
        $writer->addRow($fila(['Citas pendientes', $d['pend']]));
        $writer->addRow($fila(['Citas rechazadas (canceladas)', $d['rech']]));
        $writer->addRow(Row::fromValuesWithStyles(['Tasa de confirmación', $d['tasa']], [1 => $porcentajeEntero]));
        $writer->addRow(Row::fromValuesWithStyles(['Ingresos confirmados', $d['ingresos']], [1 => $dineroNegrita]));
        $writer->addRow(Row::fromValuesWithStyles(['Ingresos por confirmar', $d['ingresosPend']], [1 => $dinero]));
        $writer->addRow(Row::fromValuesWithStyles(['Valor promedio por cita confirmada', round($d['ticket'])], [1 => $dinero]));
        $writer->addRow($fila(['Variación de ingresos vs. período anterior', is_null($d['variacion']) ? 'Sin período anterior' : $d['variacion'] . '%']));
        if ($d['sinPrecio']) {
            $writer->addRow($fila(['Citas confirmadas sin precio configurado', $d['sinPrecio']]));
        }

        // ---- Hoja 2: por servicio (o cancha) ----
        $this->hoja($writer, $esSalon ? 'Por servicio' : 'Por cancha', [38, 10, 13, 12, 12, 16, 16, 12]);
        $writer->addRow($fila([$esSalon ? 'Servicio' : 'Cancha', 'Citas', 'Confirmadas', 'Pendientes', 'Rechazadas', 'Precio promedio', 'Ingresos', '% de ingresos'], $cabecera));
        $totalIngresos = max(1, (float) $d['servicios']->sum('ingresos'));
        foreach ($d['servicios'] as $s) {
            $writer->addRow(Row::fromValuesWithStyles(
                [$s['nombre'], $s['total'], $s['conf'], $s['pend'], $s['rech'], round($s['promedio']), $s['ingresos'], round($s['ingresos'] / $totalIngresos * 100, 1)],
                [5 => $dinero, 6 => $dinero, 7 => $porcentaje],
            ));
        }
        $writer->addRow(Row::fromValuesWithStyles(
            ['Total', $d['total'], $d['conf'], $d['pend'], $d['rech'], round($d['ticket']), $d['ingresos'], 100],
            [0 => $negrita, 5 => $dineroNegrita, 6 => $dineroNegrita, 7 => $porcentaje],
        ));

        // ---- Hoja 3: por profesional (o cancha) ----
        $this->hoja($writer, $esSalon ? 'Por profesional' : 'Estado por cancha', [30, 13, 12, 12, 10, 16]);
        $writer->addRow($fila([$esSalon ? 'Profesional' : 'Cancha', 'Confirmadas', 'Pendientes', 'Rechazadas', 'Total', 'Ingresos'], $cabecera));
        foreach ($d['profesionales'] as $p) {
            $writer->addRow(Row::fromValuesWithStyles([$p['nombre'], $p['conf'], $p['pend'], $p['rech'], $p['total'], $p['ingresos']], [5 => $dinero]));
        }

        // ---- Hoja 4: por día (o semana) ----
        $this->hoja($writer, $d['dias'] > 62 ? 'Por semana' : 'Por día', [26, 13, 12, 12, 10]);
        $writer->addRow($fila([$d['dias'] > 62 ? 'Semana' : 'Día', 'Confirmadas', 'Pendientes', 'Rechazadas', 'Total'], $cabecera));
        foreach ($d['serie'] as $dia) {
            $writer->addRow($fila([$dia['titulo'], $dia['conf'], $dia['pend'], $dia['rech'], $dia['conf'] + $dia['pend'] + $dia['rech']]));
        }

        // ---- Hoja 5: detalle de cada cita ----
        $detalle = $this->hoja($writer, 'Detalle de citas', $esSalon ? [18, 12, 20, 28, 16, 28, 34, 22, 13, 14] : [18, 12, 20, 28, 16, 28, 26, 13, 14]);
        $columnas = array_merge(['Radicado', 'Fecha', 'Horario', 'Cliente', 'Teléfono', 'Correo', $esSalon ? 'Servicio' : 'Cancha'], $esSalon ? ['Profesional'] : [], ['Estado', 'Precio']);
        $colPrecio = count($columnas) - 1;
        $writer->addRow($fila($columnas, $cabecera));
        $estados = ['confirmada' => 'Confirmada', 'pendiente' => 'Pendiente', 'cancelada' => 'Rechazada'];
        $n = 0;
        foreach ($d['reservas']->sortBy(fn ($r) => $r->fecha->format('Y-m-d') . $r->hora_inicio) as $r) {
            $writer->addRow(Row::fromValuesWithStyles(array_merge([
                $r->radicado ?? '—',
                $r->fecha->format('d/m/Y'),
                date('g:i A', strtotime($r->hora_inicio)) . ' – ' . date('g:i A', strtotime($r->hora_fin)),
                $r->nombre_cliente,
                $r->telefono,
                $r->email_cliente ?? '',
                $esSalon ? $r->nombreServicios('Sin servicio') : $r->cancha->nombre,
            ], $esSalon ? [$r->cancha->nombre] : [], [
                $estados[$r->estado] ?? $r->estado,
                $r->precio !== null ? (float) $r->precio : '',
            ]), [$colPrecio => $dinero]));
            $n++;
        }
        // Filtros en los encabezados y fila de títulos fija
        $detalle->setAutoFilter(new AutoFilter(0, 1, $colPrecio, max(2, $n + 1)));
        $detalle->setSheetView((new SheetView())->withFreezeRow(2));

        $writer->close();

        $archivo = 'metricas-' . $negocio->slug . '-' . $d['desde']->format('Ymd') . '_' . $d['hasta']->format('Ymd') . '.xlsx';

        return response()->download($ruta, $archivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Agrega una hoja nueva con los anchos de columna dados y la deja como la actual. */
    protected function hoja(Writer $writer, string $nombre, array $anchos)
    {
        $hoja = $writer->addNewSheetAndMakeItCurrent();
        $hoja->setName($nombre);
        foreach ($anchos as $i => $ancho) {
            $hoja->setColumnWidth($ancho, $i + 1);
        }

        return $hoja;
    }

    /** '#2F6B3C' → '2F6B3C' (el formato que usa Excel), con un valor por defecto si no es válido. */
    protected function colorExcel(?string $color, string $porDefecto): string
    {
        return $color && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtoupper(ltrim($color, '#')) : $porDefecto;
    }

    /** Todas las métricas del período elegido; las usan la pantalla y el Excel. */
    private function datos(Request $request): array
    {
        $negocio = Auth::user();
        abort_if($negocio->es_superadmin, 403);

        $esSalon = $negocio->tipo_negocio === 'salon';
        [$desde, $hasta, $rango] = $this->periodo($request, config('canchas.zona'));
        $dias = $desde->diffInDays($hasta) + 1;

        $reservas = $this->citas($negocio->id, $desde, $hasta);

        $conf = $reservas->where('estado', 'confirmada');
        $pend = $reservas->where('estado', 'pendiente');
        $rech = $reservas->where('estado', 'cancelada');
        $total = $reservas->count();

        $ingresos = (float) $conf->sum('precio');

        // Período anterior de la misma duración, para saber si el negocio va mejor o peor
        $hastaPrevio = $desde->copy()->subDay();
        $desdePrevio = $hastaPrevio->copy()->subDays($dias - 1);
        $ingresosPrevio = (float) $this->citas($negocio->id, $desdePrevio, $hastaPrevio)->where('estado', 'confirmada')->sum('precio');

        // Por servicio (salón) o por cancha (canchas): cuánto dejó cada uno
        // En un salón una cita puede combinar varios servicios: cada uno cuenta con su propio precio
        $nombreServicio = fn (Reserva $r) => $esSalon ? ($r->getAttribute('nombre_item') ?? 'Sin servicio') : $r->cancha->nombre;
        $servicios = $this->agrupar($esSalon ? $this->porServicio($reservas) : $reservas, $nombreServicio)->sortByDesc(fn ($g) => [$g['ingresos'], $g['conf']])->values();

        // Por profesional (salón) o por cancha: cuántas citas atendió y en qué estado quedaron
        $profesionales = $this->agrupar($reservas, fn (Reserva $r) => $r->cancha->nombre)
            ->sortByDesc(fn ($g) => [$g['total'], $g['conf']])->values();

        return [
            'negocio' => $negocio,
            'esSalon' => $esSalon,
            'rango' => $rango,
            'rangos' => self::RANGOS,
            'desde' => $desde,
            'hasta' => $hasta,
            'dias' => $dias,
            'reservas' => $reservas,
            'total' => $total,
            'conf' => $conf->count(),
            'pend' => $pend->count(),
            'rech' => $rech->count(),
            'tasa' => $total ? (int) round($conf->count() / $total * 100) : 0,
            'ingresos' => $ingresos,
            'ingresosPend' => (float) $pend->sum('precio'),
            'ingresosPrevio' => $ingresosPrevio,
            'variacion' => $ingresosPrevio > 0 ? round(($ingresos - $ingresosPrevio) / $ingresosPrevio * 100) : null,
            'ticket' => $conf->count() ? $ingresos / $conf->count() : 0,
            'sinPrecio' => $conf->whereNull('precio')->count(),
            'servicios' => $servicios,
            'profesionales' => $profesionales,
            'serie' => $this->serieDiaria($reservas, $desde, $hasta, $dias),
        ];
    }

    /**
     * Una fila por cada servicio de cada cita (la cita lleva su estado), con el precio de ese servicio.
     * Una cita "Manicure + Pedicure" aparece dos veces, cada una con lo que valió ese servicio.
     */
    private function porServicio($reservas)
    {
        return $reservas->flatMap(function (Reserva $r) {
            if ($r->servicios->isEmpty()) {
                $fila = $r->replicate();
                $fila->setAttribute('nombre_item', $r->servicio?->nombre ?? 'Sin servicio');

                return [$fila];
            }

            return $r->servicios->map(function ($item) use ($r) {
                $fila = $r->replicate();
                $fila->precio = $item->precio;
                $fila->setAttribute('nombre_item', $item->nombre);

                return $fila;
            });
        });
    }

    private function citas(int $negocioId, Carbon $desde, Carbon $hasta)
    {
        return Reserva::with(['cancha', 'servicio', 'servicios'])
            ->where('user_id', $negocioId)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get();
    }

    /** Período elegido: un atajo (hoy, 7 días...) o fechas propias; por defecto, el mes en curso. */
    protected function periodo(Request $request, ?string $zona = null): array
    {
        $hoy = now($zona)->startOfDay();

        if ($request->filled('desde') && $request->filled('hasta')) {
            try {
                $desde = Carbon::parse($request->query('desde'), $zona)->startOfDay();
                $hasta = Carbon::parse($request->query('hasta'), $zona)->startOfDay();
                if ($hasta->lt($desde)) {
                    [$desde, $hasta] = [$hasta, $desde];
                }
                if ($desde->diffInDays($hasta) > 365) {
                    $desde = $hasta->copy()->subDays(365);
                }

                return [$desde, $hasta, 'personalizado'];
            } catch (\Throwable $e) {
                // fechas inválidas: se usa el período por defecto
            }
        }

        $rango = array_key_exists($request->query('rango'), self::RANGOS) ? $request->query('rango') : 'mes';

        return match ($rango) {
            'hoy' => [$hoy, $hoy->copy(), 'hoy'],
            '7' => [$hoy->copy()->subDays(6), $hoy->copy(), '7'],
            '30' => [$hoy->copy()->subDays(29), $hoy->copy(), '30'],
            'mes_anterior' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()->startOfDay(), 'mes_anterior'],
            default => [$hoy->copy()->startOfMonth(), $hoy->copy(), 'mes'],
        };
    }

    /** Cuenta citas por estado e ingresos confirmados para cada grupo. */
    private function agrupar($reservas, callable $clave)
    {
        return $reservas->groupBy($clave)->map(function ($grupo, $nombre) {
            $conf = $grupo->where('estado', 'confirmada');

            return [
                'nombre' => $nombre,
                'total' => $grupo->count(),
                'conf' => $conf->count(),
                'pend' => $grupo->where('estado', 'pendiente')->count(),
                'rech' => $grupo->where('estado', 'cancelada')->count(),
                'ingresos' => (float) $conf->sum('precio'),
                'promedio' => $conf->count() ? (float) $conf->sum('precio') / $conf->count() : 0,
            ];
        })->values();
    }

    /** Citas por día (o por semana si el período es largo), con su estado. */
    private function serieDiaria($reservas, Carbon $desde, Carbon $hasta, int $dias): array
    {
        $porSemana = $dias > 62;
        $clave = fn (Carbon $f) => $porSemana ? $f->copy()->startOfWeek()->toDateString() : $f->toDateString();

        $serie = [];
        for ($c = $desde->copy(); $c->lte($hasta); $c->addDay()) {
            $k = $clave($c);
            $serie[$k] ??= [
                'etiqueta' => $porSemana ? $c->copy()->startOfWeek()->locale('es')->translatedFormat('j M') : $c->format('j'),
                'titulo' => $porSemana
                    ? 'Semana del ' . $c->copy()->startOfWeek()->locale('es')->translatedFormat('j M')
                    : ucfirst($c->locale('es')->translatedFormat('D j M')),
                'conf' => 0, 'pend' => 0, 'rech' => 0,
            ];
        }

        foreach ($reservas as $r) {
            $k = $clave($r->fecha);
            if (! isset($serie[$k])) {
                continue;
            }
            $serie[$k][['confirmada' => 'conf', 'pendiente' => 'pend', 'cancelada' => 'rech'][$r->estado] ?? 'pend']++;
        }

        return array_values($serie);
    }
}
