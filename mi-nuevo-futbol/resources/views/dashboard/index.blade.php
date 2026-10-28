@extends('layouts.app')

@section('title', 'Dashboard')

@section('nav')
    @include('partials.nav-negocio', ['negocio' => $negocio, 'activo' => 'dashboard'])
@endsection

@php
    $m = fn ($v) => '$' . number_format($v, 0, ',', '.');
    $pct = fn ($parte, $todo) => $todo ? round($parte / $todo * 100) : 0;
    $nombreRecurso = $esSalon ? 'servicio' : 'cancha';
    $nombrePersona = $esSalon ? 'profesional' : 'cancha';
    $periodo = $desde->isSameDay($hasta)
        ? ucfirst($desde->locale('es')->translatedFormat('l j \d\e F'))
        : $desde->locale('es')->translatedFormat('j M') . ' – ' . $hasta->locale('es')->translatedFormat('j M Y');

    // Servicios: los 8 con más ingresos, el resto se agrupa (todo está en la tabla)
    $topServicios = $servicios->take(8);
    $restoServicios = $servicios->slice(8);
    $maxIngresos = max(1, (float) $topServicios->max('ingresos'));
    $totalIngresosServ = max(1, (float) $servicios->sum('ingresos'));
    $maxPersonas = max(1, (int) $profesionales->max('total'));

    // Columnas por día
    $maxDia = max(1, collect($serie)->max(fn ($d) => $d['conf'] + $d['pend'] + $d['rech']));
    $techo = $maxDia <= 4 ? 4 : (int) (ceil($maxDia / 4) * 4);
    $cadaEtiqueta = max(1, (int) ceil(count($serie) / 12));
@endphp

@include('dashboard._estilos')

