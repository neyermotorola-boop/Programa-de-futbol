@extends('layouts.app')

@use('App\Models\Cancha')
@use('App\Models\User')

@php
    $esSalon = $negocio->tipo_negocio === 'salon';
    $formatoHora = fn ($h) => \Carbon\Carbon::createFromFormat('H:i', substr($h, 0, 5))->format('g:i A');
    $dias = $negocio->diasAtencion();
    $textoDias = count($dias) === 7 ? 'Todos los días' : collect($dias)->map(fn ($d) => mb_substr(User::DIAS_SEMANA[$d], 0, 3))->implode(' · ');
    $dinero = fn ($v) => '$' . number_format($v, 0, ',', '.');
@endphp

@section('title', $negocio->name)

@section('header')
<style>:root { {!! $negocio->variablesMarca() !!} }</style>
<header class="topbar">
    <div class="topbar-inner">
        <span class="marca">
            @include('partials.logo-negocio', ['negocio' => $negocio])
            <span class="marca-texto">
                <span class="marca-nombre">{{ $negocio->name }}</span>
                <span class="marca-tag">{{ $negocio->eslogan ?: 'Reservas en línea' }}</span>
            </span>
        </span>
        <div class="topbar-acciones">
            @include('partials.tema')
            @auth
                <a href="{{ route('reservas.admin') }}" class="btn btn-secondary btn-sm"><x-icon name="dashboard" size="16" /> <span class="ocultar-movil">Mi panel</span></a>
            @else
                <a href="{{ route('login') }}" class="btn btn-secondary btn-sm"><x-icon name="log-in" size="16" /> <span class="ocultar-movil">Iniciar sesión</span></a>
            @endauth
        </div>
    </div>
</header>
@endsection

