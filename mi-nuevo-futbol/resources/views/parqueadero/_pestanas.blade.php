{{-- Barra de pestañas del parqueadero (solo el dueño). Recibe $activa y $modo: 'boton' en el panel (cambia de sección sin recargar) o 'enlace' en otras páginas. Opcional: $sinTarifa. --}}
@include('parqueadero._pestanas-estilos')
@php
    $pestanas = [
        'operacion' => ['Operación', 'car'],
        'tarifas' => ['Tarifas', 'tag'],
        'mensualidades' => ['Mensualidades', 'calendar-days'],
        'equipo' => ['Equipo', 'users'],
        'marca' => ['Marca', 'sparkles'],
        'historial' => ['Historial', 'receipt'],
        'dashboard' => ['Dashboard', 'chart'],
    ];
@endphp
<div class="cfg-tabs" role="tablist" aria-label="Secciones del parqueadero">
    @foreach ($pestanas as $clave => [$texto, $icono])
        @php $punto = ($clave === 'tarifas' && ($sinTarifa ?? 0) > 0) || ($clave === 'mensualidades' && ($alertaMens ?? 0) > 0); @endphp
        @if ($clave === 'dashboard')
            <a href="{{ route('parqueadero.dashboard') }}" class="cfg-tab {{ $activa === 'dashboard' ? 'activo' : '' }}"><x-icon :name="$icono" size="16" /> {{ $texto }}</a>
        @elseif ($modo === 'boton')
            <button type="button" class="cfg-tab {{ $activa === $clave ? 'activo' : '' }}" data-tab="{{ $clave }}" role="tab"><x-icon :name="$icono" size="16" /> {{ $texto }} @if ($punto)<i class="punto" title="Requiere atención"></i>@endif</button>
        @else
            <a href="{{ route('parqueadero.index') }}{{ $clave === 'operacion' ? '' : '#' . $clave }}" class="cfg-tab"><x-icon :name="$icono" size="16" /> {{ $texto }} @if ($punto)<i class="punto" title="Requiere atención"></i>@endif</a>
        @endif
    @endforeach
</div>