@section('content')
<div class="dash">
    <div class="page-head">
        <div>
            <div class="eyebrow"><x-icon name="chart" size="14" /> Métricas</div>
            <h1>Dashboard</h1>
            <div class="subtitulo">{{ $periodo }} · {{ $dias }} {{ $dias === 1 ? 'día' : 'días' }}</div>
        </div>
        <div class="acciones-cab">
            <button type="button" class="btn btn-secondary" data-abrir-qr><x-icon name="qr" size="17" /> Código QR</button>
            <a href="{{ route('dashboard.excel', request()->query()) }}" class="btn btn-primary" download><x-icon name="spreadsheet" size="17" /> Exportar a Excel</a>
        </div>
    </div>

    {{-- Filtros: una sola fila sobre todo lo que alcanzan --}}
    <div class="dash-filtros">
        <nav class="segmentos" aria-label="Período">
            @foreach ($rangos as $clave => $texto)
                <a href="{{ route('dashboard.index', ['rango' => $clave]) }}" class="{{ $rango === (string) $clave ? 'activo' : '' }}" @if ($rango === (string) $clave) aria-current="true" @endif>{{ $texto }}</a>
            @endforeach
        </nav>
        <div class="rango-propio">
            <form method="GET" action="{{ route('dashboard.index') }}">
                <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" aria-label="Desde" required>
                <span class="sep">a</span>
                <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" aria-label="Hasta" required>
                <button type="submit" class="btn btn-secondary btn-sm {{ $rango === 'personalizado' ? 'btn-activo' : '' }}"><x-icon name="check" size="15" /> Aplicar</button>
            </form>
        </div>
    </div>

    @if ($total === 0)
        <div class="vacio-dash">
            <div class="ico-caja"><x-icon name="chart" size="26" /></div>
            <strong>Aún no hay citas en este período</strong>
            Prueba con otro rango de fechas. Cuando lleguen reservas, aquí verás tus métricas.
        </div>
    @else
        @if ($sinPrecio > 0)
            <div class="aviso-precio"><x-icon name="alert-circle" size="17" /> <span>{{ $sinPrecio }} {{ $sinPrecio === 1 ? 'cita confirmada no tiene' : 'citas confirmadas no tienen' }} precio configurado y no suman en los ingresos. Puedes definir los precios en <b>Configuración</b>.</span></div>
        @endif

        <div class="dash-cima">
            {{-- La cifra principal de la vista --}}
            <div class="hero">
                <div>
                    <div class="etiqueta"><span class="ico"><x-icon name="dollar" size="18" /></span> Ingresos confirmados</div>
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
                    <span>Por confirmar: <b>{{ $m($ingresosPend) }}</b></span>
                    <span>Valor promedio por cita: <b>{{ $m($ticket) }}</b></span>
                </div>
            </div>

            <div class="tiles">
                <div class="tile neutro">
                    <div class="arriba"><span class="nombre">Citas totales</span><span class="ico"><x-icon name="calendar-days" size="18" /></span></div>
                    <div class="valor">{{ $total }}</div>
                    <div class="nota">Reservadas en el período</div>
                </div>
                <div class="tile ok">
                    <div class="arriba"><span class="nombre">Confirmadas</span><span class="ico"><x-icon name="check-circle" size="18" /></span></div>
                    <div class="valor">{{ $conf }}</div>
                    <div class="nota">{{ $tasa }}% del total</div>
                </div>
                <div class="tile espera">
                    <div class="arriba"><span class="nombre">Pendientes</span><span class="ico"><x-icon name="hourglass" size="18" /></span></div>
                    <div class="valor">{{ $pend }}</div>
                    <div class="nota">Esperan tu confirmación</div>
                </div>
                <div class="tile mal">
                    <div class="arriba"><span class="nombre">Rechazadas</span><span class="ico"><x-icon name="x-circle" size="18" /></span></div>
                    <div class="valor">{{ $rech }}</div>
                    <div class="nota">Canceladas ({{ $pct($rech, $total) }}%)</div>
                </div>
            </div>
        </div>

        <div class="dash-rejilla">
            {{-- Estado de las citas --}}
            <section class="grafica" data-grafica>
                <div class="grafica-cab">
                    <div><h2>Estado de las citas</h2><p>Cómo terminó cada reserva del período</p></div>
                </div>
                <div class="estado-barra" role="img" aria-label="{{ $conf }} confirmadas, {{ $pend }} pendientes, {{ $rech }} rechazadas">
                    @foreach ([['Confirmadas', $conf, 'ok'], ['Pendientes', $pend, 'espera'], ['Rechazadas', $rech, 'mal']] as [$etq, $n, $c])
                        @if ($n > 0)<div class="s-{{ $c }}" style="flex: {{ $n }}" data-t="{{ $etq }}" data-l="{{ $c }}|Citas|{{ $n }};|Del total|{{ $pct($n, $total) }}%"></div>@endif
                    @endforeach
                </div>
                <div class="estado-filas">
                    <div class="estado-fila" style="--c: var(--ok)"><span class="ico"><x-icon name="check-circle" size="17" /></span><span>Confirmadas</span><span class="num">{{ $conf }}</span><span class="p">{{ $pct($conf, $total) }}%</span></div>
                    <div class="estado-fila" style="--c: var(--espera)"><span class="ico"><x-icon name="hourglass" size="17" /></span><span>Pendientes</span><span class="num">{{ $pend }}</span><span class="p">{{ $pct($pend, $total) }}%</span></div>
                    <div class="estado-fila" style="--c: var(--mal)"><span class="ico"><x-icon name="x-circle" size="17" /></span><span>Rechazadas</span><span class="num">{{ $rech }}</span><span class="p">{{ $pct($rech, $total) }}%</span></div>
                </div>
            </section>

            {{-- Citas por día --}}
            <section class="grafica" data-grafica>
                <div class="grafica-cab">
                    <div><h2>Citas por {{ $dias > 62 ? 'semana' : 'día' }}</h2><p>Cuándo se concentra la demanda</p></div>
                    <div class="vista-toggle" role="group" aria-label="Tipo de vista">
                        <button type="button" data-vista="grafica" aria-pressed="true"><x-icon name="chart" size="14" /> Gráfica</button>
                        <button type="button" data-vista="tabla" aria-pressed="false"><x-icon name="receipt" size="14" /> Tabla</button>
                    </div>
                </div>
                <div class="vista-grafica">
                    <div class="leyenda"><span class="ok"><i></i>Confirmadas</span><span class="espera"><i></i>Pendientes</span><span class="mal"><i></i>Rechazadas</span></div>
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
                                        @php $suma = $dia['conf'] + $dia['pend'] + $dia['rech']; @endphp
                                        <div class="col" tabindex="0" data-t="{{ $dia['titulo'] }}" data-l="ok|Confirmadas|{{ $dia['conf'] }};espera|Pendientes|{{ $dia['pend'] }};mal|Rechazadas|{{ $dia['rech'] }}" aria-label="{{ $dia['titulo'] }}: {{ $suma }} citas">
                                            @if ($dia['conf'])<span class="s-ok" style="height: {{ $dia['conf'] / $techo * 100 }}%"></span>@endif
                                            @if ($dia['pend'])<span class="s-espera" style="height: {{ $dia['pend'] / $techo * 100 }}%"></span>@endif
                                            @if ($dia['rech'])<span class="s-mal" style="height: {{ $dia['rech'] / $techo * 100 }}%"></span>@endif
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
                        <thead><tr><th>{{ $dias > 62 ? 'Semana' : 'Día' }}</th><th>Confirmadas</th><th>Pendientes</th><th>Rechazadas</th><th>Total</th></tr></thead>
                        <tbody>
                            @foreach ($serie as $dia)
                                @if ($dia['conf'] + $dia['pend'] + $dia['rech'] > 0)
                                    <tr><td>{{ $dia['titulo'] }}</td><td>{{ $dia['conf'] }}</td><td>{{ $dia['pend'] }}</td><td>{{ $dia['rech'] }}</td><td>{{ $dia['conf'] + $dia['pend'] + $dia['rech'] }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot><tr><td>Total</td><td>{{ $conf }}</td><td>{{ $pend }}</td><td>{{ $rech }}</td><td>{{ $total }}</td></tr></tfoot>
                    </table></div>
                </div>
            </section>

            {{-- Ingresos por servicio / cancha --}}
            <section class="grafica" data-grafica>
                <div class="grafica-cab">
                    <div><h2>Ingresos por {{ $nombreRecurso }}</h2><p>Lo que dejó cada {{ $nombreRecurso }} en citas confirmadas</p></div>
                    <div class="vista-toggle" role="group" aria-label="Tipo de vista">
                        <button type="button" data-vista="grafica" aria-pressed="true"><x-icon name="chart" size="14" /> Gráfica</button>
                        <button type="button" data-vista="tabla" aria-pressed="false"><x-icon name="receipt" size="14" /> Tabla</button>
                    </div>
                </div>
                <div class="vista-grafica">
                    <div class="hbars">
                        @foreach ($topServicios as $s)
                            <div class="hbar" tabindex="0" data-t="{{ $s['nombre'] }}" data-l="|Ingresos|{{ $m($s['ingresos']) }};|Confirmadas|{{ $s['conf'] }};|Precio promedio|{{ $m($s['promedio']) }}">
                                <span class="nom" title="{{ $s['nombre'] }}">{{ $s['nombre'] }}</span>
                                <span class="pista"><span style="width: {{ $s['ingresos'] / $maxIngresos * 100 }}%"></span></span>
                                <span class="val">{{ $m($s['ingresos']) }}<small>{{ $s['conf'] }} {{ $s['conf'] === 1 ? 'cita' : 'citas' }}</small></span>
                            </div>
                        @endforeach
                        @if ($restoServicios->isNotEmpty())
                            @php $ingOtros = $restoServicios->sum('ingresos'); $confOtros = $restoServicios->sum('conf'); @endphp
                            <div class="hbar otros" tabindex="0" data-t="Otros ({{ $restoServicios->count() }})" data-l="|Ingresos|{{ $m($ingOtros) }};|Confirmadas|{{ $confOtros }}">
                                <span class="nom">Otros ({{ $restoServicios->count() }})</span>
                                <span class="pista"><span style="width: {{ $ingOtros / $maxIngresos * 100 }}%"></span></span>
                                <span class="val">{{ $m($ingOtros) }}<small>{{ $confOtros }} {{ $confOtros === 1 ? 'cita' : 'citas' }}</small></span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="vista-tabla">
                    <div class="tabla-wrap"><table class="datos">
                        <thead><tr><th>{{ ucfirst($nombreRecurso) }}</th><th>Citas</th><th>Confirmadas</th><th>Rechazadas</th><th>Precio prom.</th><th>Ingresos</th><th>%</th></tr></thead>
                        <tbody>
                            @foreach ($servicios as $s)
                                <tr><td>{{ $s['nombre'] }}</td><td>{{ $s['total'] }}</td><td>{{ $s['conf'] }}</td><td>{{ $s['rech'] }}</td><td>{{ $s['conf'] ? $m($s['promedio']) : '—' }}</td><td>{{ $m($s['ingresos']) }}</td><td>{{ $pct($s['ingresos'], $totalIngresosServ) }}%</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><td>Total</td><td>{{ $total }}</td><td>{{ $conf }}</td><td>{{ $rech }}</td><td>{{ $conf ? $m($ticket) : '—' }}</td><td>{{ $m($ingresos) }}</td><td>100%</td></tr></tfoot>
                    </table></div>
                </div>
            </section>

            {{-- Desempeño por profesional / cancha --}}
            <section class="grafica" data-grafica>
                <div class="grafica-cab">
                    <div><h2>Desempeño por {{ $nombrePersona }}</h2><p>{{ $esSalon ? 'Citas atendidas por cada integrante del equipo' : 'Reservas de cada cancha según su estado' }}</p></div>
                    <div class="vista-toggle" role="group" aria-label="Tipo de vista">
                        <button type="button" data-vista="grafica" aria-pressed="true"><x-icon name="chart" size="14" /> Gráfica</button>
                        <button type="button" data-vista="tabla" aria-pressed="false"><x-icon name="receipt" size="14" /> Tabla</button>
                    </div>
                </div>
                <div class="vista-grafica">
                    <div class="leyenda"><span class="ok"><i></i>Confirmadas</span><span class="espera"><i></i>Pendientes</span><span class="mal"><i></i>Rechazadas</span></div>
                    <div class="hbars">
                        @foreach ($profesionales as $p)
                            <div class="hbar apilada" tabindex="0" data-t="{{ $p['nombre'] }}" data-l="ok|Confirmadas|{{ $p['conf'] }};espera|Pendientes|{{ $p['pend'] }};mal|Rechazadas|{{ $p['rech'] }};|Ingresos|{{ $m($p['ingresos']) }}">
                                <span class="nom" title="{{ $p['nombre'] }}">{{ $p['nombre'] }}</span>
                                <span class="pista" style="width: {{ max(6, $p['total'] / $maxPersonas * 100) }}%">
                                    @if ($p['conf'])<span class="s-ok" style="flex: {{ $p['conf'] }}"></span>@endif
                                    @if ($p['pend'])<span class="s-espera" style="flex: {{ $p['pend'] }}"></span>@endif
                                    @if ($p['rech'])<span class="s-mal" style="flex: {{ $p['rech'] }}"></span>@endif
                                </span>
                                <span class="val">{{ $p['conf'] }}<small>{{ $p['conf'] === 1 ? 'confirmada' : 'confirmadas' }}</small></span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="vista-tabla">
                    <div class="tabla-wrap"><table class="datos">
                        <thead><tr><th>{{ ucfirst($nombrePersona) }}</th><th>Confirmadas</th><th>Pendientes</th><th>Rechazadas</th><th>Total</th><th>Ingresos</th></tr></thead>
                        <tbody>
                            @foreach ($profesionales as $p)
                                <tr><td>{{ $p['nombre'] }}</td><td>{{ $p['conf'] }}</td><td>{{ $p['pend'] }}</td><td>{{ $p['rech'] }}</td><td>{{ $p['total'] }}</td><td>{{ $m($p['ingresos']) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><td>Total</td><td>{{ $conf }}</td><td>{{ $pend }}</td><td>{{ $rech }}</td><td>{{ $total }}</td><td>{{ $m($ingresos) }}</td></tr></tfoot>
                    </table></div>
                </div>
            </section>
        </div>
    @endif
</div>

<div class="tip" id="tip" role="tooltip"></div>
@include('partials.qr-modal', ['negocio' => $negocio])
@endsection

@include('dashboard._scripts')
