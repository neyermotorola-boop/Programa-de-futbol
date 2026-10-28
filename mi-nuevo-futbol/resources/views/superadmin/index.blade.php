@extends('layouts.app')

@section('title', 'Negocios')

@section('nav')
    @include('partials.nav-superadmin', ['activo' => 'negocios'])
@endsection

@push('styles')
<style>
    .negocio { display: flex; flex-direction: column; gap: 16px; transition: border-color 0.15s, transform 0.15s; }
    .negocio:hover { border-color: var(--borde-fuerte); transform: translateY(-2px); }
    .negocio-top { display: flex; gap: 14px; align-items: flex-start; }
    .negocio-ico { width: 46px; height: 46px; border-radius: 13px; display: grid; place-items: center; flex: none; background: linear-gradient(135deg, color-mix(in srgb, var(--primario-vis) 35%, transparent), rgba(47,107,60,0.15)); color: var(--primario-vis); border: 1px solid color-mix(in srgb, var(--primario-vis) 20%, transparent); }
    .negocio-ico.salon { background: linear-gradient(135deg, color-mix(in srgb, var(--acento-vis) 30%, transparent), color-mix(in srgb, var(--acento-vis) 8%, transparent)); color: var(--acento-vis); border-color: color-mix(in srgb, var(--acento-vis) 25%, transparent); }
    .negocio-nombre { font-family: var(--fuente-titulo); font-size: 17px; font-weight: 700; line-height: 1.25; margin-bottom: 6px; word-break: break-word; }
    .negocio-datos { display: grid; gap: 8px; font-size: 14px; color: var(--texto-suave); padding: 14px 0; border-top: 1px solid var(--borde); border-bottom: 1px solid var(--borde); }
    .negocio-datos div { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .negocio-datos .icon { color: var(--muted); }
    .negocio-datos a { color: var(--primario-vis); text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .negocio-datos a:hover { text-decoration: underline; }
    .negocio.apagado { opacity: 0.72; }
    .negocio.apagado .negocio-ico { filter: grayscale(0.8); }
    .chip.apagado { color: var(--error); background: color-mix(in srgb, var(--error) 12%, transparent); border-color: color-mix(in srgb, var(--error) 30%, transparent); }
</style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <div class="eyebrow"><x-icon name="shield" size="14" /> Administración</div>
            <h1>Negocios</h1>
            <div class="subtitulo">Administra todos los negocios registrados en la plataforma.</div>
        </div>
        <a href="{{ route('superadmin.crear') }}" class="btn btn-primary"><x-icon name="plus" /> Crear negocio</a>
    </div>

    @include('superadmin._credenciales')

    @if (session('exito'))
        <div class="alerta alerta-exito" role="status"><x-icon name="check-circle" /> <div>{{ session('exito') }}</div></div>
    @endif

    <div class="stats">
        <div class="stat">
            <span class="ico-caja"><x-icon name="building" size="20" /></span>
            <div><div class="valor">{{ $negocios->count() }}</div><div class="etiqueta">Negocios</div></div>
        </div>
        <div class="stat">
            <span class="ico-caja verde"><x-icon name="pitch" size="20" /></span>
            <div><div class="valor">{{ $negocios->where('tipo_negocio', 'canchas')->count() }}</div><div class="etiqueta">Canchas deportivas</div></div>
        </div>
        <div class="stat">
            <span class="ico-caja"><x-icon name="scissors" size="20" /></span>
            <div><div class="valor">{{ $negocios->where('tipo_negocio', 'salon')->count() }}</div><div class="etiqueta">Salones de belleza</div></div>
        </div>
        <div class="stat">
            <span class="ico-caja"><x-icon name="car" size="20" /></span>
            <div><div class="valor">{{ $negocios->where('tipo_negocio', 'parqueadero')->count() }}</div><div class="etiqueta">Parqueaderos</div></div>
        </div>
    </div>

    @if ($negocios->isEmpty())
        <div class="vacio">
            <div class="ico-caja"><x-icon name="building" size="26" /></div>
            <strong>Todavía no hay negocios</strong>
            Crea el primero para empezar a recibir reservas.
            <div style="margin-top:18px"><a href="{{ route('superadmin.crear') }}" class="btn btn-primary"><x-icon name="plus" /> Crear negocio</a></div>
        </div>
    @else
        <div class="grid-auto">
            @foreach ($negocios as $negocio)
                @php
                    $esSalon = $negocio->tipo_negocio === 'salon';
                    $esParqueadero = $negocio->esParqueadero();
                @endphp
                <article class="tarjeta negocio {{ $negocio->activo ? '' : 'apagado' }}">
                    <div class="negocio-top">
                        <span class="negocio-ico {{ $esSalon ? 'salon' : '' }}"><x-icon :name="$esParqueadero ? 'car' : ($esSalon ? 'scissors' : 'pitch')" size="22" /></span>
                        <div style="min-width:0">
                            <div class="negocio-nombre">{{ $negocio->name }}</div>
                            <span class="chip {{ $esSalon ? 'ambar' : 'verde' }}">{{ $esParqueadero ? 'Parqueadero' : ($esSalon ? 'Salón de belleza' : 'Canchas deportivas') }}</span>
                            @unless ($negocio->activo)<span class="chip apagado"><x-icon name="power" size="12" /> Desactivado</span>@endunless
                        </div>
                    </div>

                    <div class="negocio-datos">
                        @if ($esParqueadero)
                            <div><x-icon name="car" size="16" /> {{ $negocio->parqueaderoIngresos()->dentro()->count() }} dentro ahora · {{ $negocio->tiposVehiculo()->count() }} tipos de vehículo</div>
                        @elseif ($esSalon)
                            <div><x-icon name="users" size="16" /> {{ $negocio->canchas_count }} {{ $negocio->canchas_count === 1 ? 'profesional' : 'profesionales' }} · {{ $negocio->servicios_count }} {{ $negocio->servicios_count === 1 ? 'servicio' : 'servicios' }}</div>
                        @else
                            <div><x-icon name="pitch" size="16" /> {{ $negocio->canchas_count }} {{ $negocio->canchas_count === 1 ? 'cancha' : 'canchas' }}</div>
                        @endif
                        @unless ($esParqueadero)
                            <div><x-icon name="clock" size="16" /> <span class="num">{{ $negocio->hora_apertura }} – {{ $negocio->hora_cierre }}</span></div>
                            <div><x-icon name="link" size="16" /> <a href="{{ route('reservas.index', $negocio->slug) }}" target="_blank">/reservas/{{ $negocio->slug }}</a></div>
                        @endunless
                    </div>

                    @include('superadmin._acciones', ['negocio' => $negocio])
                </article>
            @endforeach
        </div>
    @endif
@endsection
