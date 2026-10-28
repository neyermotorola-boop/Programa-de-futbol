<?php

namespace App\Http\Controllers;

use App\Models\MensualidadPago;
use App\Models\ParqueaderoIngreso;
use App\Models\TipoVehiculo;
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
 * Dashboard de métricas de un parqueadero: ingresos cobrados, vehículos atendidos por tipo
 * (carro, moto, cicla...), movimiento por día y horas de mayor entrada.
 * Reutiliza los filtros de período del dashboard de reservas.
 */
class ParqueaderoDashboardController extends DashboardController
{
    public function index(Request $request)
    {
        return view('parqueadero.dashboard', $this->datosParqueadero($request));
    }

    /** Descarga las métricas de la pantalla en un libro de Excel con resumen, tipos, días y detalle. */
    public function excel(Request $request)
    {
        $d = $this->datosParqueadero($request);
        $negocio = $d['negocio'];

        $ruta = tempnam(sys_get_temp_dir(), 'parqueadero');
        $writer = new Writer();
        $writer->openToFile($ruta);

        $titulo = (new Style())->withFontBold(true)->withFontSize(16);
        $cabecera = (new Style())->withFontBold(true)->withFontColor('FFFFFF')->withBackgroundColor($this->colorExcel($negocio->color_primario, '1F4E8C'))->withCellAlignment(CellAlignment::LEFT);
        $negrita = (new Style())->withFontBold(true);
        $dinero = (new Style())->withFormat('$#,##0');
        $dineroNegrita = (new Style())->withFormat('$#,##0')->withFontBold(true);
        $porcentaje = (new Style())->withFormat('0.0"%"');

        $fila = fn (array $valores, ?Style $estilo = null) => $estilo ? Row::fromValuesWithStyle($valores, $estilo) : Row::fromValues($valores);
        $periodo = $d['desde']->isSameDay($d['hasta'])
            ? $d['desde']->format('d/m/Y')
            : $d['desde']->format('d/m/Y') . ' al ' . $d['hasta']->format('d/m/Y');

        // ---- Hoja 1: Resumen ----
        $hoja = $writer->getCurrentSheet();
        $hoja->setName('Resumen');
        $hoja->setColumnWidth(36, 1);
        $hoja->setColumnWidth(24, 2);
        $writer->addRow($fila([$negocio->name], $titulo));
        $writer->addRow($fila(['Métricas del período', $periodo]));
        $writer->addRow($fila(['Generado el', now(ParqueaderoIngreso::ZONA)->format('d/m/Y H:i')]));
        $writer->addRow($fila([]));
        $writer->addRow($fila(['Indicador', 'Valor'], $cabecera));
        $writer->addRow($fila(['Vehículos que ingresaron', $d['entradas']]));
        $writer->addRow($fila(['Vehículos atendidos (con salida)', $d['atendidos']]));
        $writer->addRow($fila(['Dentro del parqueadero ahora', $d['dentro']]));
        $writer->addRow(Row::fromValuesWithStyles(['Ingresos cobrados', $d['ingresos']], [1 => $dineroNegrita]));
        $writer->addRow(Row::fromValuesWithStyles(['Valor promedio por vehículo', round($d['ticket'])], [1 => $dinero]));
        $writer->addRow($fila(['Tiempo promedio de permanencia', ParqueaderoIngreso::textoDuracion((int) round($d['tiempoPromedio']))]));
        $writer->addRow(Row::fromValuesWithStyles(['Mensualidades cobradas (' . $d['mensualidadesPagos'] . ' pagos)', $d['mensualidadesCobradas']], [1 => $dinero]));
        $writer->addRow($fila(['Variación de ingresos vs. período anterior', is_null($d['variacion']) ? 'Sin período anterior' : $d['variacion'] . '%']));

        // ---- Hoja 2: por tipo de vehículo ----
        $this->hoja($writer, 'Por tipo de vehículo', [26, 12, 18, 18, 16, 14]);
        $writer->addRow($fila(['Tipo de vehículo', 'Atendidos', 'Tiempo promedio', 'Valor promedio', 'Ingresos', '% de ingresos'], $cabecera));
        $totalIngresos = max(1, $d['ingresos']);
        foreach ($d['tipos'] as $t) {
            $writer->addRow(Row::fromValuesWithStyles(
                [$t['nombre'], $t['total'], ParqueaderoIngreso::textoDuracion((int) round($t['tiempoPromedio'])), round($t['promedio']), $t['ingresos'], round($t['ingresos'] / $totalIngresos * 100, 1)],
                [3 => $dinero, 4 => $dinero, 5 => $porcentaje],
            ));
        }
        $writer->addRow(Row::fromValuesWithStyles(
            ['Total', $d['atendidos'], ParqueaderoIngreso::textoDuracion((int) round($d['tiempoPromedio'])), round($d['ticket']), $d['ingresos'], 100],
            [0 => $negrita, 3 => $dineroNegrita, 4 => $dineroNegrita, 5 => $porcentaje],
        ));

        // ---- Hoja 3: por día (o semana) ----
        $this->hoja($writer, $d['dias'] > 62 ? 'Por semana' : 'Por día', array_merge([26], array_fill(0, $d['tipos']->count() + 2, 14)));
        $writer->addRow($fila(array_merge([$d['dias'] > 62 ? 'Semana' : 'Día'], $d['tipos']->pluck('nombre')->all(), ['Total', 'Ingresos']), $cabecera));
        foreach ($d['serie'] as $dia) {
            $writer->addRow(Row::fromValuesWithStyles(
                array_merge([$dia['titulo']], $d['tipos']->map(fn ($t) => $dia['porTipo'][$t['nombre']] ?? 0)->all(), [$dia['total'], $dia['ingresos']]),
                [count($d['tipos']) + 2 => $dinero],
            ));
        }

        // ---- Hoja 4: detalle de cada salida ----
        $detalle = $this->hoja($writer, 'Detalle', [14, 16, 20, 20, 14, 18, 14, 24]);
        $columnas = ['Placa', 'Tipo', 'Entrada', 'Salida', 'Tiempo', 'Tarifa', 'Cobrado', 'Cobró'];
        $writer->addRow($fila($columnas, $cabecera));
        $n = 0;
        foreach ($d['salidas']->sortBy('salida') as $s) {
            $writer->addRow(Row::fromValuesWithStyles([
                $s->placa,
                $s->vehiculo,
                $s->entradaLocal()->format('d/m/Y h:i A'),
                $s->salidaLocal()->format('d/m/Y h:i A'),
                ParqueaderoIngreso::textoDuracion($s->minutos),
                '$' . number_format($s->tarifa_valor, 0, ',', '.') . ' por ' . ($s->tarifa_tipo === 'minuto' ? 'minuto' : 'hora'),
                (float) $s->valor_cobrado,
                $s->cobrador?->name ?? 'Sin registro',
            ], [6 => $dinero]));
            $n++;
        }
        $detalle->setAutoFilter(new AutoFilter(0, 1, count($columnas) - 1, max(2, $n + 1)));
        $detalle->setSheetView((new SheetView())->withFreezeRow(2));

        $writer->close();

        $archivo = 'parqueadero-' . str($negocio->name)->slug() . '-' . $d['desde']->format('Ymd') . '_' . $d['hasta']->format('Ymd') . '.xlsx';

        return response()->download($ruta, $archivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Todas las métricas del período elegido; las usan la pantalla y el Excel. */
    private function datosParqueadero(Request $request): array
    {
        $negocio = Auth::user();
        abort_unless($negocio && $negocio->esParqueadero() && ! $negocio->esOperador(), 403);

        $zona = ParqueaderoIngreso::ZONA;
        [$desde, $hasta, $rango] = $this->periodo($request, $zona);
        $dias = $desde->diffInDays($hasta) + 1;

        $salidas = $this->salidas($negocio->id, $desde, $hasta);
        $ingresos = (float) $salidas->sum('valor_cobrado');
        $atendidos = $salidas->count();
        $minutosTotal = (int) $salidas->sum('minutos');

        // Período anterior de la misma duración, para saber si el negocio va mejor o peor
        $hastaPrevio = $desde->copy()->subDay();
        $desdePrevio = $hastaPrevio->copy()->subDays($dias - 1);
        $ingresosPrevio = (float) $this->salidas($negocio->id, $desdePrevio, $hastaPrevio)->sum('valor_cobrado');

        // Por tipo de vehículo: el que más ingresos deja primero
        $tipos = $salidas->groupBy('vehiculo')->map(function ($grupo, $nombre) {
            $ingresosTipo = (float) $grupo->sum('valor_cobrado');

            return [
                'nombre' => $nombre,
                'icono' => TipoVehiculo::iconoPara($nombre),
                'total' => $grupo->count(),
                'ingresos' => $ingresosTipo,
                'promedio' => $grupo->count() ? $ingresosTipo / $grupo->count() : 0,
                'tiempoPromedio' => $grupo->count() ? $grupo->sum('minutos') / $grupo->count() : 0,
            ];
        })->sortByDesc(fn ($t) => [$t['ingresos'], $t['total']])->values();

        $entradas = $this->entradas($negocio->id, $desde, $hasta);

        // Lo cobrado en mensualidades en el período (cada pago cuenta el día en que se registró)
        $pagosMensualidad = MensualidadPago::where('user_id', $negocio->id)
            ->where('created_at', '>=', $desde->copy()->startOfDay()->utc())
            ->where('created_at', '<', $hasta->copy()->addDay()->startOfDay()->utc())
            ->get();

        return [
            'negocio' => $negocio,
            'rango' => $rango,
            'rangos' => self::RANGOS,
            'desde' => $desde,
            'hasta' => $hasta,
            'dias' => $dias,
            'salidas' => $salidas,
            'entradas' => $entradas->count(),
            'atendidos' => $atendidos,
            'dentro' => $negocio->parqueaderoIngresos()->dentro()->count(),
            'ingresos' => $ingresos,
            'ingresosPrevio' => $ingresosPrevio,
            'variacion' => $ingresosPrevio > 0 ? round(($ingresos - $ingresosPrevio) / $ingresosPrevio * 100) : null,
            'ticket' => $atendidos ? $ingresos / $atendidos : 0,
            'tiempoPromedio' => $atendidos ? $minutosTotal / $atendidos : 0,
            'tipos' => $tipos,
            'cobradores' => $this->cobradores($salidas),
            'mensualidadesCobradas' => $pagosMensualidad->sum('valor'),
            'mensualidadesPagos' => $pagosMensualidad->count(),
            'serie' => $this->serieDiaria($salidas, $tipos, $desde, $hasta, $dias),
            'horas' => $this->porHora($entradas),
        ];
    }

    /** Salidas registradas en el período (días completos en hora local). */
    private function salidas(int $negocioId, Carbon $desde, Carbon $hasta)
    {
        return ParqueaderoIngreso::where('user_id', $negocioId)
            ->whereNotNull('salida')
            ->where('salida', '>=', $desde->copy()->startOfDay()->utc())
            ->where('salida', '<', $hasta->copy()->addDay()->startOfDay()->utc())
            ->with('cobrador')->get();
    }

    private function entradas(int $negocioId, Carbon $desde, Carbon $hasta)
    {
        return ParqueaderoIngreso::where('user_id', $negocioId)
            ->where('entrada', '>=', $desde->copy()->startOfDay()->utc())
            ->where('entrada', '<', $hasta->copy()->addDay()->startOfDay()->utc())
            ->get();
    }

    /** Cuánto cobró cada persona (dueño u operador) en el período, para cuadrar la caja por usuario. */
    private function cobradores($salidas)
    {
        return $salidas->groupBy(fn ($s) => $s->cobrador?->name ?? 'Sin registro')->map(function ($grupo, $nombre) {
            return [
                'nombre' => $nombre,
                'total' => $grupo->count(),
                'ingresos' => (float) $grupo->sum('valor_cobrado'),
            ];
        })->sortByDesc('ingresos')->values();
    }

    /** Vehículos atendidos por día (o por semana si el período es largo), separados por tipo. */
    private function serieDiaria($salidas, $tipos, Carbon $desde, Carbon $hasta, int $dias): array
    {
        $porSemana = $dias > 62;
        $clave = fn (Carbon $f) => $porSemana ? $f->copy()->startOfWeek()->toDateString() : $f->toDateString();

        $serie = [];
        for ($c = $desde->copy(); $c->lte($hasta); $c->addDay()) {
            $serie[$clave($c)] ??= [
                'etiqueta' => $porSemana ? $c->copy()->startOfWeek()->locale('es')->translatedFormat('j M') : $c->format('j'),
                'titulo' => $porSemana
                    ? 'Semana del ' . $c->copy()->startOfWeek()->locale('es')->translatedFormat('j M')
                    : ucfirst($c->locale('es')->translatedFormat('D j M')),
                'porTipo' => [], 'total' => 0, 'ingresos' => 0.0,
            ];
        }

        foreach ($salidas as $s) {
            $k = $clave($s->salidaLocal());
            if (! isset($serie[$k])) {
                continue;
            }
            $serie[$k]['porTipo'][$s->vehiculo] = ($serie[$k]['porTipo'][$s->vehiculo] ?? 0) + 1;
            $serie[$k]['total']++;
            $serie[$k]['ingresos'] += (float) $s->valor_cobrado;
        }

        return array_values($serie);
    }

    /** Cuántos vehículos entraron en cada hora del día (0 a 23, hora local): sirve para ver las horas pico. */
    private function porHora($entradas): array
    {
        $horas = array_fill(0, 24, 0);
        foreach ($entradas as $e) {
            $horas[(int) $e->entradaLocal()->format('G')]++;
        }

        return $horas;
    }
}