@push('styles')
<style>
    .contenedor { padding-top: 18px; padding-bottom: 32px; }
    .hero { display: flex; justify-content: space-between; align-items: flex-end; gap: 10px 20px; flex-wrap: wrap; margin-bottom: 16px; }
    .hero h1 { font-size: clamp(22px, 2.6vw, 30px); }
    .hero .subtitulo { font-size: 14px; margin-top: 2px; }
    .hero .meta { display: flex; gap: 6px; flex-wrap: wrap; }

    /* Tres columnas para que la reserva quepa en una sola pantalla */
    .reserva-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr) minmax(0, 1fr); gap: 14px; align-items: start; }
    .reserva-grid .pila { gap: 14px; }
    .reserva-grid .tarjeta { padding: 16px 18px; }
    .reserva-grid .tarjeta-titulo { margin-bottom: 12px; }
    .reserva-grid .tarjeta-titulo h2 { font-size: 16px; }
    .reserva-grid .campo { margin-bottom: 12px; }
    .reserva-grid input[type], .reserva-grid select { min-height: 42px; padding-top: 9px; padding-bottom: 9px; }
    .reserva-grid label, .reserva-grid .label { margin-bottom: 6px; }

    .paso { display: inline-grid; place-items: center; width: 26px; height: 26px; border-radius: 8px; background: var(--floodlight); color: var(--texto-acento); font-size: 13px; font-weight: 800; font-family: var(--fuente-titulo); flex: none; }

    .opciones { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 200px), 1fr)); gap: 7px; }
    .opciones.una-columna { grid-template-columns: 1fr; }
    .opcion {
        display: flex; align-items: center; gap: 10px; text-align: left; width: 100%;
        border: 1px solid var(--borde-fuerte); background: var(--superficie-alta); color: var(--linea);
        padding: 9px 12px; border-radius: 11px; cursor: pointer; font-family: var(--fuente); font-size: 14px; font-weight: 600;
        transition: border-color 0.15s, background 0.15s, box-shadow 0.15s, transform 0.1s;
    }
    .opcion:hover { border-color: color-mix(in srgb, var(--linea) 30%, transparent); }
    .opcion:active { transform: scale(0.98); }
    .opcion > .icon { color: var(--muted); }
    .opcion .cuerpo { min-width: 0; flex: 1; }
    .opcion .sub { display: block; color: var(--muted); font-weight: 500; font-size: 12px; margin-top: 2px; }
    .opcion .detalle { margin-left: auto; color: var(--muted); font-weight: 500; font-size: 13px; white-space: nowrap; font-variant-numeric: tabular-nums; text-align: right; }
    .opcion .detalle b { display: block; color: var(--acento-vis); font-weight: 700; }
    .opcion.activa { border-color: var(--acento-vis); background: color-mix(in srgb, var(--floodlight) 10%, transparent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--floodlight) 14%, transparent); }
    .opcion.activa > .icon { color: var(--acento-vis); }
    .opcion .ico-servicio { width: 42px; height: 42px; border-radius: 13px; display: grid; place-items: center; flex: none; margin: -3px 0;
        background: color-mix(in srgb, var(--primario-vis) 13%, transparent); color: var(--primario-vis); transition: background 0.2s, color 0.2s, transform 0.25s cubic-bezier(0.3, 1.4, 0.5, 1); }
    .opcion:hover .ico-servicio { transform: scale(1.08) rotate(-4deg); }
    .opcion.activa .ico-servicio { background: var(--floodlight); color: var(--texto-acento); transform: scale(1.05); }
    .avatar { width: 32px; height: 32px; font-size: 13px; border-radius: 50%; display: grid; place-items: center; flex: none; font-weight: 800; font-family: var(--fuente-titulo); background: var(--turf-claro); color: var(--texto-primario); }
    .avatar.todos { background: var(--superficie-hover); color: var(--acento-vis); }

    /* Visor 3D */
    .visor { position: relative; aspect-ratio: 16 / 9; border-radius: 12px; overflow: hidden; border: 1px solid var(--borde-fuerte); background: #0b1610; margin-bottom: 10px; }
    .visor canvas { width: 100% !important; height: 100% !important; display: block; cursor: grab; }
    .visor canvas:active { cursor: grabbing; }
    .visor-capa { position: absolute; left: 12px; right: 12px; display: flex; justify-content: space-between; align-items: center; gap: 8px; pointer-events: none; }
    .visor-capa > * { pointer-events: auto; }
    .visor-capa.arriba { top: 12px; }
    .visor-capa.abajo { bottom: 12px; }
    .visor-capa.medio { top: 54px; justify-content: flex-end; }
    .chips-deporte { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
    .chips-deporte button { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; border-radius: 999px; border: 1px solid transparent; background: var(--superficie-alta); color: var(--texto-suave); font-family: var(--fuente); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
    .chips-deporte button:hover { color: var(--linea); }
    .chips-deporte button.activo { color: var(--acento-vis); border-color: var(--floodlight); background: color-mix(in srgb, var(--floodlight) 10%, transparent); }
    .burbuja { background: color-mix(in srgb, var(--fondo) 80%, transparent); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .burbuja.aviso { color: var(--alerta-error-txt); }
    .dia-noche { display: inline-flex; background: color-mix(in srgb, var(--fondo) 80%, transparent); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-radius: 999px; padding: 3px; border: 1px solid var(--borde-fuerte); }
    .dia-noche button { border: 0; background: none; color: var(--muted); padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: var(--fuente); }
    .dia-noche button.activo { background: var(--floodlight); color: var(--texto-acento); }
    .ficha { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
    .visor-vacio { position: absolute; inset: 0; display: grid; place-items: center; text-align: center; color: var(--muted); font-size: 14px; padding: 20px; }

    .rejilla-horas { display: grid; grid-template-columns: repeat(auto-fill, minmax(72px, 1fr)); gap: 6px; }
    .hora-tile {
        position: relative; text-align: center; padding: 7px 2px; border-radius: 9px; line-height: 1.2;
        border: 1px solid var(--borde-fuerte); background: var(--superficie-alta); color: var(--linea);
        cursor: pointer; font-family: var(--fuente); overflow: hidden; transition: border-color 0.15s, transform 0.1s, background 0.15s;
    }
    .hora-tile:not(.ocupada):hover { border-color: var(--acento-vis); }
    .hora-tile:not(.ocupada):active { transform: scale(0.96); }
    .hora-tile .numero { display: block; font-weight: 700; font-size: 14px; font-variant-numeric: tabular-nums; }
    .hora-tile .meridiano { display: block; font-size: 10px; font-weight: 700; letter-spacing: 1.2px; color: var(--acento-vis); }
    .hora-tile.noche .meridiano { color: var(--noche-txt); }
    .hora-tile.seleccionada { background: var(--floodlight); border-color: var(--acento-vis); color: var(--texto-acento); box-shadow: 0 6px 18px color-mix(in srgb, var(--floodlight) 35%, transparent); }
    .hora-tile.seleccionada .meridiano { color: var(--texto-acento); }
    .hora-tile.ocupada { cursor: not-allowed; opacity: 0.4; text-decoration: line-through; }
    .hora-tile.ocupada .meridiano { color: var(--muted); }
    .estado-horas { color: var(--muted); font-size: 13px; padding: 16px 12px; text-align: center; border: 1px dashed var(--borde-fuerte); border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 8px; }

    .resumen { display: grid; gap: 6px; margin: 14px 0 12px; padding: 10px 14px; border-radius: 12px; background: color-mix(in srgb, var(--fondo) 60%, transparent); border: 1px solid var(--borde); font-size: 14px; }
    .resumen div { display: flex; align-items: center; gap: 10px; color: var(--texto-suave); }
    .resumen .icon { color: var(--muted); }
    .resumen strong { color: var(--linea); font-weight: 600; margin-left: auto; text-align: right; }
    .resumen .total { padding-top: 8px; border-top: 1px solid var(--borde); }
    .resumen .total strong { color: var(--acento-vis); font-size: 16px; }

    .contacto { display: flex; justify-content: center; gap: 10px 22px; flex-wrap: wrap; margin-top: 18px; color: var(--muted); font-size: 13px; }
    .contacto span { display: inline-flex; align-items: center; gap: 6px; }

    /* Lista de servicios larga: se desplaza por dentro para que la página siga en una pantalla */
    .lista-scroll { max-height: calc(100vh - 290px); min-height: 240px; overflow-y: auto; padding-right: 4px; }

    /* Diseño de uñas */
    #tarjetaDiseno .tarjeta-titulo { margin-bottom: 10px; }
    #tarjetaDiseno .tarjeta-titulo .ico-caja { width: 30px; height: 30px; border-radius: 9px; font-size: 0; }
    .diseno-cols { display: grid; grid-template-columns: 138px minmax(0, 1fr); gap: 14px; align-items: start; }
    .diseno-lienzo {
        position: relative; height: 192px; border-radius: 16px; overflow: hidden; cursor: pointer;
        background: radial-gradient(ellipse at 50% 30%, color-mix(in srgb, var(--turf) 22%, var(--superficie-alta)), var(--superficie-alta));
        transition: box-shadow 0.15s;
    }
    .diseno-lienzo:hover { box-shadow: 0 0 0 3px color-mix(in srgb, var(--floodlight) 22%, transparent); }
    .diseno-lienzo.con-foto { cursor: grab; }
    .diseno-lienzo.arrastrando { cursor: grabbing; }
    .diseno-ctrl { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
    .diseno-botones { display: flex; gap: 8px; flex-wrap: wrap; }
    .ctrl-grupo .label { font-size: 11.5px; margin-bottom: 5px; }
    .formas { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px; }
    .formas button {
        display: flex; align-items: center; gap: 7px; padding: 6px 8px; border-radius: 10px; min-width: 0;
        background: var(--superficie-alta); border: 1px solid transparent; color: var(--muted); cursor: pointer; font-family: var(--fuente);
        font-size: 11.5px; font-weight: 600; transition: color 0.15s, background 0.15s, border-color 0.15s;
    }
    .formas button:hover { color: var(--linea); }
    .formas button[aria-pressed="true"] { color: var(--acento-vis); border-color: var(--floodlight); background: color-mix(in srgb, var(--floodlight) 10%, transparent); }
    .pieles { display: flex; gap: 8px; }
    .pieles button { width: 26px; height: 26px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; padding: 0; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.15); transition: transform 0.12s; }
    .pieles button:hover { transform: scale(1.1); }
    .pieles button[aria-pressed="true"] { border-color: var(--floodlight); box-shadow: 0 0 0 2px var(--superficie); }
    .diseno input[type=range] { width: 100%; min-height: 0; padding: 0; accent-color: var(--floodlight); }
    .btn-suave { background: color-mix(in srgb, var(--linea) 8%, transparent); color: var(--muted); border: 0; }
    .btn-suave:hover { color: var(--error); background: color-mix(in srgb, var(--error) 14%, transparent); }

    @media (max-width: 1180px) {
        .reserva-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .reserva-grid .col-c { grid-column: 1 / -1; }
    }
    @media (max-width: 720px) {
        .reserva-grid { grid-template-columns: 1fr; }
        .ocultar-movil { display: none; }
        .rejilla-horas { grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 6px; }
        .hora-tile { padding: 9px 2px; }
        .hora-tile .numero { font-size: 15px; }
        .hero { align-items: flex-start; }
    }
</style>
@endpush

@section('content')
    <div class="hero">
        <div>
            <h1>{{ $esSalon ? 'Reserva tu cita' : 'Reserva tu cancha' }}</h1>
            <div class="subtitulo">{{ $negocio->eslogan ?: ($esSalon ? 'Elige el servicio, tu profesional favorita y la hora.' : 'Mira la cancha en 3D, elige la fecha y la hora.') }}</div>
        </div>
        <div class="meta">
            <span class="chip"><x-icon name="calendar" size="13" /> {{ $textoDias }}</span>
            <span class="chip"><x-icon name="clock" size="13" /> <span class="num">{{ $formatoHora($horaApertura) }} – {{ $formatoHora($horaCierre) }}</span></span>
            <span class="chip verde"><x-icon :name="$esSalon ? 'users' : 'pitch'" size="13" /> {{ $canchas->count() }} {{ $esSalon ? ($canchas->count() === 1 ? 'profesional' : 'profesionales') : ($canchas->count() === 1 ? 'cancha' : 'canchas') }}</span>
        </div>
    </div>

    {{-- Tras reservar: pop-up de agradecimiento con el radicado (reemplaza al aviso verde) --}}
    @if (session('reserva_ok'))
        @include('reservas._exito', ['ok' => session('reserva_ok')])
    @elseif (session('exito'))
        <div class="alerta alerta-exito" role="status"><x-icon name="check-circle" /> <div>{{ session('exito') }}</div></div>
    @endif

    @if ($errors->any())
        <div class="alerta alerta-error" role="alert">
            <x-icon name="alert-circle" />
            <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        </div>
    @endif

    @if ($canchas->isEmpty() || ($esSalon && $servicios->isEmpty()))
        <div class="vacio">
            <div class="ico-caja"><x-icon name="calendar" size="26" /></div>
            <strong>Reservas no disponibles por ahora</strong>
            Este negocio todavía está configurando su agenda. Vuelve pronto.
        </div>
    @else
        <form method="POST" action="{{ route('reservas.store', $negocio->slug) }}" id="formReserva" class="reserva-grid" enctype="multipart/form-data">
            @csrf

            <div class="pila col-a">
                @if ($esSalon)
                    <section class="tarjeta">
                        <div class="tarjeta-titulo"><span class="paso">1</span><h2>Servicios <small>Puedes elegir más de uno</small></h2></div>
                        @php $elegidos = collect(old('servicio_ids', []))->map(fn ($v) => (string) $v)->all(); @endphp
                        <div class="opciones una-columna lista-scroll" id="selectorServicios">
                            @foreach ($servicios as $s)
                                <button type="button" class="opcion {{ in_array((string) $s->id, $elegidos, true) ? 'activa' : '' }}" data-grupo="servicio" aria-pressed="{{ in_array((string) $s->id, $elegidos, true) ? 'true' : 'false' }}" data-id="{{ $s->id }}" data-nombre="{{ $s->nombre }}" data-precio="{{ (int) $s->precio }}" data-minutos="{{ $s->duracion_minutos }}" data-diseno="{{ $s->permite_diseno ? 1 : 0 }}">
                                    <span class="ico-servicio"><x-icon :name="$s->icono()" size="20" /></span>
                                    <span class="cuerpo">{{ $s->nombre }} @if ($s->descripcion)<span class="sub">{{ $s->descripcion }}</span>@endif</span>
                                    <span class="detalle">@if ($s->precio)<b>{{ $dinero($s->precio) }}</b>@endif {{ $s->duracion_minutos }} min</span>
                                </button>
                            @endforeach
                        </div>
                        {{-- Un campo oculto por servicio elegido (los llena el script) --}}
                        <div id="servicioInputs"></div>
                    </section>
                </div>

                <div class="pila col-b">

                    <section class="tarjeta">
                        <div class="tarjeta-titulo"><span class="paso">2</span><h2>Profesional</h2></div>
                        <div class="opciones una-columna">
                            <button type="button" class="opcion {{ (old('profesional_id', 'cualquiera') === 'cualquiera') ? 'activa' : '' }}" data-grupo="profesional" data-id="cualquiera" data-nombre="Cualquiera disponible">
                                <span class="avatar todos"><x-icon name="users" size="18" /></span>
                                <span class="cuerpo">Cualquiera disponible <span class="sub">Te asignamos quien esté libre</span></span>
                            </button>
                            @foreach ($canchas as $c)
                                <button type="button" class="opcion {{ ((string) old('profesional_id') === (string) $c->id) ? 'activa' : '' }}" data-grupo="profesional" data-id="{{ $c->id }}" data-nombre="{{ $c->nombre }}">
                                    <span class="avatar">{{ mb_strtoupper(mb_substr($c->nombre, 0, 1)) }}</span>
                                    <span class="cuerpo">{{ $c->nombre }}
                                        <span class="sub">{{ $c->especialidad ?: 'Profesional' }}@if (! empty($c->dias_disponibles) && count($c->dias_disponibles) < 7) · {{ collect($c->dias_disponibles)->map(fn ($d) => mb_substr(User::DIAS_SEMANA[$d], 0, 2))->implode(' ') }}@endif
                                        @if ($c->textoHorarioPropio()) · {{ $c->textoHorarioPropio() }}@endif</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="profesional_id" id="profesional_id" value="{{ old('profesional_id', 'cualquiera') }}">
                    </section>

                    <section class="tarjeta" id="tarjetaDiseno" hidden>
                        <div class="tarjeta-titulo"><span class="ico-caja"><x-icon name="sparkles" size="16" /></span><h2>Tu diseño de uñas <small>Sube una foto y mira cómo quedaría</small></h2></div>
                        <div class="diseno" id="diseno">
                            <input type="file" name="diseno" accept="image/png,image/jpeg,image/webp" data-archivo hidden>
                            <input type="hidden" name="diseno_forma" value="almendra" data-forma-valor>
                            <div class="diseno-cols">
                                <div class="diseno-lienzo" data-lienzo title="Toca para subir tu foto"></div>
                                <div class="diseno-ctrl">
                                    <div class="diseno-botones">
                                        <button type="button" class="btn btn-primary btn-sm" data-subir><x-icon name="plus" size="15" /> Subir foto</button>
                                        <button type="button" class="btn btn-suave btn-sm" data-quitar hidden><x-icon name="x" size="15" /> Quitar</button>
                                    </div>
                                    <div class="ctrl-grupo"><span class="label">Forma de uña</span><div class="formas" data-formas></div></div>
                                    <div class="ctrl-grupo"><span class="label">Tono de piel</span><div class="pieles" data-pieles></div></div>
                                    <div class="ctrl-grupo" data-zoom-grupo hidden><span class="label">Encuadre</span><input type="range" min="100" max="300" value="100" data-zoom aria-label="Acercar el diseño"><div class="ayuda" style="margin-top:4px">Arrastra la uña para mover el diseño.</div></div>
                                </div>
                            </div>
                            <p class="ayuda" style="margin:10px 0 0;font-size:12px">Es una vista de ejemplo. El resultado final puede variar un poco; la profesional verá tu foto.</p>
                        </div>
                    </section>
                @else
                    <section class="tarjeta">
                        <div class="tarjeta-titulo"><span class="paso">1</span><h2>Elige tu cancha</h2></div>
                        <div class="visor" id="visor">
                            <div class="visor-vacio" id="visorVacio">Cargando vista 3D…</div>
                            <div class="visor-capa arriba">
                                <span class="burbuja" id="visorNombre"><x-icon name="pitch" size="14" /> <span></span></span>
                                <span class="burbuja aviso" id="avisoLuz" hidden><x-icon name="alert-circle" size="14" /> Sin iluminación nocturna</span>
                            </div>
                            <div class="visor-capa medio">
                                <div class="dia-noche" id="vistaModo" hidden>
                                    <button type="button" class="activo" data-vista="3d">Modelo 3D</button>
                                    <button type="button" data-vista="foto">Foto real</button>
                                </div>
                            </div>
                            <div class="visor-capa abajo">
                                <span class="burbuja"><x-icon name="arrow-right" size="13" /> Arrastra para girar</span>
                                <div class="dia-noche" id="diaNoche">
                                    <button type="button" class="activo" data-noche="0">Día</button>
                                    <button type="button" data-noche="1">Noche</button>
                                </div>
                            </div>
                        </div>
                        <div class="ficha" id="ficha"></div>
                        @php $deportesLista = $canchas->pluck('deporte')->filter()->unique('id')->values(); @endphp
                        @if ($deportesLista->count() > 1)
                            <div class="chips-deporte" id="filtroDeporte" role="group" aria-label="Filtrar por deporte">
                                <button type="button" class="activo" data-dep="todos">Todos</button>
                                @foreach ($deportesLista as $d)
                                    <button type="button" data-dep="{{ $d->id }}"><x-icon :name="$d->icono()" size="15" /> {{ $d->nombre }}</button>
                                @endforeach
                            </div>
                        @endif
                        <div class="opciones una-columna" id="selectorCanchas">
                            @foreach ($canchas as $c)
                                <button type="button" class="opcion {{ ((string) old('cancha_id') === (string) $c->id) ? 'activa' : '' }}" data-grupo="recurso" data-id="{{ $c->id }}" data-nombre="{{ $c->nombre }}" data-precio="{{ (int) $c->precio_hora }}" data-3d='@json($c->datos3d())' data-deporte="{{ $c->deporte_id }}" data-deporte-nombre="{{ $c->deporte?->nombre }}" data-formato-nombre="{{ $c->nombreFormato() }}" data-superficie-nombre="{{ $c->superficieCategoria?->nombre }}">
                                    <x-icon :name="$c->iconoDeporte()" size="18" />
                                    <span class="cuerpo">{{ $c->nombre }} <span class="sub">{{ $c->resumen() }}</span></span>
                                    @if ($c->precio_hora)<span class="detalle"><b>{{ $dinero($c->precio_hora) }}</b>/hora</span>@endif
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="cancha_id" id="cancha_id" value="{{ old('cancha_id') }}">
                    </section>
                </div>

                <div class="pila col-b">
                @endif

                <section class="tarjeta">
                    <div class="tarjeta-titulo"><span class="paso">{{ $esSalon ? 3 : 2 }}</span><h2>Tus datos</h2></div>
                    <div class="campo">
                        <label for="nombre_cliente">Nombre completo</label>
                        <div class="control"><x-icon name="user" /><input type="text" id="nombre_cliente" name="nombre_cliente" value="{{ old('nombre_cliente') }}" placeholder="Tu nombre" autocomplete="name" required></div>
                    </div>
                    <div class="campo">
                        <label for="telefono">Teléfono</label>
                        <div class="control"><x-icon name="phone" /><input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="573001234567" inputmode="numeric" autocomplete="tel" required></div>
                        <div class="ayuda" style="font-size:12px">Con indicativo y solo números.</div>
                    </div>
                    @if ($negocio->email_confirmacion_activo)
                        <div class="campo" style="margin-bottom:0">
                            <label for="email_cliente">Correo <span style="font-weight:500;color:var(--muted)">(opcional)</span></label>
                            <div class="control"><x-icon name="mail" /><input type="email" id="email_cliente" name="email_cliente" value="{{ old('email_cliente') }}" placeholder="tu@correo.com" autocomplete="email" maxlength="120"></div>
                            <div class="ayuda" style="font-size:12px">Te enviaremos la confirmación de tu cita.</div>
                        </div>
                    @endif
                </section>
            </div>

            <section class="tarjeta col-c">
                <div class="tarjeta-titulo"><span class="paso">{{ $esSalon ? 4 : 3 }}</span><h2>Fecha y hora</h2></div>

                <div class="campo">
                    <label for="fecha">Fecha</label>
                    <div class="control"><x-icon name="calendar" /><input type="date" id="fecha" name="fecha" min="{{ $hoy }}" value="{{ old('fecha', $hoy) }}" required></div>
                </div>

                <span class="label">Hora de inicio</span>
                <div class="rejilla-horas" id="rejillaHoras"></div>
                <div class="estado-horas" id="estadoHoras"><x-icon name="arrow-left" size="16" /> Elige {{ $esSalon ? 'uno o más servicios' : 'una cancha' }} para ver la disponibilidad.</div>
                <input type="hidden" name="hora_inicio" id="hora_inicio">

                @unless ($esSalon)
                    <div class="campo" style="margin:18px 0 0">
                        <label for="duracion_horas">Duración</label>
                        <div class="control">
                            <x-icon name="timer" />
                            <select id="duracion_horas" name="duracion_horas">
                                @for ($i = 1; $i <= $maxHoras; $i++)
                                    <option value="{{ $i }}" @selected((int) old('duracion_horas', 1) === $i)>{{ $i }} {{ $i === 1 ? 'hora' : 'horas' }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                @endunless

                <div class="resumen">
                    <div><x-icon :name="$esSalon ? 'sparkles' : 'pitch'" size="16" /> {{ $esSalon ? 'Servicio' : 'Cancha' }} <strong id="resRecurso">—</strong></div>
                    @if ($esSalon)
                        <div><x-icon name="timer" size="16" /> Duración <strong id="resDuracion">—</strong></div>
                        <div><x-icon name="user" size="16" /> Profesional <strong id="resProfesional">—</strong></div>
                    @endif
                    <div><x-icon name="calendar" size="16" /> Fecha <strong id="resFecha">—</strong></div>
                    <div><x-icon name="clock" size="16" /> Hora <strong id="resHora">—</strong></div>
                    <div class="total" id="filaTotal" hidden><x-icon name="dollar" size="16" /> Total estimado <strong id="resTotal">—</strong></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="botonEnviar" disabled><x-icon name="check" /> Confirmar reserva</button>
            </section>
        </form>
    @endif

    @if ($negocio->telefono_contacto || $negocio->direccion || $negocio->razon_social)
        <div class="contacto">
            @if ($negocio->direccion)<span><x-icon name="building" size="14" /> {{ $negocio->direccion }}</span>@endif
            @if ($negocio->telefono_contacto)<span><x-icon name="phone" size="14" /> {{ $negocio->telefono_contacto }}</span>@endif
            @if ($negocio->razon_social)<span><x-icon name="shield" size="14" /> {{ $negocio->razon_social }}</span>@endif
        </div>
    @endif
@endsection

@push('scripts')
@unless ($esSalon)
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="{{ asset('js/cancha3d.js') }}"></script>
@else
    <script src="{{ asset('js/diseno-unas.js') }}"></script>
@endunless
<script>
(() => {
    const form = document.getElementById('formReserva');
    if (!form) return;

    const esSalon = @json($esSalon);
    const horaApertura = @json($horaApertura);
    const horaCierre = @json($horaCierre);
    const urlCanchas = @json(route('reservas.disponibilidad', $negocio->slug));
    const urlServicio = @json(route('reservas.disponibilidadServicio', $negocio->slug));
    const HORA_NOCHE = 18;

    const rejilla = document.getElementById('rejillaHoras');
    const estadoHoras = document.getElementById('estadoHoras');
    const inputRecurso = document.getElementById('cancha_id');   // solo canchas; en el salón se eligen servicios (uno o varios)
    const serviciosActivos = () => [...document.querySelectorAll('.opcion.activa[data-grupo="servicio"]')];
    const tieneRecurso = () => esSalon ? serviciosActivos().length > 0 : !!inputRecurso.value;
    const textoMinutos = min => { const h = Math.floor(min / 60), m = min % 60; return (h ? h + ' h ' : '') + (m || !h ? m + ' min' : ''); };

    /** Un campo oculto servicio_ids[] por cada servicio elegido, en el orden en que se eligieron. */
    function sincronizarServicios() {
        const contenedor = document.getElementById('servicioInputs');
        if (!contenedor) return;
        contenedor.innerHTML = '';
        serviciosActivos().forEach(b => {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = 'servicio_ids[]'; i.value = b.dataset.id;
            contenedor.appendChild(i);
        });
    }
    const inputProfesional = document.getElementById('profesional_id');
    const inputHora = document.getElementById('hora_inicio');
    const inputFecha = document.getElementById('fecha');
    const selectDuracion = document.getElementById('duracion_horas');
    const botonEnviar = document.getElementById('botonEnviar');
    const horaPrevia = @json(old('hora_inicio'));
    const dinero = v => '$' + Math.round(v).toLocaleString('es-CO');

    function horaAMPM(hora) {
        let [h, m] = hora.split(':').map(Number);
        const sufijo = h >= 12 ? 'PM' : 'AM';
        let h12 = h % 12;
        if (h12 === 0) h12 = 12;
        return { numero: `${h12}:${String(m).padStart(2, '0')}`, sufijo };
    }

    function mostrarEstado(texto) {
        rejilla.innerHTML = '';
        estadoHoras.textContent = texto;
        estadoHoras.hidden = false;
    }

    const activa = grupo => document.querySelector(`.opcion.activa[data-grupo="${grupo}"]`);

    function actualizarResumen() {
        const recurso = activa('recurso');
        const servicios = esSalon ? serviciosActivos() : [];
        document.getElementById('resRecurso').textContent = esSalon
            ? (servicios.map(s => s.dataset.nombre).join(' + ') || '—')
            : (recurso ? recurso.dataset.nombre : '—');
        if (esSalon) {
            document.getElementById('resProfesional').textContent = activa('profesional')?.dataset.nombre || '—';
            const minutos = servicios.reduce((t, s) => t + (+s.dataset.minutos || 0), 0);
            document.getElementById('resDuracion').textContent = minutos ? textoMinutos(minutos) : '—';
        }
        document.getElementById('resFecha').textContent = inputFecha.value
            ? new Date(inputFecha.value + 'T00:00').toLocaleDateString('es', { weekday: 'short', day: 'numeric', month: 'short' })
            : '—';
        const hora = inputHora.value ? horaAMPM(inputHora.value) : null;
        document.getElementById('resHora').textContent = hora ? `${hora.numero} ${hora.sufijo}` : '—';

        const total = esSalon
            ? servicios.reduce((t, s) => t + (+s.dataset.precio || 0), 0)
            : (recurso ? +recurso.dataset.precio : 0) * (+selectDuracion.value || 1);
        document.getElementById('filaTotal').hidden = !total;
        document.getElementById('resTotal').textContent = total ? dinero(total) : '—';

        botonEnviar.disabled = !(tieneRecurso() && inputHora.value && inputFecha.value);
    }

    // horas: [{ hora: 'HH:MM', disponible: bool }]
    function pintarRejilla(horas) {
        rejilla.innerHTML = '';
        if (!horas.length) {
            mostrarEstado('No hay horarios disponibles para esta fecha.');
            return;
        }
        estadoHoras.hidden = true;
        horas.forEach(({ hora, disponible }) => {
            const tile = document.createElement('button');
            tile.type = 'button';
            tile.className = 'hora-tile';
            const { numero, sufijo } = horaAMPM(hora);
            tile.innerHTML = `<span class="numero">${numero}</span><span class="meridiano">${sufijo}</span>`;
            if (!esSalon && parseInt(hora) >= HORA_NOCHE) tile.classList.add('noche');
            if (!disponible) {
                tile.classList.add('ocupada');
                tile.disabled = true;
                tile.setAttribute('aria-label', `${numero} ${sufijo} ocupada`);
            } else {
                tile.addEventListener('click', () => {
                    rejilla.querySelectorAll('.hora-tile').forEach(t => t.classList.remove('seleccionada'));
                    tile.classList.add('seleccionada');
                    inputHora.value = hora;
                    // En la tarde-noche, la cancha se muestra con las luces encendidas
                    if (!esSalon) setNoche(parseInt(hora) >= HORA_NOCHE);
                    actualizarResumen();
                });
                if (hora === inputHora.value) tile.classList.add('seleccionada');
            }
            rejilla.appendChild(tile);
        });
    }

    function horasDelDia() {
        const horas = [];
        let [h] = horaApertura.split(':').map(Number);
        const [hCierre] = horaCierre.split(':').map(Number);
        for (; h < hCierre; h++) horas.push(String(h).padStart(2, '0') + ':00');
        return horas;
    }

    function cargarDisponibilidad() {
        actualizarResumen();
        if (!tieneRecurso() || !inputFecha.value) {
            if (esSalon) { inputHora.value = ''; mostrarEstado('Elige uno o más servicios para ver la disponibilidad.'); actualizarResumen(); }
            return;
        }
        mostrarEstado('Cargando disponibilidad…');

        const params = new URLSearchParams({ fecha: inputFecha.value });
        let url;
        if (esSalon) {
            serviciosActivos().forEach(s => params.append('servicio_ids[]', s.dataset.id));
            params.set('profesional_id', inputProfesional.value);
            url = `${urlServicio}?${params}`;
        } else {
            params.set('cancha_id', inputRecurso.value);
            url = `${urlCanchas}?${params}`;
        }

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (data.cerrado) {
                    inputHora.value = '';
                    mostrarEstado('Ese día no hay atención. Elige otra fecha.');
                    actualizarResumen();
                    return;
                }
                const horas = esSalon
                    ? data.horas
                    // Las horas que ya pasaron (o sin la anticipación del negocio) no se pueden elegir
                    : horasDelDia().map(hora => ({ hora, disponible: !data.ocupadas.some(o => hora >= o.inicio && hora < o.fin) && (!data.minimo || hora >= data.minimo) }));
                if (!horas.some(h => h.hora === inputHora.value && h.disponible)) inputHora.value = '';
                pintarRejilla(horas);
                actualizarResumen();
            })
            .catch(() => mostrarEstado('No se pudo cargar la disponibilidad. Intenta de nuevo.'));
    }

    // --- Visor 3D (solo canchas) ---
    let visor = null;
    function setNoche(noche) {
        if (!visor) return;
        visor.setNoche(noche);
        document.querySelectorAll('#diaNoche button').forEach(b => b.classList.toggle('activo', (b.dataset.noche === '1') === noche));
    }
    function elegirVista(foto) {
        document.querySelectorAll('#vistaModo button').forEach(b => b.classList.toggle('activo', (b.dataset.vista === 'foto') === foto));
        visor?.setVistaFoto(foto);
    }
    document.querySelectorAll('#vistaModo button').forEach(b => b.addEventListener('click', () => elegirVista(b.dataset.vista === 'foto')));

    // Filtro por deporte (solo si el negocio ofrece más de uno)
    document.querySelectorAll('#filtroDeporte button').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('#filtroDeporte button').forEach(x => x.classList.toggle('activo', x === b));
        document.querySelectorAll('#selectorCanchas .opcion').forEach(o => { o.hidden = b.dataset.dep !== 'todos' && o.dataset.deporte !== b.dataset.dep; });
    }));

    function mostrarCancha(boton) {
        const datos = JSON.parse(boton.dataset['3d']);
        const contenedor = document.getElementById('visor');
        if (!visor && window.Cancha3D) {
            document.getElementById('visorVacio').remove();
            visor = Cancha3D.crear(contenedor, datos);
        } else if (visor) {
            visor.actualizar(datos);
        }
        document.querySelector('#visorNombre span').textContent = boton.dataset.nombre;
        document.getElementById('avisoLuz').hidden = datos.iluminacion || datos.techada;
        // Si la cancha tiene foto real, se puede alternar entre el modelo 3D y la foto
        const modo = document.getElementById('vistaModo');
        modo.hidden = !datos.foto;
        if (!datos.foto) elegirVista(false);
        const chip = (texto, clase = '') => `<span class="chip ${clase}">${texto}</span>`;
        document.getElementById('ficha').innerHTML =
            chip(boton.dataset.deporteNombre, 'verde') + (boton.dataset.formatoNombre ? chip(boton.dataset.formatoNombre) : '') + chip(boton.dataset.superficieNombre || 'Sin superficie') +
            (datos.iluminacion ? chip('Iluminación nocturna', 'ambar') : '') +
            (datos.techada ? chip('Techada') : '') +
            (+boton.dataset.precio ? chip(dinero(boton.dataset.precio) + ' / hora', 'ambar') : '');
    }
    document.querySelectorAll('#diaNoche button').forEach(b => b.addEventListener('click', () => setNoche(b.dataset.noche === '1')));

    // --- Diseño de uñas: la tarjeta solo aparece en servicios que lo admiten ---
    const tarjetaDiseno = document.getElementById('tarjetaDiseno');
    const diseno = tarjetaDiseno && window.DisenoUnas ? DisenoUnas.iniciar(document.getElementById('diseno')) : null;
    function mostrarDiseno() {
        if (!tarjetaDiseno) return;
        // Se pide la foto si alguno de los servicios elegidos la admite
        const admite = serviciosActivos().some(s => s.dataset.diseno === '1');
        if (!admite) diseno?.limpiar();      // sin diseño no se envía ninguna foto
        tarjetaDiseno.hidden = !admite;
    }

    // --- Selección de cancha / servicio / profesional ---
    document.querySelectorAll('.opcion').forEach(el => {
        el.addEventListener('click', () => {
            const grupo = el.dataset.grupo;
            if (grupo === 'servicio') {
                // Los servicios se suman: cada clic activa o quita uno
                el.classList.toggle('activa');
                el.setAttribute('aria-pressed', el.classList.contains('activa'));
                sincronizarServicios();
                mostrarDiseno();
            } else {
                document.querySelectorAll(`.opcion[data-grupo="${grupo}"]`).forEach(o => o.classList.toggle('activa', o === el));
                (grupo === 'profesional' ? inputProfesional : inputRecurso).value = el.dataset.id;
                if (!esSalon) mostrarCancha(el);
            }
            cargarDisponibilidad();
        });
    });
    inputFecha.addEventListener('change', cargarDisponibilidad);
    selectDuracion?.addEventListener('change', actualizarResumen);

    // Enlaces del QR por agenda: ?profesional=ID (salón) o ?cancha=ID (canchas) dejan esa opción ya elegida
    const preseleccion = new URLSearchParams(location.search).get(esSalon ? 'profesional' : 'cancha');
    if (preseleccion) {
        document.querySelector(`.opcion[data-grupo="${esSalon ? 'profesional' : 'recurso'}"][data-id="${CSS.escape(preseleccion)}"]`)?.click();
    }

    // Arranque: muestra en 3D la cancha elegida (o la primera) y restaura la selección si hubo errores.
    if (!esSalon) {
        const inicial = activa('recurso') || document.querySelector('.opcion[data-grupo="recurso"]');
        if (inicial) mostrarCancha(inicial);
    }
    if (horaPrevia) inputHora.value = horaPrevia;
    if (esSalon) { sincronizarServicios(); mostrarDiseno(); }   // tras un error de validación, recupera el estado
    if (tieneRecurso()) cargarDisponibilidad(); else actualizarResumen();
})();
</script>
@endpush
