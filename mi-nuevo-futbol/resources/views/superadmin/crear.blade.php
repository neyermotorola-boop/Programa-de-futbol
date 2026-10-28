@extends('layouts.app')

@section('title', 'Crear negocio')

@section('nav')
    @include('partials.nav-superadmin', ['activo' => 'crear'])
@endsection

@push('styles')
<style>
    .formulario-estrecho { max-width: 720px; }
    .tipo-selector { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 22px; }
    .tipo-opcion {
        display: flex; align-items: center; gap: 12px; text-align: left; cursor: pointer;
        border: 1px solid var(--borde-fuerte); background: var(--superficie-alta); color: var(--linea);
        padding: 14px; border-radius: 12px; font-family: var(--fuente); transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
    }
    .tipo-opcion:hover { border-color: color-mix(in srgb, var(--linea) 30%, transparent); }
    .tipo-opcion .ico-caja { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; flex: none; background: color-mix(in srgb, var(--linea) 6%, transparent); color: var(--muted); transition: all 0.15s; }
    .tipo-opcion strong { display: block; font-size: 14px; font-weight: 600; }
    .tipo-opcion small { display: block; font-size: 12px; color: var(--muted); }
    .tipo-opcion.activo { border-color: var(--acento-vis); background: color-mix(in srgb, var(--acento-vis) 8%, transparent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--acento-vis) 12%, transparent); }
    .tipo-opcion.activo .ico-caja { background: var(--floodlight); color: var(--texto-acento); }
    .seccion-oculta { display: none; }
    .nota-cliente { display: flex; gap: 10px; align-items: flex-start; margin: 4px 0 22px; padding: 12px 14px; border-radius: 12px; font-size: 13.5px; line-height: 1.5; color: var(--texto-suave); background: var(--superficie-alta); border: 1px solid var(--borde); }
    .nota-cliente .icon { flex: none; margin-top: 2px; color: var(--muted); }
    .enviar { margin-top: 8px; }

    @media (max-width: 720px) {
        .tipo-selector { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    <a href="{{ route('superadmin.index') }}" class="enlace-volver"><x-icon name="arrow-left" size="16" /> Volver a negocios</a>
    <div class="page-head">
        <div>
            <div class="eyebrow"><x-icon name="sparkles" size="14" /> Nuevo registro</div>
            <h1>Crear negocio</h1>
            <div class="subtitulo">Crea la cuenta del cliente con su correo y su clave. Lo demás lo configura él desde su panel.</div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alerta alerta-error" role="alert">
            <x-icon name="alert-circle" />
            <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        </div>
    @endif

    <form method="POST" action="{{ route('superadmin.guardar') }}" id="formCrear" class="tarjeta formulario-estrecho">
        @csrf

        <div class="tarjeta-titulo">
            <span class="ico-caja"><x-icon name="building" /></span>
            <h2>Datos del negocio <small>Información general y acceso</small></h2>
        </div>

        <span class="label">Tipo de negocio</span>
        <div class="tipo-selector">
            <button type="button" class="tipo-opcion activo" data-tipo="canchas">
                <span class="ico-caja"><x-icon name="pitch" size="20" /></span>
                <span><strong>Canchas deportivas</strong><small>Reservas por horas</small></span>
            </button>
            <button type="button" class="tipo-opcion" data-tipo="salon">
                <span class="ico-caja"><x-icon name="scissors" size="20" /></span>
                <span><strong>Salón de belleza</strong><small>Turnos por servicio</small></span>
            </button>
            <button type="button" class="tipo-opcion" data-tipo="parqueadero">
                <span class="ico-caja"><x-icon name="car" size="20" /></span>
                <span><strong>Parqueadero</strong><small>Cobro por tiempo</small></span>
            </button>
        </div>
        <input type="hidden" name="tipo_negocio" id="tipo_negocio" value="{{ old('tipo_negocio', 'canchas') }}">

        <div class="campo">
            <label for="name">Nombre del negocio</label>
            <div class="control"><x-icon name="building" /><input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Ej: Complejo El Golazo" required></div>
        </div>

        {{-- Un parqueadero no tiene página pública, así que no necesita apodo --}}
        <div class="campo" id="campoSlug">
            <label for="slug">Apodo para su página pública</label>
            <div class="control"><x-icon name="link" /><input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="Ej: el-golazo" required></div>
            <div class="ayuda">Sin espacios ni tildes. Página: <code>/reservas/<span id="preview-slug">{{ old('slug') ?: 'apodo' }}</span></code></div>
        </div>

        <div class="grid-2">
            <div class="campo">
                <label for="email">Correo para iniciar sesión</label>
                <div class="control"><x-icon name="mail" /><input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="negocio@correo.com" autocomplete="off" required></div>
            </div>
            <div class="campo">
                <label for="password">Contraseña</label>
                <div class="control"><x-icon name="lock" /><input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" autocomplete="new-password" minlength="6" required></div>
            </div>
        </div>

        <div class="nota-cliente">
            <x-icon name="info" size="16" />
            <span id="notaCliente"></span>
        </div>

        <button type="submit" class="btn btn-primary btn-block enviar"><x-icon name="check" /> Crear negocio</button>
    </form>
@endsection

@push('scripts')
<script>
// --- Selector de tipo de negocio ---
const tiposOpciones = document.querySelectorAll('.tipo-opcion');
const inputTipo = document.getElementById('tipo_negocio');
const campoSlug = document.getElementById('campoSlug');
const inputSlug = document.getElementById('slug');
const notas = {
    canchas: 'El cliente agrega después sus canchas, su horario y su marca desde Configuración.',
    salon: 'El cliente agrega después sus profesionales, sus servicios y su horario desde Configuración.',
    parqueadero: 'El cliente define después su tarifa (por hora o por minuto) y empieza a registrar placas desde su panel.',
};

function actualizarTipo(tipo) {
    inputTipo.value = tipo;
    tiposOpciones.forEach(op => op.classList.toggle('activo', op.dataset.tipo === tipo));
    const sinPagina = tipo === 'parqueadero';
    campoSlug.classList.toggle('seccion-oculta', sinPagina);
    inputSlug.required = !sinPagina;
    document.getElementById('notaCliente').textContent = notas[tipo] || '';
}

tiposOpciones.forEach(op => op.addEventListener('click', () => actualizarTipo(op.dataset.tipo)));
actualizarTipo(inputTipo.value);

// --- Slug automático a partir del nombre ---
const inputNombre = document.getElementById('name');
let slugEditadoManualmente = false;

function generarSlug(texto) {
    return texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-');
}

inputNombre.addEventListener('input', () => {
    if (!slugEditadoManualmente) {
        inputSlug.value = generarSlug(inputNombre.value);
        document.getElementById('preview-slug').textContent = inputSlug.value || 'apodo';
    }
});
inputSlug.addEventListener('input', () => {
    slugEditadoManualmente = true;
    document.getElementById('preview-slug').textContent = inputSlug.value || 'apodo';
});
</script>
@endpush
