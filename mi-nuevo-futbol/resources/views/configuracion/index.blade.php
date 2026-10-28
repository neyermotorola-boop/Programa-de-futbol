@extends('layouts.app')

@use('App\Models\Cancha')
@use('App\Models\User')
@use('App\Models\CategoriaCancha')

@php
    $esSalon = $negocio->tipo_negocio === 'salon';
    $nombreRecursos = $esSalon ? 'Profesionales' : 'Canchas';
    $diasNegocio = $negocio->diasAtencion();
    $dinero = fn ($v) => '$' . number_format($v, 0, ',', '.');
    $pestanas = array_filter([
        'marca' => ['Marca y colores', 'sparkles'],
        'horario' => ['Horario' . ($esSalon ? ' y sillas' : ''), 'clock'],
        'recursos' => [$nombreRecursos, $esSalon ? 'users' : 'pitch'],
        'categorias' => $esSalon ? null : ['Deportes y superficies', 'layers'],
        'servicios' => $esSalon ? ['Servicios', 'tag'] : null,
        'general' => ['General', 'settings'],
    ]);
@endphp

@section('title', 'Configuración')

@section('nav')
    @include('partials.nav-negocio', ['negocio' => $negocio, 'activo' => 'configuracion'])
@endsection

@push('styles')
<style>
    .contenedor { padding-top: 20px; padding-bottom: 20px; }
    .cfg-head { display: flex; align-items: center; justify-content: space-between; gap: 12px 20px; flex-wrap: wrap; margin-bottom: 14px; }
    .cfg-head h1 { font-size: clamp(21px, 2.4vw, 26px); }
    .cfg-head .subtitulo { margin-top: 2px; font-size: 14px; }

    /* Pestañas */
    .cfg-tabs { display: flex; gap: 4px; overflow-x: auto; border-bottom: 1px solid var(--borde-fuerte); margin-bottom: 16px; scrollbar-width: none; }
    .cfg-tabs::-webkit-scrollbar { display: none; }
    .cfg-tab {
        display: inline-flex; align-items: center; gap: 8px; flex: none; padding: 11px 16px; margin-bottom: -1px;
        background: none; border: 0; border-bottom: 2px solid transparent; color: var(--muted); cursor: pointer;
        font-family: var(--fuente); font-size: 14px; font-weight: 600; white-space: nowrap; border-radius: 10px 10px 0 0;
        transition: color 0.15s, background 0.15s, border-color 0.15s;
    }
    .cfg-tab:hover { color: var(--linea); background: var(--superficie-alta); }
    .cfg-tab.activo { color: var(--linea); border-bottom-color: var(--floodlight); }
    .cfg-tab.activo .icon { color: var(--acento-vis); }
    .cfg-tab:focus-visible { outline: 3px solid color-mix(in srgb, var(--floodlight) 40%, transparent); outline-offset: -3px; }

    .cfg-panel { display: none; }
    .cfg-panel.activo { display: block; }
    @media (min-width: 1025px) {
        /* Cada pestaña ocupa la pantalla; solo las listas largas se desplazan por dentro */
        .cfg-panel.activo { height: calc(100vh - 262px); min-height: 440px; overflow: auto; padding-right: 2px; }
    }

    .titulo-mini { font-family: var(--fuente-titulo); font-size: 15px; font-weight: 700; margin: 0 0 12px; display: flex; align-items: center; gap: 8px; }
    .titulo-mini .icon { color: var(--acento-vis); }
    .cfg-panel .tarjeta { padding: 16px 18px; }
    .cfg-panel .campo { margin-bottom: 12px; }
    .cfg-panel label, .cfg-panel .label { margin-bottom: 5px; font-size: 12.5px; }
    .cfg-panel input[type], .cfg-panel select { min-height: 40px; padding-top: 8px; padding-bottom: 8px; font-size: 14px; }

    /* ----- Horario ----- */
    .dias { display: flex; gap: 6px; flex-wrap: wrap; }
    .dia input { position: absolute; opacity: 0; pointer-events: none; }
    .dia span { display: inline-flex; align-items: center; justify-content: center; min-width: 50px; height: 38px; padding: 0 10px; border-radius: 10px; border: 1px solid var(--borde-fuerte); background: var(--superficie-alta); color: var(--muted); font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.15s; user-select: none; }
    .dia input:checked + span { background: var(--floodlight); border-color: var(--floodlight); color: var(--texto-acento); }
    .dia input:focus-visible + span { box-shadow: 0 0 0 3px color-mix(in srgb, var(--floodlight) 35%, transparent); }
    .dias.chicos .dia span { min-width: 38px; height: 32px; font-size: 12px; padding: 0 6px; }
    .puestos { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .puestos-visual { display: flex; gap: 5px; flex-wrap: wrap; }
    .puestos-visual span { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; background: color-mix(in srgb, var(--acento-vis) 15%, transparent); color: var(--acento-vis); }
    .horario-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; align-items: start; }

    /* ----- Lista + detalle (canchas, profesionales, servicios) ----- */
    .md { display: grid; grid-template-columns: 250px minmax(0, 1fr); gap: 14px; height: 100%; min-height: 0; }
    .md.con-visor { grid-template-columns: 230px minmax(0, 1fr) minmax(0, 1fr); }
    .md-lista { display: flex; flex-direction: column; gap: 6px; overflow-y: auto; min-height: 0; padding-right: 4px; }
    .md-lista .btn-nuevo { flex: none; position: sticky; top: 0; z-index: 1; }
    .md-item {
        display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; padding: 9px 11px; border-radius: 11px; flex: none;
        background: var(--superficie); border: 1px solid var(--borde); color: var(--linea); cursor: pointer; font-family: var(--fuente);
        transition: border-color 0.15s, background 0.15s;
    }
    .md-item:hover { border-color: var(--borde-fuerte); background: var(--superficie-alta); }
    .md-item.activo { border-color: var(--floodlight); background: var(--superficie-alta); box-shadow: 0 0 0 3px color-mix(in srgb, var(--floodlight) 13%, transparent); }
    .md-item .ico { width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center; flex: none; background: color-mix(in srgb, var(--primario-vis) 14%, transparent); color: var(--primario-vis); }
    .md-item .txt { min-width: 0; flex: 1; }
    .md-item b { display: block; font-size: 13.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .md-item small { display: block; color: var(--muted); font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .md-item.inactivo { opacity: 0.55; }
    .md-item .punto { width: 8px; height: 8px; border-radius: 50%; background: var(--primario-vis); flex: none; }
    .md-item.inactivo .punto { background: var(--muted); }
    .md-detalle { min-height: 0; overflow-y: auto; }
    .md-form { display: none; }
    .md-form.activo { display: block; }
    .md-form .recurso-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .md-form .recurso-head .ico-caja { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: color-mix(in srgb, var(--primario-vis) 14%, transparent); color: var(--primario-vis); flex: none; }
    .md-form .recurso-head input { font-family: var(--fuente-titulo); font-weight: 700; font-size: 16px; }
    .md-form.es-nuevo { border-style: dashed; }
    .foto-cancha { margin: 2px 0 14px; padding: 12px; border-radius: 14px; background: color-mix(in srgb, var(--linea) 4%, transparent); }
    .foto-fila { display: flex; gap: 12px; align-items: flex-start; }
    .foto-prev { width: 92px; height: 76px; flex: none; border-radius: 12px; background: var(--superficie-alta) center / cover no-repeat; display: grid; place-items: center; color: var(--muted); }
    .foto-ctrl { min-width: 0; flex: 1; }
    .foto-botones { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .visor-controles.arriba { top: 10px; bottom: auto; justify-content: flex-end; }
    .cat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; align-items: start; }
    .cat-fila { display: flex; gap: 8px; align-items: center; margin-bottom: 8px; }
    .cat-fila > input[type=text] { flex: 1; min-width: 0; }
    .cat-fila > select { flex: 0 1 190px; min-width: 0; }
    .cat-fila > input[type=color] { flex: none; width: 44px; height: 40px; padding: 3px; border-radius: 10px; background: var(--superficie-alta); border: 1px solid var(--borde-fuerte); cursor: pointer; min-height: 0; }
    .cat-fila .uso { flex: none; min-width: 26px; text-align: center; font-size: 12px; font-weight: 700; color: var(--muted); padding: 4px 7px; border-radius: 999px; background: color-mix(in srgb, var(--linea) 7%, transparent); }
    .cat-fila.nueva { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--borde); }
    @media (max-width: 1024px) { .cat-grid { grid-template-columns: 1fr; } }
    @media (max-width: 720px) { .cat-fila { flex-wrap: wrap; } .cat-fila > select { flex: 1 1 100%; order: 3; } }
    .iconos { display: flex; flex-wrap: wrap; gap: 6px; }
    .ico-opcion input { position: absolute; opacity: 0; pointer-events: none; }
    .ico-opcion span { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-width: 40px; height: 40px; padding: 0 10px; border-radius: 11px; cursor: pointer; font-size: 12.5px; font-weight: 600;
        background: var(--superficie-alta); color: var(--muted); border: 1px solid transparent; transition: color 0.15s, background 0.15s, border-color 0.15s, transform 0.15s; }
    .ico-opcion:hover span { color: var(--linea); transform: translateY(-1px); }
    .ico-opcion input:checked + span { color: var(--acento-vis); border-color: var(--floodlight); background: color-mix(in srgb, var(--floodlight) 11%, transparent); }
    .ico-opcion input:focus-visible + span { box-shadow: 0 0 0 3px color-mix(in srgb, var(--floodlight) 35%, transparent); }
    .switches { display: flex; gap: 8px 20px; flex-wrap: wrap; margin: 2px 0 14px; }
    .switch { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; font-weight: 500; color: var(--texto-suave); margin: 0; user-select: none; }
    .switch input { position: absolute; opacity: 0; pointer-events: none; }
    .switch .riel { width: 40px; height: 23px; border-radius: 999px; background: color-mix(in srgb, var(--linea) 18%, transparent); position: relative; transition: background 0.15s; flex: none; }
    .switch .riel::after { content: ""; position: absolute; top: 3px; left: 3px; width: 17px; height: 17px; border-radius: 50%; background: #fff; transition: transform 0.15s; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
    .switch input:checked + .riel { background: var(--floodlight); }
    .switch input:checked + .riel::after { transform: translateX(17px); }
    .switch input:focus-visible + .riel { box-shadow: 0 0 0 3px color-mix(in srgb, var(--floodlight) 35%, transparent); }
    .recurso-pie { display: flex; gap: 8px; justify-content: flex-end; align-items: center; flex-wrap: wrap; padding-top: 12px; border-top: 1px solid var(--borde); margin-top: 4px; }

    .visor { position: relative; aspect-ratio: 4 / 3; max-height: 100%; width: 100%; border-radius: var(--radio); overflow: hidden; border: 1px solid var(--borde-fuerte); background: #0b1610; }
    .visor canvas { width: 100% !important; height: 100% !important; display: block; cursor: grab; }
    .visor canvas:active { cursor: grabbing; }
    .visor-controles { position: absolute; left: 10px; right: 10px; bottom: 10px; display: flex; justify-content: space-between; align-items: center; gap: 8px; pointer-events: none; z-index: 2; }
    .visor-controles > * { pointer-events: auto; }
    .visor-nombre { background: rgba(8,19,13,0.78); color: #f2f2e9; backdrop-filter: blur(8px); padding: 5px 11px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .dia-noche { display: inline-flex; background: rgba(8,19,13,0.78); backdrop-filter: blur(8px); border-radius: 999px; padding: 3px; border: 1px solid rgba(242,242,233,0.17); }
    .dia-noche button { border: 0; background: none; color: #a9b5ad; padding: 5px 11px; border-radius: 999px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: var(--fuente); }
    .dia-noche button.activo { background: var(--floodlight); color: var(--texto-acento); }
    .visor-ayuda { color: var(--muted); font-size: 12.5px; margin-top: 8px; }

    /* ----- General ----- */
    .general-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .opcion-switch { display: flex; gap: 14px; align-items: flex-start; padding: 12px 14px; border: 1px solid var(--borde-fuerte); border-radius: 12px; background: var(--superficie-alta); margin-bottom: 14px; }
    .opcion-switch .switch { margin-top: 2px; }
    .opcion-switch strong { display: block; color: var(--linea); font-size: 14px; margin-bottom: 2px; }
    .opcion-switch p { margin: 0; color: var(--muted); font-size: 12.5px; line-height: 1.45; }
    .general-form section + section { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--borde); }
    .cfg-panel textarea { width: 100%; background: var(--superficie-alta); border: 1px solid var(--borde-fuerte); color: var(--linea); padding: 9px 14px; border-radius: var(--radio-sm); font-size: 14px; font-family: var(--fuente); resize: vertical; min-height: 64px; transition: border-color 0.15s, box-shadow 0.15s; }
    .cfg-panel textarea:focus { outline: none; border-color: var(--floodlight); box-shadow: 0 0 0 4px color-mix(in srgb, var(--floodlight) 14%, transparent); }
    .marcadores { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; align-items: center; }
    .marcadores button { font-family: var(--fuente); font-size: 12px; padding: 3px 9px; border-radius: 999px; border: 1px dashed var(--borde-fuerte); background: none; color: var(--muted); cursor: pointer; }
    .marcadores button:hover { color: var(--linea); border-color: var(--floodlight); }
    .nota { display: flex; gap: 10px; align-items: flex-start; padding: 10px 12px; border-radius: 10px; font-size: 12.5px; color: var(--muted); background: color-mix(in srgb, var(--linea) 5%, transparent); }
    .nota .icon { margin-top: 1px; flex: none; color: var(--acento-vis); }
    .correo-previa { position: sticky; top: 0; }
    .correo-previa .asunto { font-size: 13px; color: var(--muted); margin-bottom: 8px; display: flex; gap: 6px; flex-wrap: wrap; }
    .correo-previa .asunto b { color: var(--linea); font-weight: 600; }
    .correo-marco { background: #f2f4f3; border-radius: var(--radio); padding: 16px 10px; border: 1px solid var(--borde-fuerte); transition: opacity 0.2s, filter 0.2s; }
    .correo-marco.apagado { opacity: 0.5; filter: grayscale(0.6); }

    @media (max-width: 1200px) {
        .md.con-visor { grid-template-columns: 230px minmax(0, 1fr); }
        .md.con-visor .md-visor { grid-column: 1 / -1; }
    }
    @media (max-width: 1024px) {
        .marca-grid, .general-grid, .horario-grid { grid-template-columns: 1fr; }
        .correo-previa { position: static; }
        .md, .md.con-visor { grid-template-columns: 1fr; height: auto; }
        .md-lista { flex-direction: row; overflow-x: auto; overflow-y: visible; padding: 0 0 6px; }
        .md-lista .btn-nuevo { position: static; flex: none; width: auto; }
        .md-item { width: 190px; flex: none; }
        .md-detalle { overflow: visible; }
    }
    @media (max-width: 720px) {
        .grid-3 { grid-template-columns: 1fr; gap: 0; }
        .cfg-tab { padding: 10px 13px; }
        .colores { grid-template-columns: 1fr; }
        .cfg-head .btn { width: 100%; }
        .recurso-pie .btn { flex: 1; }
    }
</style>
@endpush

@include('configuracion._marca-estilos')

@section('content')
    <div class="cfg-head">
        <div>
            <h1>Configuración</h1>
            <div class="subtitulo">Personaliza tu negocio. Los cambios se reflejan al instante en tu página de reservas.</div>
        </div>
        <div class="acciones-cab">
            <button type="button" class="btn btn-secondary btn-sm" data-abrir-qr><x-icon name="qr" size="16" /> Código QR</button>
            <a href="{{ route('reservas.index', $negocio->slug) }}" target="_blank" class="btn btn-secondary btn-sm"><x-icon name="external" size="16" /> Ver mi página</a>
        </div>
    </div>

    @if (session('exito'))
        <div class="alerta alerta-exito" role="status" style="margin-bottom:12px"><x-icon name="check-circle" /> <div>{{ session('exito') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="alerta alerta-error" role="alert" style="margin-bottom:12px">
            <x-icon name="alert-circle" />
            <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        </div>
    @endif

    <div class="cfg-tabs" role="tablist" aria-label="Secciones de configuración">
        @foreach ($pestanas as $clave => [$texto, $icono])
            <button type="button" class="cfg-tab" role="tab" data-tab="{{ $clave }}" aria-controls="panel-{{ $clave }}"><x-icon :name="$icono" size="16" /> {{ $texto }}</button>
        @endforeach
    </div>

    {{-- ================= MARCA ================= --}}
    <section class="cfg-panel" id="panel-marca" role="tabpanel">
        @include('configuracion._marca', [
            'accionMarca' => route('configuracion.marca'),
            'iconoMarca' => $esSalon ? 'scissors' : 'pitch',
            'vpTitulo' => $esSalon ? 'Reserva tu cita' : 'Reserva tu cancha',
            'vpSub' => 'Así se verá tu página para tus clientes',
        ])
    </section>

    {{-- ================= HORARIO ================= --}}
    <section class="cfg-panel" id="panel-horario" role="tabpanel">
        <form method="POST" action="{{ route('configuracion.horario') }}" class="tarjeta">
            @csrf
            <div class="horario-grid">
                <div>
                    <h3 class="titulo-mini"><x-icon name="calendar" size="17" /> Días y horas de atención</h3>
                    <span class="label">Días de atención</span>
                    <div class="dias" style="margin-bottom:16px">
                        @foreach (User::DIAS_SEMANA as $num => $dia)
                            <label class="dia" style="margin:0"><input type="checkbox" name="dias_atencion[]" value="{{ $num }}" @checked(in_array($num, old('dias_atencion', $diasNegocio)))><span>{{ mb_substr($dia, 0, 3) }}</span></label>
                        @endforeach
                    </div>
                    <div class="grid-2">
                        <div class="campo">
                            <label for="hora_apertura">Abre a las</label>
                            <div class="control"><x-icon name="clock" /><input type="time" id="hora_apertura" name="hora_apertura" value="{{ old('hora_apertura', $negocio->hora_apertura) }}" required></div>
                        </div>
                        <div class="campo">
                            <label for="hora_cierre">Cierra a las</label>
                            <div class="control"><x-icon name="clock" /><input type="time" id="hora_cierre" name="hora_cierre" value="{{ old('hora_cierre', $negocio->hora_cierre) }}" required></div>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="titulo-mini"><x-icon :name="$esSalon ? 'users' : 'hourglass'" size="17" /> {{ $esSalon ? 'Capacidad del salón' : 'Reservas' }}</h3>
                    @if ($esSalon)
                        <div class="campo">
                            <label for="tipo_puesto">Tipo de puesto</label>
                            <div class="control"><x-icon name="building" />
                                <select id="tipo_puesto" name="tipo_puesto">
                                    @foreach (User::TIPOS_PUESTO as $valor => $texto)
                                        <option value="{{ $valor }}" @selected(old('tipo_puesto', $negocio->tipo_puesto) === $valor)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="campo">
                            <label for="numero_puestos">¿Cuántas personas se atienden al mismo tiempo?</label>
                            <div class="puestos">
                                <div class="control" style="width:130px"><x-icon name="users" /><input type="number" id="numero_puestos" name="numero_puestos" min="1" max="50" value="{{ old('numero_puestos', $negocio->numero_puestos) }}" required></div>
                                <div class="puestos-visual" id="puestosVisual"></div>
                            </div>
                            <div class="ayuda">Es el número de sillas, consultorios o cabinas. Nunca se agendan más citas simultáneas que este número.</div>
                        </div>
                        <div class="grid-2">
                            <div class="campo">
                                <label for="intervalo_agenda">Horas de inicio cada</label>
                                <div class="control"><x-icon name="clock" />
                                    <select id="intervalo_agenda" name="intervalo_agenda">
                                        @foreach (User::INTERVALOS_AGENDA as $min => $texto)
                                            <option value="{{ $min }}" @selected((int) old('intervalo_agenda', $negocio->intervalo_agenda ?: 30) === $min)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="campo">
                                <label for="margen_minutos">Margen entre citas (min)</label>
                                <div class="control"><x-icon name="timer" /><input type="number" id="margen_minutos" name="margen_minutos" min="0" max="120" step="5" value="{{ old('margen_minutos', $negocio->margen_minutos ?? 0) }}" required></div>
                            </div>
                        </div>
                        <div class="ayuda" style="margin:-4px 0 12px">Las horas de inicio se ofrecen cada ese intervalo, y además pegadas al final de otra cita para no dejar huecos. El margen son los minutos libres después de cada cita (limpiar, preparar): se suman a lo que ocupa. Cada servicio puede tener su propio margen.</div>
                        <div class="campo">
                            <label for="anticipacion_minutos">Anticipación mínima para reservar</label>
                            <div class="control"><x-icon name="hourglass" />
                                <select id="anticipacion_minutos" name="anticipacion_minutos">
                                    @foreach (User::ANTICIPACIONES as $min => $texto)
                                        <option value="{{ $min }}" @selected((int) old('anticipacion_minutos', $negocio->anticipacion_minutos ?? 0) === $min)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="ayuda">Las horas que ya pasaron nunca se ofrecen. Con anticipación, tampoco se ofrecen las más cercanas a este momento.</div>
                        </div>
                    @else
                        <div class="campo">
                            <label for="max_horas_por_reserva">Máximo de horas por reserva</label>
                            <div class="control"><x-icon name="hourglass" /><input type="number" id="max_horas_por_reserva" name="max_horas_por_reserva" min="1" max="12" value="{{ old('max_horas_por_reserva', $negocio->max_horas_por_reserva) }}" required></div>
                            <div class="ayuda">Cuántas horas seguidas puede reservar un cliente en una sola cancha.</div>
                        </div>
                    @endif
                </div>
            </div>
            <div style="margin-top:6px"><button type="submit" class="btn btn-primary"><x-icon name="check" /> Guardar horario</button></div>
        </form>
    </section>

    {{-- ================= CANCHAS / PROFESIONALES ================= --}}
    <section class="cfg-panel" id="panel-recursos" role="tabpanel">
        <div class="md {{ $esSalon ? '' : 'con-visor' }}" data-md="recurso">
            <div class="md-lista">
                <button type="button" class="btn btn-primary btn-sm btn-nuevo" data-md-item="recurso-nuevo"><x-icon name="plus" size="16" /> Agregar {{ $esSalon ? 'profesional' : 'cancha' }}</button>
                @foreach ($negocio->canchas as $recurso)
                    <button type="button" class="md-item {{ $recurso->activa ? '' : 'inactivo' }}" data-md-item="recurso-{{ $recurso->id }}">
                        <span class="ico"><x-icon :name="$esSalon ? 'user' : $recurso->iconoDeporte()" size="16" /></span>
                        <span class="txt"><b>{{ $recurso->nombre }}</b>
                            <small>{{ $esSalon ? ($recurso->especialidad ?: 'Sin especialidad') : $recurso->resumen() }}</small></span>
                        <span class="punto" title="{{ $recurso->activa ? 'Activa' : 'Inactiva' }}"></span>
                    </button>
                @endforeach
            </div>

            <div class="md-detalle">
                @foreach ($negocio->canchas as $recurso)
                    <form method="POST" action="{{ route('configuracion.recursos.actualizar', $recurso) }}" class="tarjeta md-form" id="recurso-{{ $recurso->id }}" data-recurso enctype="multipart/form-data">
                        @csrf
                        <div class="recurso-head">
                            <span class="ico-caja"><x-icon :name="$esSalon ? 'user' : $recurso->iconoDeporte()" /></span>
                            <input type="text" name="nombre" value="{{ $recurso->nombre }}" required maxlength="50" aria-label="Nombre">
                        </div>
                        @if ($esSalon)
                            <div class="campo">
                                <label>Especialidad</label>
                                <div class="control"><x-icon name="sparkles" /><input type="text" name="especialidad" value="{{ $recurso->especialidad }}" placeholder="Ej: Uñas acrílicas y nail art" maxlength="80"></div>
                            </div>
                            <span class="label">Días en que atiende</span>
                            <div class="dias chicos" style="margin-bottom:14px">
                                @foreach (User::DIAS_SEMANA as $num => $dia)
                                    <label class="dia" style="margin:0"><input type="checkbox" name="dias_disponibles[]" value="{{ $num }}" @checked($recurso->disponibleEl($num))><span>{{ mb_substr($dia, 0, 2) }}</span></label>
                                @endforeach
                            </div>
                            @include('configuracion._horario-profesional', ['recurso' => $recurso, 'negocio' => $negocio])
                        @else
                            @include('configuracion._campos-cancha', ['cancha' => $recurso])
                        @endif
                        <div class="recurso-pie">
                            <label class="switch" style="margin-right:auto"><input type="checkbox" name="activa" value="1" @checked($recurso->activa)><span class="riel"></span> {{ $esSalon ? 'Disponible' : 'Activa' }}</label>
                            <button type="submit" form="eliminar-{{ $recurso->id }}" class="btn btn-danger btn-sm"><x-icon name="trash" size="16" /> Eliminar</button>
                            <button type="submit" class="btn btn-success btn-sm"><x-icon name="check" size="16" /> Guardar</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('configuracion.recursos.eliminar', $recurso) }}" id="eliminar-{{ $recurso->id }}" onsubmit="return confirm('¿Eliminar {{ $recurso->nombre }}? Sus reservas también se borrarán.');">@csrf</form>
                @endforeach

                <form method="POST" action="{{ route('configuracion.recursos.agregar') }}" class="tarjeta md-form es-nuevo" id="recurso-nuevo" data-recurso enctype="multipart/form-data">
                    @csrf
                    <div class="recurso-head">
                        <span class="ico-caja"><x-icon name="plus" /></span>
                        <input type="text" name="nombre" placeholder="{{ $esSalon ? 'Nombre de la profesional' : 'Nombre de la nueva cancha' }}" required maxlength="50" aria-label="Nombre">
                    </div>
                    @if ($esSalon)
                        <div class="campo"><label>Especialidad</label>
                            <div class="control"><x-icon name="sparkles" /><input type="text" name="especialidad" placeholder="Ej: Colorimetría" maxlength="80"></div></div>
                        <span class="label">Días en que atiende</span>
                        <div class="dias chicos" style="margin-bottom:14px">
                            @foreach (User::DIAS_SEMANA as $num => $dia)
                                <label class="dia" style="margin:0"><input type="checkbox" name="dias_disponibles[]" value="{{ $num }}" @checked(in_array($num, $diasNegocio))><span>{{ mb_substr($dia, 0, 2) }}</span></label>
                            @endforeach
                        </div>
                        @include('configuracion._horario-profesional', ['recurso' => null, 'negocio' => $negocio])
                    @else
                        @include('configuracion._campos-cancha', ['cancha' => null])
                    @endif
                    <input type="hidden" name="activa" value="1">
                    <div class="recurso-pie"><button type="submit" class="btn btn-primary btn-sm"><x-icon name="plus" size="16" /> Agregar {{ $esSalon ? 'profesional' : 'cancha' }}</button></div>
                </form>
            </div>

            @unless ($esSalon)
                <div class="md-visor">
                    <div class="visor" id="visorConfig">
                        <div class="visor-controles arriba">
                            <div class="dia-noche" id="vistaModo" hidden>
                                <button type="button" class="activo" data-vista="3d">Modelo 3D</button>
                                <button type="button" data-vista="foto">Foto real</button>
                            </div>
                        </div>
                        <div class="visor-controles">
                            <span class="visor-nombre" id="visorNombre">Vista previa</span>
                            <div class="dia-noche" id="diaNoche">
                                <button type="button" class="activo" data-noche="0">Día</button>
                                <button type="button" data-noche="1">Noche</button>
                            </div>
                        </div>
                    </div>
                    <div class="visor-ayuda">Arrastra para girar. La vista se actualiza al cambiar los datos de la cancha.</div>
                </div>
            @endunless
        </div>
    </section>

    {{-- ================= DEPORTES Y SUPERFICIES (canchas) ================= --}}
    @unless ($esSalon)
        <section class="cfg-panel" id="panel-categorias" role="tabpanel">
            <div class="cat-grid">
                @foreach ([['deporte', 'Deportes', 'pitch', 'Los deportes que se juegan en tus canchas.', $deportes], ['superficie', 'Superficies', 'layers', 'Los tipos de piso de tus canchas.', $superficies]] as [$tipo, $titulo, $icono, $descripcion, $lista])
                    <div class="tarjeta">
                        <div class="tarjeta-titulo"><span class="ico-caja"><x-icon :name="$icono" /></span><h2>{{ $titulo }} <small>{{ $descripcion }}</small></h2></div>
                        @foreach ($lista as $cat)
                            <form class="cat-fila" method="POST" action="{{ route('configuracion.categorias.actualizar', $cat) }}">
                                @csrf
                                <input type="text" name="nombre" value="{{ $cat->nombre }}" maxlength="40" required aria-label="Nombre">
                                <select name="base" aria-label="{{ $tipo === 'deporte' ? 'Cómo se dibuja en 3D' : 'Textura del piso' }}">
                                    @foreach ($tipo === 'deporte' ? CategoriaCancha::BASES_DEPORTE : CategoriaCancha::ESTILOS as $clave => $texto)
                                        <option value="{{ $clave }}" @selected($cat->base === $clave)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                                @if ($tipo === 'superficie')<input type="color" name="color" value="{{ $cat->color ?: '#2F8F3F' }}" aria-label="Color del piso" title="Color del piso">@endif
                                <span class="uso" title="Canchas que usan este {{ $tipo }}">{{ $cat->enUso() }}</span>
                                <button type="submit" class="btn btn-success btn-sm btn-icon" title="Guardar" aria-label="Guardar {{ $cat->nombre }}"><x-icon name="check" size="16" /></button>
                                <button type="submit" form="del-cat-{{ $cat->id }}" class="btn btn-danger btn-sm btn-icon" title="Eliminar" aria-label="Eliminar {{ $cat->nombre }}"><x-icon name="trash" size="16" /></button>
                            </form>
                            <form id="del-cat-{{ $cat->id }}" method="POST" action="{{ route('configuracion.categorias.eliminar', $cat) }}" onsubmit="return confirm('¿Eliminar {{ $cat->nombre }}?');">@csrf</form>
                        @endforeach
                        <form class="cat-fila nueva" method="POST" action="{{ route('configuracion.categorias.crear') }}">
                            @csrf
                            <input type="hidden" name="tipo" value="{{ $tipo }}">
                            <input type="text" name="nombre" placeholder="{{ $tipo === 'deporte' ? 'Nuevo deporte (Ej: Baloncesto)' : 'Nueva superficie (Ej: Parquet)' }}" maxlength="40" required aria-label="Nombre nuevo">
                            <select name="base" aria-label="{{ $tipo === 'deporte' ? 'Cómo se dibuja en 3D' : 'Textura del piso' }}">
                                @foreach ($tipo === 'deporte' ? CategoriaCancha::BASES_DEPORTE : CategoriaCancha::ESTILOS as $clave => $texto)
                                    <option value="{{ $clave }}" @selected($clave === ($tipo === 'deporte' ? 'generico' : 'dura'))>{{ $texto }}</option>
                                @endforeach
                            </select>
                            @if ($tipo === 'superficie')<input type="color" name="color" value="#B9803F" aria-label="Color del piso" title="Color del piso">@endif
                            <button type="submit" class="btn btn-primary btn-sm btn-icon" title="Agregar" aria-label="Agregar"><x-icon name="plus" size="16" /></button>
                        </form>
                        <p class="ayuda" style="margin-top:12px">{{ $tipo === 'deporte' ? 'El diseño define cómo se ve la cancha en 3D (líneas, red, arcos o paredes).' : 'El color y la textura definen el piso de la cancha en 3D.' }} El número es cuántas canchas lo usan.</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endunless

    {{-- ================= SERVICIOS (salón) ================= --}}
    @if ($esSalon)
        <section class="cfg-panel" id="panel-servicios" role="tabpanel">
            <div class="md" data-md="servicio">
                <div class="md-lista">
                    <button type="button" class="btn btn-primary btn-sm btn-nuevo" data-md-item="servicio-nuevo"><x-icon name="plus" size="16" /> Agregar servicio</button>
                    @foreach ($negocio->servicios as $servicio)
                        <button type="button" class="md-item {{ $servicio->activo ? '' : 'inactivo' }}" data-md-item="servicio-{{ $servicio->id }}">
                            <span class="ico"><x-icon :name="$servicio->icono()" size="16" /></span>
                            <span class="txt"><b>{{ $servicio->nombre }}</b>
                                <small class="num">{{ $servicio->duracion_minutos }} min{{ $servicio->precio ? ' · ' . $dinero($servicio->precio) : '' }}</small></span>
                            @if (! is_null($servicio->confirmacion_automatica))<span class="chip {{ $servicio->confirmacion_automatica ? 'verde' : 'ambar' }}" style="padding:2px 7px;font-size:10.5px" title="Regla de confirmación propia">{{ $servicio->confirmacion_automatica ? 'Auto' : 'Manual' }}</span>@endif
                        </button>
                    @endforeach
                </div>

                <div class="md-detalle">
                    @foreach ($negocio->servicios as $servicio)
                        <form method="POST" action="{{ route('configuracion.servicios.actualizar', $servicio) }}" class="tarjeta md-form" id="servicio-{{ $servicio->id }}">
                            @csrf
                            <div class="recurso-head">
                                <span class="ico-caja"><x-icon :name="$servicio->icono()" /></span>
                                <input type="text" name="nombre" value="{{ $servicio->nombre }}" required maxlength="50" aria-label="Nombre del servicio">
                            </div>
                            <div class="campo"><label>Descripción</label>
                                <div class="control"><x-icon name="tag" /><input type="text" name="descripcion" value="{{ $servicio->descripcion }}" placeholder="Opcional" maxlength="200"></div></div>
                            <div class="campo"><span class="label">Ícono del servicio</span>
                                <div class="iconos" role="radiogroup" aria-label="Ícono del servicio">
                                    <label class="ico-opcion auto" title="Automático: se elige según el nombre"><input type="radio" name="icono" value="" @checked(! $servicio->icono)><span><x-icon :name="\App\Models\Servicio::detectarIcono($servicio->nombre)" size="17" /> Auto</span></label>
                                    @foreach (\App\Models\Servicio::ICONOS as $clave => $nombreIcono)
                                        <label class="ico-opcion" title="{{ $nombreIcono }}"><input type="radio" name="icono" value="{{ $clave }}" @checked($servicio->icono === $clave)><span><x-icon :name="$clave" size="20" /></span></label>
                                    @endforeach
                                </div></div>
                            <div class="grid-3">
                                <div class="campo"><label>Duración (min)</label>
                                    <div class="control"><x-icon name="timer" /><input type="number" name="duracion_minutos" value="{{ $servicio->duracion_minutos }}" min="5" max="480" required></div></div>
                                <div class="campo"><label>Precio</label>
                                    <div class="control"><x-icon name="dollar" /><input type="number" name="precio" value="{{ $servicio->precio ? (int) $servicio->precio : '' }}" min="0" step="1000"></div></div>
                                <div class="campo"><label>Confirmación</label>
                                    <div class="control"><x-icon name="check-circle" />
                                        <select name="confirmacion">
                                            <option value="heredar" @selected(is_null($servicio->confirmacion_automatica))>Según general</option>
                                            <option value="si" @selected($servicio->confirmacion_automatica === true)>Automática</option>
                                            <option value="no" @selected($servicio->confirmacion_automatica === false)>Manual</option>
                                        </select>
                                    </div></div>
                            </div>
                            <div class="campo"><label>Margen después del servicio (min)</label>
                                <div class="control"><x-icon name="timer" /><input type="number" name="margen_minutos" min="0" max="120" step="5" value="{{ $servicio->margen_minutos }}" placeholder="Según el negocio ({{ $negocio->margen_minutos ?? 0 }})"></div>
                                <div class="ayuda">Minutos libres tras este servicio para limpiar o preparar. Vacío = el margen general del negocio.</div></div>
                            <div class="switches">
                                <label class="switch"><input type="checkbox" name="permite_diseno" value="1" @checked($servicio->permite_diseno)><span class="riel"></span> Pedir foto del diseño (uñas)</label>
                            </div>
                            <div class="recurso-pie">
                                <label class="switch" style="margin-right:auto"><input type="checkbox" name="activo" value="1" @checked($servicio->activo)><span class="riel"></span> Activo</label>
                                <button type="submit" form="eliminar-servicio-{{ $servicio->id }}" class="btn btn-danger btn-sm"><x-icon name="trash" size="16" /> Eliminar</button>
                                <button type="submit" class="btn btn-success btn-sm"><x-icon name="check" size="16" /> Guardar</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('configuracion.servicios.eliminar', $servicio) }}" id="eliminar-servicio-{{ $servicio->id }}" onsubmit="return confirm('¿Eliminar el servicio {{ $servicio->nombre }}?');">@csrf</form>
                    @endforeach

                    <form method="POST" action="{{ route('configuracion.servicios.agregar') }}" class="tarjeta md-form es-nuevo" id="servicio-nuevo">
                        @csrf
                        <div class="recurso-head">
                            <span class="ico-caja"><x-icon name="plus" /></span>
                            <input type="text" name="nombre" placeholder="Nombre del nuevo servicio" required maxlength="50" aria-label="Nombre del servicio">
                        </div>
                        <div class="campo"><label>Descripción</label>
                            <div class="control"><x-icon name="tag" /><input type="text" name="descripcion" placeholder="Opcional" maxlength="200"></div></div>
                        <div class="campo"><span class="label">Ícono del servicio</span>
                            <div class="iconos" role="radiogroup" aria-label="Ícono del servicio">
                                <label class="ico-opcion auto" title="Automático: se elige según el nombre"><input type="radio" name="icono" value="" @checked(true)><span><x-icon :name="'sparkles'" size="17" /> Auto</span></label>
                                @foreach (\App\Models\Servicio::ICONOS as $clave => $nombreIcono)
                                    <label class="ico-opcion" title="{{ $nombreIcono }}"><input type="radio" name="icono" value="{{ $clave }}" @checked(false)><span><x-icon :name="$clave" size="20" /></span></label>
                                @endforeach
                            </div></div>
                        <div class="grid-3">
                            <div class="campo"><label>Duración (min)</label>
                                <div class="control"><x-icon name="timer" /><input type="number" name="duracion_minutos" min="5" max="480" placeholder="45" required></div></div>
                            <div class="campo"><label>Precio</label>
                                <div class="control"><x-icon name="dollar" /><input type="number" name="precio" min="0" step="1000" placeholder="Opcional"></div></div>
                            <div class="campo"><label>Confirmación</label>
                                <div class="control"><x-icon name="check-circle" />
                                    <select name="confirmacion"><option value="heredar">Según general</option><option value="si">Automática</option><option value="no">Manual</option></select>
                                </div></div>
                        </div>
                        <div class="campo"><label>Margen después del servicio (min)</label>
                            <div class="control"><x-icon name="timer" /><input type="number" name="margen_minutos" min="0" max="120" step="5" placeholder="Según el negocio ({{ $negocio->margen_minutos ?? 0 }})"></div>
                            <div class="ayuda">Minutos libres tras este servicio para limpiar o preparar. Vacío = el margen general del negocio.</div></div>
                        <div class="switches">
                            <label class="switch"><input type="checkbox" name="permite_diseno" value="1"><span class="riel"></span> Pedir foto del diseño (uñas)</label>
                        </div>
                        <input type="hidden" name="activo" value="1">
                        <div class="recurso-pie"><button type="submit" class="btn btn-primary btn-sm"><x-icon name="plus" size="16" /> Agregar servicio</button></div>
                    </form>
                </div>
            </div>
        </section>
    @endif

    {{-- ================= GENERAL ================= --}}
    <section class="cfg-panel" id="panel-general" role="tabpanel">
        <div class="general-grid">
            <form method="POST" action="{{ route('configuracion.general') }}" class="tarjeta general-form" id="formGeneral">
                @csrf
                <section>
                    <h3 class="titulo-mini"><x-icon name="check-circle" size="17" /> Confirmación de citas</h3>
                    <div class="opcion-switch">
                        <label class="switch" style="margin:0">
                            <input type="checkbox" name="confirmacion_automatica" value="1" @checked(old('confirmacion_automatica', $negocio->confirmacion_automatica))>
                            <span class="riel"></span>
                        </label>
                        <div>
                            <strong>Confirmar reservas automáticamente</strong>
                            <p>Activado: la reserva queda confirmada al instante. Desactivado: queda pendiente hasta que tú la confirmes en el panel.@if ($esSalon) Cada servicio puede tener su propia regla en la pestaña <b>Servicios</b>.@endif</p>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="titulo-mini"><x-icon name="mail" size="17" /> Correo de confirmación</h3>
                    <div class="opcion-switch">
                        <label class="switch" style="margin:0">
                            <input type="checkbox" name="email_confirmacion_activo" id="emailActivo" value="1" @checked(old('email_confirmacion_activo', $negocio->email_confirmacion_activo))>
                            <span class="riel"></span>
                        </label>
                        <div>
                            <strong>Enviar correo cuando la cita se confirme</strong>
                            <p>Se envía al correo que el cliente deje al reservar (es opcional para él), con tu logo, tus colores y los datos de la cita.</p>
                        </div>
                    </div>
                    <div class="campo">
                        <label for="email_asunto">Asunto</label>
                        <div class="control"><x-icon name="mail" /><input type="text" id="email_asunto" name="email_asunto" value="{{ old('email_asunto', $negocio->email_asunto) }}" placeholder="{{ User::EMAIL_ASUNTO }}" maxlength="150"></div>
                    </div>
                    <div class="campo">
                        <label for="email_mensaje">Mensaje</label>
                        <textarea id="email_mensaje" name="email_mensaje" rows="2" maxlength="500" placeholder="{{ User::EMAIL_MENSAJE }}">{{ old('email_mensaje', $negocio->email_mensaje) }}</textarea>
                        <div class="marcadores">
                            <span class="ayuda" style="margin:0">Insertar:</span>
                            @foreach (['{cliente}', '{negocio}', '{servicio}', '{fecha}', '{hora}'] as $m)<button type="button" data-marcador="{{ $m }}">{{ $m }}</button>@endforeach
                        </div>
                    </div>
                    <div class="campo">
                        <label for="email_pie">Nota al pie</label>
                        <div class="control"><x-icon name="tag" /><input type="text" id="email_pie" name="email_pie" value="{{ old('email_pie', $negocio->email_pie) }}" placeholder="{{ User::EMAIL_PIE }}" maxlength="300"></div>
                    </div>
                    <div class="nota"><x-icon name="alert-circle" size="16" /> <span>El envío usa el servidor de correo de la plataforma. Muy pronto podrás conectar tu propio proveedor de correo (API). Deja un campo vacío para usar el texto estándar.</span></div>
                </section>

                <button type="submit" class="btn btn-primary" style="margin-top:16px"><x-icon name="check" /> Guardar configuración</button>
            </form>

            <div class="correo-previa">
                <h3 class="titulo-mini"><x-icon name="eye" size="17" /> Vista previa del correo</h3>
                <div class="asunto">Asunto: <b id="prevAsunto"></b></div>
                <div class="correo-marco" id="correoMarco">
                    @include('emails._cuerpo', $vistaCorreo)
                </div>
            </div>
        </div>
    </section>
    @include('partials.qr-modal', ['negocio' => $negocio])
@endsection

@push('scripts')
@unless ($esSalon)
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="{{ asset('js/cancha3d.js') }}"></script>
@endunless
<script>
(() => {
    const ejemplo = @json($datosEjemplo);
    const porDefecto = @json($textosPorDefecto);

    // ---------- Pestañas ----------
    const tabs = [...document.querySelectorAll('.cfg-tab')];
    const paneles = [...document.querySelectorAll('.cfg-panel')];
    let visor = null, mostrarVisor = () => {};

    function abrirTab(clave) {
        if (!tabs.some(t => t.dataset.tab === clave)) clave = 'marca';
        tabs.forEach(t => { const on = t.dataset.tab === clave; t.classList.toggle('activo', on); t.setAttribute('aria-selected', on); });
        paneles.forEach(p => p.classList.toggle('activo', p.id === 'panel-' + clave));
        try { sessionStorage.setItem('cfgTab', clave); } catch (e) {}
        if (clave === 'recursos') setTimeout(mostrarVisor, 30);   // el visor 3D necesita su caja visible
    }
    tabs.forEach(t => t.addEventListener('click', () => {
        history.replaceState(null, '', '#' + t.dataset.tab);
        abrirTab(t.dataset.tab);
    }));

    // ---------- Lista + detalle ----------
    function seleccionar(grupo, id) {
        const cont = document.querySelector(`[data-md="${grupo}"]`);
        if (!cont) return;
        cont.querySelectorAll('[data-md-item]').forEach(b => b.classList.toggle('activo', b.dataset.mdItem === id));
        cont.querySelectorAll('.md-form').forEach(f => f.classList.toggle('activo', f.id === id));
        if (grupo === 'recurso') mostrarVisor();
    }
    document.querySelectorAll('[data-md-item]').forEach(b => b.addEventListener('click', () => {
        seleccionar(b.closest('[data-md]').dataset.md, b.dataset.mdItem);
    }));
    ['recurso', 'servicio'].forEach(g => {
        const primero = document.querySelector(`[data-md="${g}"] .md-item`);
        seleccionar(g, primero ? primero.dataset.mdItem : g + '-nuevo');
    });

    // El hash puede venir de un guardado: #recurso-5, #servicio-3, #general...
    function desdeHash() {
        const h = location.hash.slice(1);
        let tab = h;
        if (h.startsWith('recurso-')) { tab = 'recursos'; seleccionar('recurso', h); }
        else if (h.startsWith('servicio-')) { tab = 'servicios'; seleccionar('servicio', h); }
        else if (!h) { try { tab = sessionStorage.getItem('cfgTab'); } catch (e) {} }
        abrirTab(tab || 'marca');
        scrollTo({ top: 0 });   // el ancla (#recurso-14) no debe empujar la página bajo la barra superior
    }

    @include('configuracion._marca-js')

    // ---------- Horario: dibuja las sillas ----------
    const puestos = document.getElementById('numero_puestos');
    if (puestos) {
        const icono = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
        const dibujar = () => { document.getElementById('puestosVisual').innerHTML = `<span>${icono}</span>`.repeat(Math.min(Math.max(+puestos.value || 0, 0), 20)); };
        puestos.addEventListener('input', dibujar);
        dibujar();
    }

    // ---------- Canchas: formatos según el deporte, foto real y visor 3D ----------
    const FORMATOS = @json(CategoriaCancha::FORMATOS);

    /** El formato de juego depende del deporte (fútbol 5/7/11, individual/dobles...); sin formatos, el campo se oculta. */
    function poblarFormatos(form) {
        const dep = form.querySelector('[data-3d="deporte"]');
        if (!dep) return;
        const sel = form.querySelector('[data-3d="formato"]');
        const formatos = FORMATOS[dep.selectedOptions[0]?.dataset.base] || {};
        const previo = sel.value || sel.dataset.valor;
        sel.innerHTML = Object.entries(formatos).map(([k, n]) => `<option value="${k}">${n}</option>`).join('');
        if (formatos[previo]) sel.value = previo;
        sel.closest('[data-campo-formato]').hidden = !Object.keys(formatos).length;
    }
    document.querySelectorAll('[data-recurso]').forEach(poblarFormatos);

    const contenedor = document.getElementById('visorConfig');
    if (contenedor && window.Cancha3D) {
        const datosDe = form => {
            const v = n => form.querySelector(`[data-3d="${n}"]`);
            const dep = v('deporte').selectedOptions[0], sup = v('superficie').selectedOptions[0];
            const quitar = form.querySelector('[data-quitar-foto]')?.checked;
            return {
                deporte: dep?.dataset.base || 'futbol', formato: v('formato').value || null,
                estilo: sup?.dataset.estilo || 'cesped', color: sup?.dataset.color || '#2F8F3F',
                iluminacion: v('iluminacion').checked, techada: v('techada').checked,
                foto: quitar ? null : (form.dataset.fotoLocal || form.querySelector('[data-foto]')?.dataset.foto || null),
                fotoModo: v('fotoModo').value,
            };
        };
        const vistaModo = document.getElementById('vistaModo');
        const elegirVista = foto => {
            vistaModo.querySelectorAll('button').forEach(x => x.classList.toggle('activo', (x.dataset.vista === 'foto') === foto));
            visor?.setVistaFoto(foto);
        };
        mostrarVisor = () => {
            const form = document.querySelector('[data-md="recurso"] .md-form.activo');
            if (!form || !contenedor.clientWidth) return;
            const datos = datosDe(form);
            visor ? visor.actualizar(datos) : (visor = Cancha3D.crear(contenedor, datos));
            document.getElementById('visorNombre').textContent = form.querySelector('[name="nombre"]').value || 'Nueva cancha';
            vistaModo.hidden = !datos.foto;
            if (!datos.foto) elegirVista(false);
        };
        document.querySelectorAll('[data-recurso]').forEach(form => {
            form.addEventListener('change', e => {
                if (e.target.matches('[data-3d="deporte"]')) poblarFormatos(form);
                if (e.target.dataset['3d'] || e.target.matches('[data-quitar-foto]')) mostrarVisor();
            });
            form.querySelector('[name="nombre"]').addEventListener('input', e => {
                if (form.classList.contains('activo')) document.getElementById('visorNombre').textContent = e.target.value;
            });
            // Al elegir una foto se ve al instante en la vista previa y en el modelo 3D (aún sin guardar)
            form.querySelector('[data-foto-input]')?.addEventListener('change', e => {
                const archivo = e.target.files[0];
                if (!archivo) return;
                if (form.dataset.fotoLocal) URL.revokeObjectURL(form.dataset.fotoLocal);
                form.dataset.fotoLocal = URL.createObjectURL(archivo);
                const prev = form.querySelector('[data-foto-prev]');
                prev.style.backgroundImage = `url("${form.dataset.fotoLocal}")`; prev.replaceChildren();
                form.querySelector('[data-foto-texto]').textContent = 'Cambiar foto';
                const quitar = form.querySelector('[data-quitar-foto]'); if (quitar) quitar.checked = false;
                mostrarVisor();
            });
        });
        document.querySelectorAll('#diaNoche button').forEach(b => b.addEventListener('click', () => {
            document.querySelectorAll('#diaNoche button').forEach(x => x.classList.toggle('activo', x === b));
            visor?.setNoche(b.dataset.noche === '1');
        }));
        vistaModo.querySelectorAll('button').forEach(b => b.addEventListener('click', () => elegirVista(b.dataset.vista === 'foto')));
    }


    const campoAsunto = document.getElementById('email_asunto');
    const campoMensaje = document.getElementById('email_mensaje');
    const campoPie = document.getElementById('email_pie');
    const rellenar = t => t.replace(/\{(\w+)\}/g, (m, k) => ejemplo[k] ?? m);
    function pintarCorreo() {
        document.getElementById('prevAsunto').textContent = rellenar(campoAsunto.value.trim() || porDefecto.asunto);
        document.querySelector('[data-prev="mensaje"]').textContent = rellenar(campoMensaje.value.trim() || porDefecto.mensaje);
        document.querySelector('[data-prev="pie"]').textContent = rellenar(campoPie.value.trim() || porDefecto.pie);
        document.getElementById('correoMarco').classList.toggle('apagado', !document.getElementById('emailActivo').checked);
    }
    [campoAsunto, campoMensaje, campoPie, document.getElementById('emailActivo')].forEach(c => c.addEventListener('input', pintarCorreo));
    document.getElementById('emailActivo').addEventListener('change', pintarCorreo);
    document.querySelectorAll('[data-marcador]').forEach(b => b.addEventListener('click', () => {
        const t = campoMensaje, ini = t.selectionStart ?? t.value.length, fin = t.selectionEnd ?? t.value.length;
        t.value = t.value.slice(0, ini) + b.dataset.marcador + t.value.slice(fin);
        t.focus(); t.selectionStart = t.selectionEnd = ini + b.dataset.marcador.length;
        pintarCorreo();
    }));
    pintarCorreo();

    window.addEventListener('hashchange', desdeHash);
    desdeHash();
})();
</script>
@endpush
