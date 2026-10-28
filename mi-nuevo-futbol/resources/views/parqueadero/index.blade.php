@extends('layouts.app')

@section('title', 'Parqueadero')

@section('nav')
    @include('partials.nav-parqueadero', ['negocio' => $negocio, 'activo' => 'panel'])
@endsection

@php
    use App\Models\ParqueaderoIngreso;
    use App\Models\TipoVehiculo;

    $dinero = fn ($v) => '$ ' . number_format((float) $v, 0, ',', '.');
    $recibo = session('recibo');
    $sinTarifa = $tipos->where('tarifa_valor', 0)->count();
    $tipoElegido = (int) old('tipo_id', $tipos->first()?->id);
    $alertasMens = $mensualidades->filter(fn ($m) => in_array($m->estado(), ['por_vencer', 'vencida']));
    $alertaMens = $alertasMens->count();
@endphp

@push('styles')
<style>
    .vista { display: none; }
    .vista.activa { display: block; }

    /* ---------- Operación: ingreso a la izquierda, vehículos dentro a la derecha ---------- */
    .operacion { display: grid; grid-template-columns: minmax(300px, 400px) minmax(0, 1fr); gap: 16px; align-items: start; }
    .operacion > .tarjeta:first-child { position: sticky; top: 92px; }
    .coches-scroll { max-height: calc(100vh - 300px); min-height: 160px; overflow-y: auto; padding-right: 4px; }

    .selector-fecha { display: flex; align-items: center; gap: 8px; }
    .selector-fecha form { margin: 0; }
    .selector-fecha input[type=date] { width: auto; min-width: 170px; }

    .ingreso-form { display: grid; gap: 14px; }
    .ingreso-fila { display: flex; gap: 10px; }
    .ingreso-fila .control { flex: 1; min-width: 0; }
    .ingreso-fila input { font-family: var(--fuente-titulo); font-size: 22px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; }
    .vehiculos { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 10px; }
    .vehiculo { position: relative; }
    .vehiculo input { position: absolute; opacity: 0; inset: 0; cursor: pointer; }
    .vehiculo label { display: flex; align-items: center; gap: 10px; margin: 0; padding: 12px 14px; border-radius: 12px; cursor: pointer; text-transform: none; letter-spacing: 0; font-size: 14.5px; font-weight: 600; color: var(--texto-suave);
        background: var(--superficie-alta); border: 1px solid var(--borde-fuerte); transition: border-color 0.15s, background 0.15s, box-shadow 0.15s; }
    .vehiculo label small { display: block; font-size: 11.5px; font-weight: 500; color: var(--muted); }
    .vehiculo input:checked + label { border-color: var(--acento-vis); color: var(--linea); background: color-mix(in srgb, var(--acento-vis) 9%, transparent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--acento-vis) 13%, transparent); }
    .vehiculo input:focus-visible + label { outline: 2px solid var(--acento-vis); outline-offset: 2px; }


    .coches { display: grid; gap: 12px; }
    .coche { display: grid; grid-template-columns: auto 1fr auto; gap: 16px; align-items: center; padding: 14px 16px; border-radius: 16px; background: var(--superficie-alta); border: 1px solid var(--borde); }
    .coche .placa { display: flex; align-items: center; gap: 10px; font-family: var(--fuente-titulo); font-size: 20px; font-weight: 800; letter-spacing: 2px; padding: 8px 12px; border-radius: 10px; background: color-mix(in srgb, var(--acento-vis) 14%, transparent); border: 1px solid color-mix(in srgb, var(--acento-vis) 30%, transparent); }
    .coche .detalle { display: grid; gap: 3px; font-size: 13.5px; color: var(--muted); min-width: 0; }
    .coche .detalle b { color: var(--linea); font-weight: 600; }
    .coche .cobro { display: flex; align-items: center; gap: 8px; }
    .coche .cobro form { margin: 0; }
    .coche .valor-vivo { font-family: var(--fuente-titulo); font-size: 18px; font-weight: 800; text-align: right; margin-right: 6px; white-space: nowrap; }
    .coche .valor-vivo small { display: block; font-family: var(--fuente); font-size: 11px; font-weight: 500; color: var(--muted); }

    .historial { width: 100%; border-collapse: collapse; font-size: 14px; }
    .historial th { text-align: left; font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); padding: 0 10px 10px; }
    .historial td { padding: 11px 10px; border-top: 1px solid var(--borde); }
    .historial td.der, .historial th.der { text-align: right; }
    .historial .placa-h { font-family: var(--fuente-titulo); font-weight: 700; letter-spacing: 1.5px; }
    .historial .tipo-h { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 13px; }
    .historial-scroll { overflow-x: auto; }
    .sin-items { color: var(--muted); font-size: 14px; padding: 18px; text-align: center; border: 1px dashed var(--borde-fuerte); border-radius: 12px; }

    /* ---------- Tarifas y equipo: tarjetas en rejilla en lugar de una lista larga ---------- */
    .rejilla-tarjetas { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 14px; }
    .tarifa-item { padding: 14px; border-radius: 14px; background: var(--superficie-alta); border: 1px solid var(--borde); }
    .tarifa-item form { margin: 0; }
    .tarifa-fila { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 8px; }
    .tarifa-fila .control { min-width: 0; }
    .tarifa-acciones { display: flex; gap: 8px; margin-top: 10px; }
    .tarifa-acciones .btn-primary, .tarifa-acciones .btn-success { flex: 1; }
    .tarifa-item.nuevo { background: none; border-style: dashed; border-color: var(--borde-fuerte); }
    .rejilla-tarjetas .tarifa-fila { grid-template-columns: 1fr 1fr; }
    .rejilla-tarjetas .tarifa-fila .control:first-child { grid-column: 1 / -1; }
    .vista-cab { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .vista-cab h2 { font-size: 19px; margin: 0 0 3px; }
    .vista-cab p { margin: 0; font-size: 13.5px; color: var(--muted); }
    .aviso-tarifa { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 14px; font-size: 13.5px; margin-bottom: 16px; color: var(--texto-suave); background: color-mix(in srgb, var(--acento-vis) 12%, transparent); }
    .aviso-tarifa .icon { margin-top: 1px; color: var(--acento-vis); flex: none; }

    /* ---------- Mensualidades ---------- */
    .aviso-mens { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 12px 16px; margin-bottom: 16px; border-radius: 14px; font-size: 13.5px; color: var(--texto-suave);
        background: color-mix(in srgb, var(--acento-vis) 12%, transparent); border: 1px solid color-mix(in srgb, var(--acento-vis) 30%, transparent); }
    .aviso-mens > .icon { color: var(--acento-vis); flex: none; }
    .aviso-mens > div { flex: 1; min-width: 200px; }
    .aviso-mens strong { display: block; color: var(--linea); font-size: 14px; }
    .mens-form .campo { margin-bottom: 12px; }
    .mens-filtros { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
    .mens-filtros button { display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border: 0; border-radius: 999px; cursor: pointer; font-family: var(--fuente); font-size: 12.5px; font-weight: 600; color: var(--muted); background: var(--superficie-alta); }
    .mens-filtros button b { font-variant-numeric: tabular-nums; color: var(--linea); }
    .mens-filtros button.activo { color: var(--linea); box-shadow: inset 0 0 0 1px var(--acento-vis); background: color-mix(in srgb, var(--acento-vis) 10%, transparent); }
    .mens-lista { display: grid; gap: 12px; max-height: calc(100vh - 330px); min-height: 160px; overflow-y: auto; padding-right: 4px; }
    .mens-item { --c: var(--muted); padding: 14px 16px; border-radius: 16px; background: var(--superficie-alta); border: 1px solid var(--borde); border-left: 4px solid var(--c); }
    .mens-item.est-vigente { --c: #0ca30c; } .mens-item.est-por_vencer { --c: #fab219; } .mens-item.est-vencida { --c: #d03b3b; } .mens-item.est-inactiva { opacity: 0.7; }
    .mens-cab { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .mens-cab .placa { display: inline-flex; align-items: center; gap: 8px; font-family: var(--fuente-titulo); font-size: 17px; font-weight: 800; letter-spacing: 2px; padding: 6px 10px; border-radius: 10px; background: color-mix(in srgb, var(--acento-vis) 14%, transparent); border: 1px solid color-mix(in srgb, var(--acento-vis) 30%, transparent); }
    .mens-quien { flex: 1; min-width: 120px; display: grid; }
    .mens-quien small { color: var(--muted); font-size: 12.5px; }
    .mens-estado { padding: 4px 11px; border-radius: 999px; font-size: 12px; font-weight: 700; color: var(--c); background: color-mix(in srgb, var(--c) 15%, transparent); }
    .mens-fechas { display: flex; justify-content: space-between; gap: 6px 16px; flex-wrap: wrap; margin: 10px 0; font-size: 13.5px; color: var(--muted); }
    .mens-fechas b { color: var(--linea); }
    .mens-acciones { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .mens-acciones form { margin: 0; }
    .mens-renovar { display: flex; gap: 6px; }
    .mens-renovar .control { width: 120px; }
    .mens-renovar input { min-height: 34px; padding-top: 4px; padding-bottom: 4px; }
    .mens-pagos { margin-top: 10px; font-size: 13px; color: var(--muted); }
    .mens-pagos summary { cursor: pointer; font-weight: 600; }
    .mens-pagos div { display: flex; justify-content: space-between; padding: 5px 0; border-top: 1px solid var(--borde); }
    .mens-pagos b { color: var(--linea); }

    @media (max-width: 1100px) {
        .operacion { grid-template-columns: minmax(0, 1fr); }
        .operacion > .tarjeta:first-child { position: static; }
        .coches-scroll { max-height: none; }
    }
    }
    @media (max-width: 720px) {
        .ingreso-fila { flex-direction: column; }
        .coche { grid-template-columns: 1fr; gap: 12px; }
        .coche .cobro { justify-content: space-between; }
        .tarifa-fila { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <div class="eyebrow"><x-icon name="car" size="14" /> Parqueadero</div>
            <h1>Control de vehículos</h1>
            <div class="subtitulo">Registra la placa y el tipo al entrar, y cobra según el tiempo que estuvo.</div>
        </div>
    </div>

    @if (session('exito'))
        <div class="alerta alerta-exito" role="status"><x-icon name="check-circle" /> <div>{{ session('exito') }}</div></div>
    @endif

    @if (session('aviso'))
        <div class="aviso-tarifa" role="alert"><x-icon name="alert-circle" size="17" /> <span>{{ session('aviso') }}</span></div>
    @endif

    @if (session('error_mensualidad'))
        <div class="alerta alerta-error" role="alert"><x-icon name="alert-circle" /> <div>{{ session('error_mensualidad') }}</div></div>
    @endif

    @if ($errors->any())
        <div class="alerta alerta-error" role="alert">
            <x-icon name="alert-circle" />
            <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        </div>
    @endif

    @if ($recibo)
        @include('parqueadero._recibo', ['recibo' => $recibo, 'negocio' => $negocio])
    @endif

    @unless ($esOperador)
        @include('parqueadero._pestanas', ['activa' => 'operacion', 'modo' => 'boton', 'sinTarifa' => $sinTarifa, 'alertaMens' => $alertaMens])
    @endunless

            {{-- ================= OPERACIÓN ================= --}}
            <section class="vista activa" id="vista-operacion" role="tabpanel">
    <div class="stats">
        <div class="stat">
            <span class="ico-caja verde"><x-icon name="car" size="20" /></span>
            <div><div class="valor">{{ $dentro->count() }}</div><div class="etiqueta">Dentro ahora</div></div>
        </div>
        @unless ($esOperador)
            <div class="stat">
                <span class="ico-caja"><x-icon name="log-out" size="20" /></span>
                <div><div class="valor">{{ $salidas->count() }}</div><div class="etiqueta">Salidas {{ $esHoy ? 'de hoy' : 'del día' }}</div></div>
            </div>
            <div class="stat">
                <span class="ico-caja"><x-icon name="dollar" size="20" /></span>
                <div><div class="valor">{{ $dinero($recaudado) }}</div><div class="etiqueta">Recaudado {{ $esHoy ? 'hoy' : 'ese día' }}</div></div>
            </div>
        @endunless
    </div>
                @if ($alertaMens > 0)
                    <div class="aviso-mens" role="alert">
                        <x-icon name="calendar-days" size="18" />
                        <div>
                            <strong>{{ $alertaMens === 1 ? '1 mensualidad requiere atención' : $alertaMens . ' mensualidades requieren atención' }}</strong>
                            <span>@foreach ($alertasMens->take(4) as $m){{ $m->placa }} ({{ mb_strtolower($m->textoDias()) }}){{ $loop->last ? '' : ' · ' }}@endforeach{{ $alertaMens > 4 ? ' · y ' . ($alertaMens - 4) . ' más' : '' }}</span>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" data-ir-tab="mensualidades">Ver mensualidades</button>
                    </div>
                @endif

                <div class="operacion">
                    <section class="tarjeta">
                        <div class="tarjeta-titulo">
                            <span class="ico-caja"><x-icon name="log-in" /></span>
                            <h2>Registrar ingreso <small>La hora de entrada es la de este momento</small></h2>
                        </div>
                        <form method="POST" action="{{ route('parqueadero.ingresar') }}" class="ingreso-form">
                            @csrf
                            <div class="vehiculos" role="radiogroup" aria-label="Tipo de vehículo">
                                @foreach ($tipos as $t)
                                    <div class="vehiculo">
                                        <input type="radio" name="tipo_id" id="tipo{{ $t->id }}" value="{{ $t->id }}" @checked($tipoElegido === $t->id) required>
                                        <label for="tipo{{ $t->id }}"><x-icon :name="$t->icono()" size="22" /><span>{{ $t->nombre }}<small>{{ $t->textoTarifa() }}</small></span></label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="ingreso-fila">
                                <div class="control"><x-icon name="car" /><input type="text" name="placa" value="{{ old('placa') }}" placeholder="ABC123" maxlength="12" autocomplete="off" autofocus required></div>
                                <button type="submit" class="btn btn-primary"><x-icon name="plus" /> Ingresar</button>
                            </div>
                            <div class="ayuda" style="margin:0">Una cicla puede no tener placa: escribe un número o código para reconocerla.</div>
                        </form>
                    </section>

                    <section class="tarjeta">
                        <div class="tarjeta-titulo">
                            <span class="ico-caja"><x-icon name="car" /></span>
                            <h2>Dentro del parqueadero <small>{{ $dentro->count() }} {{ $dentro->count() === 1 ? 'vehículo' : 'vehículos' }}</small></h2>
                        </div>

                        @if ($dentro->isEmpty())
                            <div class="sin-items">No hay vehículos dentro en este momento.</div>
                        @else
                            <div class="coches-scroll">
                                <div class="coches" id="coches">
                                    @foreach ($dentro as $c)
                                        <article class="coche" data-entrada="{{ $c->entrada->getTimestampMs() }}" data-tipo="{{ $c->mensualidad_id ? 'mensualidad' : $c->tipo->tarifa_tipo }}" data-valor="{{ $c->mensualidad_id ? 0 : $c->tipo->tarifa_valor }}">
                                            <span class="placa"><x-icon :name="TipoVehiculo::iconoPara($c->vehiculo)" size="20" /> {{ $c->placa }}</span>
                                            <div class="detalle">
                                                <span>{{ $c->vehiculo }} · entró <b>{{ $c->entradaLocal()->format('d/m h:i a') }}</b>@if ($c->mensualidad_id) <span class="chip verde" style="margin-left:6px">Mensualidad</span>@endif</span>
                                                <span>Lleva <b class="tiempo">—</b></span>
                                            </div>
                                            <div class="cobro">
                                                <div class="valor-vivo"><span class="monto">—</span><small>a cobrar ahora</small></div>
                                                <form method="POST" action="{{ route('parqueadero.salir', $c) }}" class="form-salida" data-placa="{{ $c->placa }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm"><x-icon name="log-out" size="16" /> Salida</button>
                                                </form>
                                                @unless ($esOperador)
                                                    <form method="POST" action="{{ route('parqueadero.eliminar', $c) }}" onsubmit="return confirm('¿Anular el ingreso de {{ $c->placa }}? Úsalo solo si se registró por error.');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger btn-sm btn-icon" aria-label="Anular ingreso de {{ $c->placa }}" title="Anular ingreso"><x-icon name="trash" size="16" /></button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </section>
                </div>
            </section>

            @unless ($esOperador)
                {{-- ================= TARIFAS ================= --}}
                <section class="vista" id="vista-tarifas" role="tabpanel">
                    <div class="vista-cab">
                        <div><h2>Tipos de vehículo y tarifas</h2><p>La tarifa se aplica a las próximas salidas. Por hora: cada hora o fracción cuenta completa. Por minuto: tiempo exacto.</p></div>
                    </div>
                    @if ($sinTarifa > 0)
                        <div class="aviso-tarifa"><x-icon name="alert-circle" size="17" /> <span>{{ $sinTarifa === 1 ? 'Hay un tipo de vehículo' : "Hay {$sinTarifa} tipos de vehículo" }} con tarifa en $ 0: se cobraría gratis. Defínela abajo.</span></div>
                    @endif
                    <div class="rejilla-tarjetas">
                        @foreach ($tipos as $t)
                            <div class="tarifa-item">
                                <form method="POST" action="{{ route('parqueadero.tipos.actualizar', $t) }}">
                                    @csrf
                                    <div class="tarifa-fila">
                                        <div class="control"><x-icon :name="$t->icono()" /><input type="text" name="nombre" value="{{ $t->nombre }}" maxlength="30" aria-label="Nombre del tipo" required></div>
                                        <div class="control"><x-icon name="timer" /><select name="tarifa_tipo" aria-label="Cobrar por">
                                            <option value="hora" @selected($t->tarifa_tipo === 'hora')>Por hora</option>
                                            <option value="minuto" @selected($t->tarifa_tipo === 'minuto')>Por minuto</option>
                                        </select></div>
                                        <div class="control"><x-icon name="dollar" /><input type="number" name="tarifa_valor" min="0" step="1" value="{{ (int) $t->tarifa_valor }}" aria-label="Valor de la tarifa" required></div>
                                    </div>
                                    <div class="tarifa-acciones">
                                        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="check" size="16" /> Guardar</button>
                                        <button type="submit" formaction="{{ route('parqueadero.tipos.eliminar', $t) }}" formnovalidate class="btn btn-danger btn-sm btn-icon" aria-label="Eliminar {{ $t->nombre }}" title="Eliminar" onclick="return confirm('¿Eliminar el tipo {{ $t->nombre }}? Su historial se conserva.');"><x-icon name="trash" size="16" /></button>
                                    </div>
                                </form>
                            </div>
                        @endforeach

                        <div class="tarifa-item nuevo">
                            <form method="POST" action="{{ route('parqueadero.tipos.crear') }}">
                                @csrf
                                <div class="tarifa-fila">
                                    <div class="control"><x-icon name="plus" /><input type="text" name="nombre" placeholder="Nuevo tipo (ej: Camioneta)" maxlength="30" required></div>
                                    <div class="control"><x-icon name="timer" /><select name="tarifa_tipo" aria-label="Cobrar por">
                                        <option value="hora">Por hora</option>
                                        <option value="minuto">Por minuto</option>
                                    </select></div>
                                    <div class="control"><x-icon name="dollar" /><input type="number" name="tarifa_valor" min="0" step="1" placeholder="Valor" aria-label="Valor de la tarifa" required></div>
                                </div>
                                <div class="tarifa-acciones"><button type="submit" class="btn btn-success btn-sm"><x-icon name="plus" size="16" /> Agregar tipo</button></div>
                            </form>
                        </div>
                    </div>
                </section>

                {{-- ================= MENSUALIDADES ================= --}}
                <section class="vista" id="vista-mensualidades" role="tabpanel">
                    <div class="vista-cab">
                        <div><h2>Mensualidades</h2><p>Vehículos que pagan por mes: entran y salen sin cobro mientras la mensualidad esté vigente. Te avisamos {{ \App\Models\Mensualidad::AVISO_DIAS }} días antes de que se cumpla el mes.</p></div>
                    </div>

                    <div class="operacion">
                        <section class="tarjeta">
                            <div class="tarjeta-titulo">
                                <span class="ico-caja"><x-icon name="plus-circle" /></span>
                                <h2>Nueva mensualidad <small>Registra el primer pago</small></h2>
                            </div>
                            <form method="POST" action="{{ route('parqueadero.mensualidades.crear') }}" class="mens-form">
                                @csrf
                                <div class="campo">
                                    <label for="mens_tipo">Tipo de vehículo</label>
                                    <div class="control"><x-icon name="car" /><select id="mens_tipo" name="tipo_id" required>
                                        @foreach ($tipos as $t)<option value="{{ $t->id }}" @selected((int) old('tipo_id') === $t->id)>{{ $t->nombre }}</option>@endforeach
                                    </select></div>
                                </div>
                                <div class="campo">
                                    <label for="mens_placa">Placa</label>
                                    <div class="control"><x-icon name="car" /><input type="text" id="mens_placa" name="placa" value="{{ old('placa') }}" maxlength="12" placeholder="ABC123" autocomplete="off" style="text-transform:uppercase" required></div>
                                </div>
                                <div class="campo">
                                    <label for="mens_cliente">Cliente</label>
                                    <div class="control"><x-icon name="user" /><input type="text" id="mens_cliente" name="cliente" value="{{ old('cliente') }}" maxlength="100" placeholder="Nombre de quien paga" required></div>
                                </div>
                                <div class="campo">
                                    <label for="mens_tel">WhatsApp / teléfono</label>
                                    <div class="control"><x-icon name="phone" /><input type="tel" id="mens_tel" name="telefono" value="{{ old('telefono') }}" maxlength="30" placeholder="Para avisarle cuando venza"></div>
                                </div>
                                <div class="grid-2">
                                    <div class="campo">
                                        <label for="mens_valor">Valor del mes</label>
                                        <div class="control"><x-icon name="dollar" /><input type="number" id="mens_valor" name="valor" min="0" step="1" value="{{ old('valor') }}" placeholder="0" required></div>
                                    </div>
                                    <div class="campo">
                                        <label for="mens_inicio">Desde</label>
                                        <div class="control"><x-icon name="calendar" /><input type="date" id="mens_inicio" name="inicio" value="{{ old('inicio', \App\Models\Mensualidad::hoy()) }}"></div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Registrar mensualidad</button>
                            </form>
                        </section>

                        <section class="tarjeta">
                            @php
                                $cuentaEstado = $mensualidades->groupBy(fn ($m) => $m->estado())->map->count();
                            @endphp
                            <div class="mens-filtros" role="group" aria-label="Filtrar mensualidades">
                                <button type="button" class="activo" data-filtro="todas">Todas <b>{{ $mensualidades->count() }}</b></button>
                                <button type="button" data-filtro="vigente">Vigentes <b>{{ $cuentaEstado['vigente'] ?? 0 }}</b></button>
                                <button type="button" data-filtro="por_vencer">Por vencer <b>{{ $cuentaEstado['por_vencer'] ?? 0 }}</b></button>
                                <button type="button" data-filtro="vencida">Vencidas <b>{{ $cuentaEstado['vencida'] ?? 0 }}</b></button>
                                <button type="button" data-filtro="inactiva">Inactivas <b>{{ $cuentaEstado['inactiva'] ?? 0 }}</b></button>
                            </div>

                            @if ($mensualidades->isEmpty())
                                <div class="sin-items">Aún no hay mensualidades. Registra la primera con el formulario.</div>
                            @else
                                <div class="mens-lista">
                                    @foreach ($mensualidades->sortBy(fn ($m) => [$m->activa ? 0 : 1, $m->vence->toDateString()]) as $m)
                                        @php $est = $m->estado(); @endphp
                                        <article class="mens-item est-{{ $est }}" data-estado="{{ $est }}">
                                            <div class="mens-cab">
                                                <span class="placa"><x-icon :name="TipoVehiculo::iconoPara($m->vehiculo)" size="18" /> {{ $m->placa }}</span>
                                                <div class="mens-quien">
                                                    <strong>{{ $m->cliente }}</strong>
                                                    <small>{{ $m->vehiculo }}@if ($m->telefono) · {{ $m->telefono }}@endif</small>
                                                </div>
                                                <span class="mens-estado">{{ ['vigente' => 'Vigente', 'por_vencer' => 'Por vencer', 'vencida' => 'Vencida', 'inactiva' => 'De baja'][$est] }}</span>
                                            </div>
                                            <div class="mens-fechas">
                                                <span>Hasta <b>{{ $m->vence->format('d/m/Y') }}</b>@if ($est !== 'inactiva') · {{ $m->textoDias() }}@endif</span>
                                                <span>Mes: <b>{{ $dinero($m->valor) }}</b></span>
                                            </div>
                                            <div class="mens-acciones">
                                                @if ($m->activa)
                                                    <form method="POST" action="{{ route('parqueadero.mensualidades.renovar', $m) }}" class="mens-renovar">
                                                        @csrf
                                                        <div class="control"><x-icon name="dollar" /><input type="number" name="valor" min="0" step="1" value="{{ (int) $m->valor }}" aria-label="Valor del mes para {{ $m->placa }}" required></div>
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Registrar el pago de otro mes para {{ $m->placa }}?');"><x-icon name="check" size="16" /> Renovar mes</button>
                                                    </form>
                                                @endif
                                                @if ($wa = $m->urlWhatsapp($negocio->name))
                                                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm"><x-icon name="phone" size="16" /> Avisar</a>
                                                @endif
                                                <form method="POST" action="{{ route('parqueadero.mensualidades.estado', $m) }}" @if ($m->activa) onsubmit="return confirm('¿Dar de baja la mensualidad de {{ $m->placa }}? Dejará de entrar sin cobro. Su historial se conserva.');" @endif>
                                                    @csrf
                                                    <button type="submit" class="btn btn-secondary btn-sm">{{ $m->activa ? 'Dar de baja' : 'Reactivar' }}</button>
                                                </form>
                                            </div>
                                            <details class="mens-pagos">
                                                <summary>Pagos ({{ $m->pagos->count() }})</summary>
                                                @foreach ($m->pagos->take(12) as $p)
                                                    <div><span>{{ $p->desde->format('d/m/Y') }} → {{ $p->hasta->format('d/m/Y') }}</span><b>{{ $dinero($p->valor) }}</b></div>
                                                @endforeach
                                            </details>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    </div>
                </section>

                {{-- ================= EQUIPO ================= --}}
                <section class="vista" id="vista-equipo" role="tabpanel">
                    <div class="vista-cab">
                        <div><h2>Equipo</h2><p>Los operadores entran por el mismo login y solo registran ingresos y salidas: no ven tarifas, dashboard, historial ni lo recaudado, y no pueden anular ingresos.</p></div>
                    </div>
                    <div class="rejilla-tarjetas">
                        @foreach ($operadores as $op)
                            <div class="tarifa-item">
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:10px">
                                    <div style="min-width:0">
                                        <strong>{{ $op->name }}</strong>
                                        <div class="ayuda" style="margin:2px 0 0; overflow-wrap:anywhere">{{ $op->email }}</div>
                                    </div>
                                    <span class="chip {{ $op->activo ? 'verde' : 'apagado' }}">{{ $op->activo ? 'Activo' : 'Desactivado' }}</span>
                                </div>
                                <form method="POST" action="{{ route('parqueadero.equipo.clave', $op) }}" style="margin-bottom:8px">
                                    @csrf
                                    <div class="tarifa-acciones" style="margin-top:0">
                                        <div class="control" style="flex:1; min-width:0"><x-icon name="lock" /><input type="password" name="password" placeholder="Nueva contraseña" minlength="6" autocomplete="new-password" aria-label="Nueva contraseña de {{ $op->name }}" required></div>
                                        <button type="submit" class="btn btn-secondary btn-sm"><x-icon name="key" size="16" /> Cambiar</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('parqueadero.equipo.estado', $op) }}" @if ($op->activo) onsubmit="return confirm('¿Desactivar a {{ $op->name }}? No podrá entrar. Su historial se conserva.');" @endif>
                                    @csrf
                                    @if ($op->activo)
                                        <button type="submit" class="btn btn-secondary btn-sm btn-block"><x-icon name="power" size="16" /> Desactivar</button>
                                    @else
                                        <button type="submit" class="btn btn-success btn-sm btn-block"><x-icon name="power" size="16" /> Activar</button>
                                    @endif
                                </form>
                            </div>
                        @endforeach

                        <div class="tarifa-item nuevo">
                            <form method="POST" action="{{ route('parqueadero.equipo.crear') }}">
                                @csrf
                                <div style="display:grid; gap:8px">
                                    <div class="control"><x-icon name="user" /><input type="text" name="name" placeholder="Nombre del operador" maxlength="100" required></div>
                                    <div class="control"><x-icon name="mail" /><input type="email" name="email" placeholder="Correo para iniciar sesión" autocomplete="off" required></div>
                                    <div class="control"><x-icon name="lock" /><input type="password" name="password" placeholder="Contraseña (mínimo 6)" minlength="6" autocomplete="new-password" required></div>
                                </div>
                                <div class="tarifa-acciones"><button type="submit" class="btn btn-success btn-sm"><x-icon name="plus" size="16" /> Crear operador</button></div>
                            </form>
                        </div>
                    </div>
                    @if ($operadores->isEmpty())
                        <div class="ayuda" style="margin-top:12px">Aún no tienes operadores. Crea uno para que otra persona registre las entradas y salidas sin ver lo demás.</div>
                    @endif
                </section>

                {{-- ================= MARCA ================= --}}
                <section class="vista" id="vista-marca" role="tabpanel">
                    <div class="vista-cab">
                        <div><h2>Marca y colores</h2><p>Tu logo, tu nombre comercial y tus colores se ven en todo tu panel.</p></div>
                    </div>
                    @include('configuracion._marca', [
                        'accionMarca' => route('parqueadero.marca'),
                        'iconoMarca' => 'car',
                        'vpTitulo' => 'Panel del parqueadero',
                        'vpSub' => 'Así se verá tu panel',
                        'vpEslogan' => 'Control de vehículos',
                        'vpChips' => ['Carro', 'Moto', 'Cicla', 'Camión'],
                        'vpBoton' => 'Registrar ingreso',
                    ])
                </section>

                {{-- ================= HISTORIAL ================= --}}
                <section class="vista" id="vista-historial" role="tabpanel">
                    <div class="vista-cab">
                        <div><h2>Salidas registradas</h2><p>{{ \Carbon\Carbon::parse($fecha)->locale('es')->translatedFormat('l j \d\e F') }}</p></div>
                        <div class="selector-fecha">
                            <form method="GET" action="{{ route('parqueadero.index') }}">
                                <input type="date" name="fecha" value="{{ $fecha }}" max="{{ now(ParqueaderoIngreso::ZONA)->format('Y-m-d') }}" onchange="this.form.requestSubmit()" aria-label="Ver otro día">
                            </form>
                            @unless ($esHoy)
                                <a href="{{ route('parqueadero.index') }}#historial" class="btn btn-secondary btn-sm">Hoy</a>
                            @endunless
                        </div>
                    </div>
                    <section class="tarjeta">
                        @if ($salidas->isEmpty())
                            <div class="sin-items">No hay salidas registradas este día.</div>
                        @else
                            <div class="historial-scroll">
                                <table class="historial">
                                    <thead>
                                        <tr><th>Placa</th><th>Entrada</th><th>Salida</th><th>Tiempo</th><th class="der">Cobrado</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($salidas as $s)
                                            <tr>
                                                <td><span class="placa-h">{{ $s->placa }}</span><br><span class="tipo-h"><x-icon :name="TipoVehiculo::iconoPara($s->vehiculo)" size="14" /> {{ $s->vehiculo }}{{ $s->cobrador ? ' · ' . $s->cobrador->name : '' }}</span></td>
                                                <td class="num">{{ $s->entradaLocal()->format('h:i a') }}</td>
                                                <td class="num">{{ $s->salidaLocal()->format('h:i a') }}</td>
                                                <td>{{ ParqueaderoIngreso::textoDuracion($s->minutos) }}</td>
                                                <td class="der num"><b>{{ $dinero($s->valor_cobrado) }}</b></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </section>
            @endunless
@endsection

@include('configuracion._marca-estilos')

@push('scripts')
<script>
@unless ($esOperador)
(() => {
@include('configuracion._marca-js')
})();
@endunless

// ---------- Menú lateral: una sola sección visible; se recuerda la última tras guardar ----------
(() => {
    const botones = [...document.querySelectorAll('[data-tab]')];
    const vistas = [...document.querySelectorAll('.vista')];
    if (!botones.length) return;

    function abrir(clave) {
        if (!document.getElementById('vista-' + clave)) clave = 'operacion';
        vistas.forEach(v => v.classList.toggle('activa', v.id === 'vista-' + clave));
        botones.forEach(b => { const on = b.dataset.tab === clave; b.classList.toggle('activo', on); b.setAttribute('aria-selected', on); });
        history.replaceState(null, '', clave === 'operacion' ? location.pathname + location.search : '#' + clave);
    }

    // Al guardar algo dentro de una sección, se vuelve a ella después de recargar
    vistas.forEach(v => v.querySelectorAll('form').forEach(f => f.addEventListener('submit', () => {
        try { sessionStorage.setItem('pq_tab', v.id.replace('vista-', '')); } catch (e) {}
    })));

    let guardada = null;
    try { guardada = sessionStorage.getItem('pq_tab'); sessionStorage.removeItem('pq_tab'); } catch (e) {}
    abrir(location.hash.replace('#', '') || guardada || 'operacion');

    botones.forEach(b => b.addEventListener('click', () => abrir(b.dataset.tab)));
    document.querySelectorAll('[data-ir-tab]').forEach(b => b.addEventListener('click', () => { abrir(b.dataset.irTab); scrollTo({ top: 0, behavior: 'smooth' }); }));

    // Mensualidades: filtrar la lista por estado
    document.querySelectorAll('[data-filtro]').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('[data-filtro]').forEach(x => x.classList.toggle('activo', x === b));
        document.querySelectorAll('.mens-item').forEach(i => { i.hidden = b.dataset.filtro !== 'todas' && i.dataset.estado !== b.dataset.filtro; });
    }));
})();

const pesos = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
const contenedor = document.getElementById('coches');

// Mismas reglas que el servidor: mínimo 1 minuto; por hora, cada hora o fracción cuenta completa
function calcular(minutos, tipo, valor) {
    if (tipo === 'mensualidad') return 0;   // con mensualidad vigente no se cobra
    const unidades = tipo === 'minuto' ? minutos : Math.ceil(minutos / 60);
    return unidades * valor;
}

function textoDuracion(min) {
    const h = Math.floor(min / 60), m = min % 60;
    return ((h ? h + ' h ' : '') + (m || !h ? m + ' min' : '')).trim();
}

function actualizarCoches() {
    if (!contenedor) return;
    contenedor.querySelectorAll('.coche').forEach(c => {
        const minutos = Math.max(1, Math.ceil((Date.now() - Number(c.dataset.entrada)) / 60000));
        c.querySelector('.tiempo').textContent = textoDuracion(minutos);
        c.querySelector('.monto').textContent = pesos.format(calcular(minutos, c.dataset.tipo, parseFloat(c.dataset.valor)));
    });
}
actualizarCoches();
setInterval(actualizarCoches, 15000);

// Confirmar la salida mostrando el valor que se va a cobrar
document.querySelectorAll('.form-salida').forEach(f => f.addEventListener('submit', e => {
    actualizarCoches();
    const c = f.closest('.coche');
    if (!confirm(`Registrar la salida de ${f.dataset.placa}?\nTiempo: ${c.querySelector('.tiempo').textContent}\nTotal a cobrar: ${c.querySelector('.monto').textContent}`)) e.preventDefault();
}));
</script>
@endpush
