@extends('layouts.app')

@section('title', 'Dashboard')

@section('nav')
    @include('partials.nav-parqueadero', ['negocio' => $negocio, 'activo' => 'dashboard'])
@endsection

@php
    use App\Models\ParqueaderoIngreso;

    $m = fn ($v) => '$' . number_format($v, 0, ',', '.');
    $pct = fn ($parte, $todo) => $todo ? round($parte / $todo * 100) : 0;
    $tiempo = fn ($min) => ParqueaderoIngreso::textoDuracion((int) round($min));
    $periodo = $desde->isSameDay($hasta)
        ? ucfirst($desde->locale('es')->translatedFormat('l j \d\e F'))
        : $desde->locale('es')->translatedFormat('j M') . ' – ' . $hasta->locale('es')->translatedFormat('j M Y');

    // Cada tipo de vehículo conserva su color en todas las gráficas
    $color = fn ($i) => 't' . ($i % 6);
    $indiceTipo = $tipos->pluck('nombre')->flip();
    $maxIngresos = max(1, (float) $tipos->max('ingresos'));
    $totalIngresosTipo = max(1, (float) $tipos->sum('ingresos'));

    // Columnas por día (apiladas por tipo)
    $maxDia = max(1, collect($serie)->max('total'));
    $techo = $maxDia <= 4 ? 4 : (int) (ceil($maxDia / 4) * 4);
    $cadaEtiqueta = max(1, (int) ceil(count($serie) / 12));

    // Entradas por hora del día
    $maxHora = max(1, max($horas));
    $techoHora = $maxHora <= 4 ? 4 : (int) (ceil($maxHora / 4) * 4);
    $horaPico = array_search(max($horas), $horas);
    $formatoHora = fn ($h) => ($h % 12 === 0 ? 12 : $h % 12) . ($h < 12 ? ' a. m.' : ' p. m.');
@endphp

@include('dashboard._estilos')

