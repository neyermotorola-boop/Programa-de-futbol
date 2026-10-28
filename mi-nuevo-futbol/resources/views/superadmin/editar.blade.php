@extends('layouts.app')

@section('title', 'Editar ' . $negocio->name)

@section('nav')
    @include('partials.nav-superadmin', ['activo' => 'negocios'])
@endsection

@push('styles')
<style>
    .cabecera-negocio { display: flex; align-items: center; gap: 16px; }
    .cabecera-negocio .negocio-ico { width: 56px; height: 56px; border-radius: 16px; display: grid; place-items: center; flex: none; background: linear-gradient(135deg, var(--turf-claro), var(--turf)); color: var(--acento-vis); box-shadow: var(--sombra); }
    .meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
    .meta a.chip { text-decoration: none; }
    .meta a.chip:hover { border-color: var(--borde-fuerte); color: var(--linea); }

    .item-existente {
        display: flex; justify-content: space-between; align-items: center; gap: 12px;
        background: var(--superficie-alta); border: 1px solid var(--borde); padding: 10px 10px 10px 14px;
        border-radius: 12px; margin-bottom: 8px; font-size: 14px; font-weight: 500;
    }
    .item-existente .info { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .item-existente .info .icon { color: var(--muted); }
    .item-existente .info small { display: block; color: var(--muted); font-size: 12px; font-weight: 400; margin-top: 2px; }
    .item-existente form { margin: 0; }
    .sin-items { color: var(--muted); font-size: 14px; padding: 14px; text-align: center; border: 1px dashed var(--borde-fuerte); border-radius: 12px; margin-bottom: 8px; }

    .fila-agregar { display: flex; gap: 8px; margin-top: 14px; }
    .fila-agregar input { flex: 1; }
    .fila-agregar-servicio { display: grid; grid-template-columns: 1.5fr 0.8fr 0.8fr auto; gap: 8px; margin-top: 14px; }

    @media (max-width: 720px) {
        .fila-agregar-servicio { grid-template-columns: 1fr 1fr; }
        .fila-agregar-servicio input:first-of-type, .fila-agregar-servicio button { grid-column: 1 / -1; }
        .cabecera-negocio .negocio-ico { width: 46px; height: 46px; border-radius: 13px; }
    }
</style>
@endpush

@section('content')
    @php
        $esSalon = $negocio->tipo_negocio === 'salon';
        $esParqueadero = $negocio->esParqueadero();
    @endphp

    <a href="{{ route('superadmin.index') }}" class="enlace-volver"><x-icon name="arrow-left" size="16" /> Volver a negocios</a>
    <div class="page-head">
        <div class="cabecera-negocio">
            <span class="negocio-ico"><x-icon :name="$esParqueadero ? 'car' : ($esSalon ? 'scissors' : 'pitch')" size="26" /></span>
            <div>
                <h1>{{ $negocio->name }}</h1>
                <div class="meta">
                    <span class="chip {{ $esSalon ? 'ambar' : 'verde' }}">{{ $esParqueadero ? 'Parqueadero' : ($esSalon ? 'Salón de belleza' : 'Canchas deportivas') }}</span>
                    @unless ($esParqueadero)
                        <a href="{{ route('reservas.index', $negocio->slug) }}" target="_blank" class="chip"><x-icon name="external" size="13" /> /reservas/{{ $negocio->slug }}</a>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    @include('superadmin._credenciales')

    @if (session('exito'))
        <div class="alerta alerta-exito" role="status"><x-icon name="check-circle" /> <div>{{ session('exito') }}</div></div>
    @endif

    @if ($errors->any())
        <div class="alerta alerta-error" role="alert">
            <x-icon name="alert-circle" />
            <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        </div>
    @endif

    <section class="tarjeta" style="margin-bottom: 20px;">
        <div class="tarjeta-titulo">
            <span class="ico-caja"><x-icon name="shield" /></span>
            <h2>Cuenta del cliente <small>{{ $negocio->activo ? 'Activa: puede iniciar sesión y recibir reservas' : 'Desactivada: no puede iniciar sesión y su página está en pausa' }}</small></h2>
        </div>
        @include('superadmin._acciones', ['negocio' => $negocio, 'sinEditar' => true])
    </section>

    <div class="layout-2col">
        <form method="POST" action="{{ route('superadmin.actualizar', $negocio) }}" class="tarjeta">
            @csrf
            <div class="tarjeta-titulo">
                <span class="ico-caja"><x-icon name="building" /></span>
                <h2>Datos del negocio <small>Información general y acceso</small></h2>
            </div>

            <div class="campo">
                <label for="name">Nombre del negocio</label>
                <div class="control"><x-icon name="building" /><input type="text" id="name" name="name" value="{{ old('name', $negocio->name) }}" required></div>
            </div>

            @if ($esParqueadero)
                <input type="hidden" name="slug" value="{{ old('slug', $negocio->slug) }}">
            @else
                <div class="campo">
                    <label for="slug">Apodo de su página pública</label>
                    <div class="control"><x-icon name="link" /><input type="text" id="slug" name="slug" value="{{ old('slug', $negocio->slug) }}" required></div>
                </div>
            @endif

            <div class="campo">
                <label for="email">Correo para iniciar sesión</label>
                <div class="control"><x-icon name="mail" /><input type="email" id="email" name="email" value="{{ old('email', $negocio->email) }}" required></div>
            </div>

            <div class="campo">
                <label for="password">Nueva contraseña</label>
                <div class="control"><x-icon name="lock" /><input type="password" id="password" name="password" placeholder="Déjalo vacío para no cambiarla"></div>
                <div class="ayuda">Solo llénalo si quieres cambiar la contraseña de este negocio.</div>
            </div>

            @unless ($esParqueadero)
            <div class="grid-2">
                <div class="campo">
                    <label for="hora_apertura">Hora de apertura</label>
                    <div class="control"><x-icon name="clock" /><input type="time" id="hora_apertura" name="hora_apertura" value="{{ old('hora_apertura', $negocio->hora_apertura) }}" required></div>
                </div>
                <div class="campo">
                    <label for="hora_cierre">Hora de cierre</label>
                    <div class="control"><x-icon name="clock" /><input type="time" id="hora_cierre" name="hora_cierre" value="{{ old('hora_cierre', $negocio->hora_cierre) }}" required></div>
                </div>
            </div>

            @endunless

            @unless ($esSalon || $esParqueadero)
                <div class="campo">
                    <label for="max_horas_por_reserva">Máximo de horas seguidas por reserva</label>
                    <div class="control"><x-icon name="hourglass" /><input type="number" id="max_horas_por_reserva" name="max_horas_por_reserva" min="1" max="12" value="{{ old('max_horas_por_reserva', $negocio->max_horas_por_reserva) }}" required></div>
                </div>
            @endunless

            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Guardar cambios</button>
        </form>

        <div class="pila">
            @if ($esParqueadero)
                <section class="tarjeta">
                    <div class="tarjeta-titulo">
                        <span class="ico-caja"><x-icon name="car" /></span>
                        <h2>Parqueadero <small>{{ $negocio->tiposVehiculo->count() }} tipos de vehículo</small></h2>
                    </div>
                    @foreach ($negocio->tiposVehiculo as $tipoV)
                        <div class="item-existente"><span class="info"><x-icon :name="$tipoV->icono()" size="16" /> {{ $tipoV->nombre }}</span><span class="num">{{ $tipoV->textoTarifa() }}</span></div>
                    @endforeach
                    <div class="sin-items">Los tipos de vehículo, las tarifas y los ingresos los maneja el propio cliente desde su panel.</div>
                </section>
            @else
            <section class="tarjeta">
                <div class="tarjeta-titulo">
                    <span class="ico-caja"><x-icon :name="$esSalon ? 'users' : 'pitch'" /></span>
                    <h2>{{ $esSalon ? 'Profesionales' : 'Canchas' }} <small>{{ $negocio->canchas->count() }} registradas</small></h2>
                </div>

                @forelse ($negocio->canchas as $cancha)
                    <div class="item-existente">
                        <span class="info"><x-icon :name="$esSalon ? 'user' : 'pitch'" size="16" /> {{ $cancha->nombre }}</span>
                        <form method="POST" action="{{ route('superadmin.canchas.eliminar', $cancha) }}" onsubmit="return confirm('¿Eliminar {{ $esSalon ? 'a ' : '' }}{{ $cancha->nombre }}? Sus reservas también se borrarán.');">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm btn-icon" aria-label="Eliminar {{ $cancha->nombre }}" title="Eliminar"><x-icon name="trash" size="16" /></button>
                        </form>
                    </div>
                @empty
                    <div class="sin-items">Este negocio todavía no tiene {{ $esSalon ? 'profesionales' : 'canchas' }}.</div>
                @endforelse

                <form method="POST" action="{{ route('superadmin.canchas.agregar', $negocio) }}" class="fila-agregar">
                    @csrf
                    <input type="text" name="nombre" placeholder="{{ $esSalon ? 'Nombre de la profesional' : 'Nombre de la nueva cancha' }}" required>
                    <button type="submit" class="btn btn-success"><x-icon name="plus" size="16" /> Agregar</button>
                </form>
            </section>

            @if ($esSalon)
                <section class="tarjeta">
                    <div class="tarjeta-titulo">
                        <span class="ico-caja"><x-icon name="tag" /></span>
                        <h2>Servicios <small>{{ $negocio->servicios->count() }} en el catálogo</small></h2>
                    </div>

                    @forelse ($negocio->servicios as $servicio)
                        <div class="item-existente">
                            <div class="info">
                                <x-icon name="sparkles" size="16" />
                                <div>
                                    {{ $servicio->nombre }}
                                    <small class="num">{{ $servicio->duracion_minutos }} min @if($servicio->precio) · ${{ number_format($servicio->precio, 0, ',', '.') }} @endif</small>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('superadmin.servicios.eliminar', $servicio) }}" onsubmit="return confirm('¿Eliminar el servicio {{ $servicio->nombre }}?');">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm btn-icon" aria-label="Eliminar {{ $servicio->nombre }}" title="Eliminar"><x-icon name="trash" size="16" /></button>
                            </form>
                        </div>
                    @empty
                        <div class="sin-items">Este negocio todavía no tiene servicios.</div>
                    @endforelse

                    <form method="POST" action="{{ route('superadmin.servicios.agregar', $negocio) }}" class="fila-agregar-servicio">
                        @csrf
                        <input type="text" name="nombre" placeholder="Nombre del servicio" required>
                        <input type="number" name="duracion" placeholder="Minutos" min="5" max="480" required>
                        <input type="number" name="precio" placeholder="Precio" min="0" step="0.01">
                        <button type="submit" class="btn btn-success"><x-icon name="plus" size="16" /> Agregar</button>
                    </form>
                </section>
            @endif
            @endif
        </div>
    </div>
@endsection