@push('styles')
<style>
    .dash { --t0: #5b9bf0; --t1: #f2a93b; --t2: #3fb68b; --t3: #c47be0; --t4: #e8776a; --t5: #8aa0b8; }
    .s-t0 { background: var(--t0) !important; } .s-t1 { background: var(--t1) !important; } .s-t2 { background: var(--t2) !important; }
    .s-t3 { background: var(--t3) !important; } .s-t4 { background: var(--t4) !important; } .s-t5 { background: var(--t5) !important; }
    .leyenda .t0 { --c: var(--t0); } .leyenda .t1 { --c: var(--t1); } .leyenda .t2 { --c: var(--t2); }
    .leyenda .t3 { --c: var(--t3); } .leyenda .t4 { --c: var(--t4); } .leyenda .t5 { --c: var(--t5); }
    .col.hora { max-width: 18px; }
    .col.hora.pico > span { background: var(--acento-vis); }
    .col.hora > span { background: var(--serie); }
</style>
@endpush

@section('content')
<div class="dash">
    <div class="page-head">
        <div>
            <div class="eyebrow"><x-icon name="chart" size="14" /> Métricas</div>
            <h1>Dashboard</h1>
            <div class="subtitulo">{{ $periodo }} · {{ $dias }} {{ $dias === 1 ? 'día' : 'días' }}</div>
        </div>
        <div class="acciones-cab">
            <a href="{{ route('parqueadero.dashboard.excel', request()->query()) }}" class="btn btn-primary" download><x-icon name="spreadsheet" size="17" /> Exportar a Excel</a>
        </div>
    </div>

    @include('parqueadero._pestanas', ['activa' => 'dashboard', 'modo' => 'enlace'])

    <div class="dash-filtros">
        <nav class="segmentos" aria-label="Período">
            @foreach ($rangos as $clave => $texto)
                <a href="{{ route('parqueadero.dashboard', ['rango' => $clave]) }}" class="{{ $rango === (string) $clave ? 'activo' : '' }}" @if ($rango === (string) $clave) aria-current="true" @endif>{{ $texto }}</a>
            @endforeach
        </nav>
        <div class="rango-propio">
            <form method="GET" action="{{ route('parqueadero.dashboard') }}">
                <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" aria-label="Desde" required>
                <span class="sep">a</span>
                <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" aria-label="Hasta" required>
                <button type="submit" class="btn btn-secondary btn-sm {{ $rango === 'personalizado' ? 'btn-activo' : '' }}"><x-icon name="check" size="15" /> Aplicar</button>
            </form>
        </div>
    </div>

    @if ($atendidos === 0 && $entradas === 0)
        <div class="vacio-dash">
            <div class="ico-caja"><x-icon name="chart" size="26" /></div>
            <strong>Aún no hay movimiento en este período</strong>
            Prueba con otro rango de fechas. Cuando registres entradas y salidas, aquí verás tus métricas.
        </div>
    @else
        <div class="dash-cima">
            <div class="hero">
                <div>
                    <div class="etiqueta"><span class="ico"><x-icon name="dollar" size="18" /></span> Ingresos cobrados</div>
                </div>
                <div>
                    <div class="cifra">{{ $m($ingresos) }}</div>
                    @if (! is_null($variacion))
                        <span class="variacion {{ $variacion >= 0 ? 'sube' : 'baja' }}"><x-icon :name="$variacion >= 0 ? 'trending-up' : 'trending-down'" size="15" /> {{ $variacion >= 0 ? '+' : '−' }}{{ abs($variacion) > 999 ? 'más de 999' : abs($variacion) }}% frente al período anterior</span>
                    @else
                        <span class="variacion">Sin período anterior para comparar</span>
                    @endif
                </div>
                <div class="pie">
                    <span>Promedio por vehículo: <b>{{ $m($ticket) }}</b></span>
                    <span>Mensualidades cobradas: <b>{{ $m($mensualidadesCobradas) }}</b>@if ($mensualidadesPagos) ({{ $mensualidadesPagos }} {{ $mensualidadesPagos === 1 ? 'pago' : 'pagos' }})@endif</span>
                    <span>Tiempo promedio: <b>{{ $atendidos ? $tiempo($tiempoPromedio) : '—' }}</b></span>
                </div>
            </div>

            <div class="tiles">
                <div class="tile ok">
                    <div class="arriba"><span class="nombre">Vehículos atendidos</span><span class="ico"><x-icon name="check-circle" size="18" /></span></div>
                    <div class="valor">{{ $atendidos }}</div>
                    <div class="nota">Con salida y cobro registrados</div>
                </div>
                <div class="tile neutro">
                    <div class="arriba"><span class="nombre">Ingresaron</span><span class="ico"><x-icon name="log-in" size="18" /></span></div>
                    <div class="valor">{{ $entradas }}</div>
                    <div class="nota">Entradas en el período</div>
                </div>
                <div class="tile espera">
                    <div class="arriba"><span class="nombre">Dentro ahora</span><span class="ico"><x-icon name="car" size="18" /></span></div>
                    <div class="valor">{{ $dentro }}</div>
                    <div class="nota">Aún sin salida</div>
                </div>
                <div class="tile neutro">
                    <div class="arriba"><span class="nombre">Tiempo promedio</span><span class="ico"><x-icon name="clock" size="18" /></span></div>
                    <div class="valor" style="font-size: clamp(24px, 3vw, 30px)">{{ $atendidos ? $tiempo($tiempoPromedio) : '—' }}</div>
                    <div class="nota">Permanencia por vehículo</div>
                </div>
            </div>
        </div>

        <div class="dash-rejilla">
            {{-- Vehículos por tipo --}}
            <section class="grafica" data-grafica>
                <div class="grafica-cab">
                    <div><h2>Vehículos por tipo</h2><p>Cuántos atendiste de cada uno</p></div>
                </div>
                @if ($tipos->isEmpty())
                    <div class="vacio-dash" style="padding: 28px 12px">Todavía no hay salidas registradas en el período.</div>
                @else
                    <div class="estado-barra" role="img" aria-label="Vehículos atendidos por tipo">
                        @foreach ($tipos as $t)
                            <div class="s-{{ $color($loop->index) }}" style="flex: {{ $t['total'] }}" data-t="{{ $t['nombre'] }}" data-l="var(--{{ $color($loop->index) }})|Vehículos|{{ $t['total'] }};|Del total|{{ $pct($t['total'], $atendidos) }}%"></div>
                        @endforeach
                    </div>
                    <div class="estado-filas">
                        @foreach ($tipos as $t)
                            <div class="estado-fila" style="--c: var(--{{ $color($loop->index) }})"><span class="ico"><x-icon :name="$t['icono']" size="17" /></span><span>{{ $t['nombre'] }}</span><span class="num">{{ $t['total'] }}</span><span class="p">{{ $pct($t['total'], $atendidos) }}%</span></div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Vehículos por día --}}
            <section class="grafica" data-grafica>
                <div class="grafica-cab">
                    <div><h2>Vehículos por {{ $dias > 62 ? 'semana' : 'día' }}</h2><p>Cuándo se concentra el movimiento</p></div>
                    <div class="vista-toggle" role="group" aria-label="Tipo de vista">
                        <button type="button" data-vista="grafica" aria-pressed="true"><x-icon name="chart" size="14" /> Gráfica</button>
                        <button type="button" data-vista="tabla" aria-pressed="false"><x-icon name="receipt" size="14" /> Tabla</button>
                    </div>
                </div>
                <div class="vista-grafica">
                    <div class="leyenda">@foreach ($tipos as $t)<span class="{{ $color($loop->index) }}"><i></i>{{ $t['nombre'] }}</span>@endforeach</div>
                    <div class="columnas">
                        <div class="eje-y" aria-hidden="true">
                            @foreach ([0, 1, 2, 3, 4] as $paso)
                                <span style="bottom: {{ $paso * 25 }}%">{{ (int) ($techo / 4 * $paso) }}</span>
                            @endforeach
                        </div>
                        <div class="plano">
                            <div class="rejilla">
                                @foreach ([25, 50, 75, 100] as $linea)<i style="bottom: {{ $linea }}%"></i>@endforeach
                                <div class="cols">
                                    @foreach ($serie as $dia)
                                        <div class="col" tabindex="0" data-t="{{ $dia['titulo'] }}" data-l="{{ collect($dia['porTipo'])->map(fn ($n, $nombre) => 'var(--' . $color($indiceTipo[$nombre] ?? 5) . ')|' . $nombre . '|' . $n)->push('|Cobrado|' . $m($dia['ingresos']))->implode(';') }}" aria-label="{{ $dia['titulo'] }}: {{ $dia['total'] }} vehículos">
                                            @foreach ($dia['porTipo'] as $nombre => $n)
                                                <span class="s-{{ $color($indiceTipo[$nombre] ?? 5) }}" style="height: {{ $n / $techo * 100 }}%"></span>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ejex" aria-hidden="true">
                                @foreach ($serie as $i => $dia)<span>{{ $i % $cadaEtiqueta === 0 ? $dia['etiqueta'] : '' }}</span>@endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="vista-tabla">
                    <div class="tabla-wrap"><table class="datos">
                        <thead><tr><th>{{ $dias > 62 ? 'Semana' : 'Día' }}</th>@foreach ($tipos as $t)<th>{{ $t['nombre'] }}</th>@endforeach<th>Total</th><th>Cobrado</th></tr></thead>
                        <tbody>
                            @foreach ($serie as $dia)
                                @if ($dia['total'] > 0)
                                    <tr><td>{{ $dia['titulo'] }}</td>@foreach ($tipos as $t)<td>{{ $dia['porTipo'][$t['nombre']] ?? 0 }}</td>@endforeach<td>{{ $dia['total'] }}</td><td>{{ $m($dia['ingresos']) }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot><tr><td>Total</td>@foreach ($tipos as $t)<td>{{ $t['total'] }}</td>@endforeach<td>{{ $atendidos }}</td><td>{{ $m($ingresos) }}</td></tr></tfoot>
                    </table></div>
                </div>
            </section>

            {{-- Ingresos por tipo --}}
            <section class="grafica" data-grafica>
                <div class="grafica-cab">
                    <div><h2>Ingresos por tipo de vehículo</h2><p>Lo que dejó cada tipo en salidas cobradas</p></div>
                    <div class="vista-toggle" role="group" aria-label="Tipo de vista">
                        <button type="button" data-vista="grafica" aria-pressed="true"><x-icon name="chart" size="14" /> Gráfica</button>
                        <button type="button" data-vista="tabla" aria-pressed="false"><x-icon name="receipt" size="14" /> Tabla</button>
                    </div>
                </div>
                <div class="vista-grafica">
                    <div class="hbars">
                        @foreach ($tipos as $t)
                            <div class="hbar" tabindex="0" data-t="{{ $t['nombre'] }}" data-l="|Ingresos|{{ $m($t['ingresos']) }};|Vehículos|{{ $t['total'] }};|Valor promedio|{{ $m($t['promedio']) }};|Tiempo promedio|{{ $tiempo($t['tiempoPromedio']) }}">
                                <span class="nom" title="{{ $t['nombre'] }}">{{ $t['nombre'] }}</span>
                                <span class="pista"><span style="width: {{ $t['ingresos'] / $maxIngresos * 100 }}%"></span></span>
                                <span class="val">{{ $m($t['ingresos']) }}<small>{{ $t['total'] }} {{ $t['total'] === 1 ? 'vehículo' : 'vehículos' }}</small></span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="vista-tabla">
                    <div class="tabla-wrap"><table class="datos">
                        <thead><tr><th>Tipo</th><th>Atendidos</th><th>Tiempo prom.</th><th>Valor prom.</th><th>Ingresos</th><th>%</th></tr></thead>
                        <tbody>
                            @foreach ($tipos as $t)
                                <tr><td>{{ $t['nombre'] }}</td><td>{{ $t['total'] }}</td><td>{{ $tiempo($t['tiempoPromedio']) }}</td><td>{{ $m($t['promedio']) }}</td><td>{{ $m($t['ingresos']) }}</td><td>{{ $pct($t['ingresos'], $totalIngresosTipo) }}%</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><td>Total</td><td>{{ $atendidos }}</td><td>{{ $atendidos ? $tiempo($tiempoPromedio) : '—' }}</td><td>{{ $atendidos ? $m($ticket) : '—' }}</td><td>{{ $m($ingresos) }}</td><td>100%</td></tr></tfoot>
                    </table></div>
                </div>
            </section>

            {{-- Cobrado por usuario: para cuadrar la caja de cada operador --}}
            <section class="grafica">
                <div class="grafica-cab">
                    <div><h2>Cobrado por usuario</h2><p>Lo que registró cada persona del equipo en el período</p></div>
                </div>
                @if ($cobradores->isEmpty())
                    <div class="vacio-dash" style="padding: 28px 12px">Todavía no hay salidas registradas en el período.</div>
                @else
                    @php $maxCobrado = max(1, (float) $cobradores->max('ingresos')); @endphp
                    <div class="hbars">
                        @foreach ($cobradores as $c)
                            <div class="hbar" tabindex="0" data-t="{{ $c['nombre'] }}" data-l="|Cobrado|{{ $m($c['ingresos']) }};|Vehículos|{{ $c['total'] }}">
                                <span class="nom" title="{{ $c['nombre'] }}">{{ $c['nombre'] }}</span>
                                <span class="pista"><span style="width: {{ $c['ingresos'] / $maxCobrado * 100 }}%"></span></span>
                                <span class="val">{{ $m($c['ingresos']) }}<small>{{ $c['total'] }} {{ $c['total'] === 1 ? 'vehículo' : 'vehículos' }}</small></span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Horas de mayor entrada --}}
            <section class="grafica" data-grafica>
                <div class="grafica-cab">
                    <div><h2>Horas de mayor entrada</h2><p>A qué hora llegan más vehículos{{ $entradas ? ' · pico: ' . $formatoHora($horaPico) : '' }}</p></div>
                </div>
                <div class="columnas">
                    <div class="eje-y" aria-hidden="true">
                        @foreach ([0, 1, 2, 3, 4] as $paso)
                            <span style="bottom: {{ $paso * 25 }}%">{{ (int) ($techoHora / 4 * $paso) }}</span>
                        @endforeach
                    </div>
                    <div class="plano">
                        <div class="rejilla">
                            @foreach ([25, 50, 75, 100] as $linea)<i style="bottom: {{ $linea }}%"></i>@endforeach
                            <div class="cols">
                                @foreach ($horas as $h => $n)
                                    <div class="col hora {{ $entradas && $h === $horaPico ? 'pico' : '' }}" tabindex="0" data-t="{{ $formatoHora($h) }}" data-l="|Ingresos|{{ $n }}" aria-label="{{ $formatoHora($h) }}: {{ $n }} ingresos">
                                        @if ($n)<span style="height: {{ $n / $techoHora * 100 }}%"></span>@endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="ejex" aria-hidden="true">
                            @foreach ($horas as $h => $n)<span>{{ $h % 3 === 0 ? $h : '' }}</span>@endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endif
</div>

<div class="tip" id="tip" role="tooltip"></div>
@endsection

@include('dashboard._scripts')
