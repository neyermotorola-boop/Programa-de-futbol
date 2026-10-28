{{-- Barra de navegación del panel de un negocio. Recibe $negocio y $activo. --}}
<style>:root { {!! $negocio->variablesMarca() !!} }</style>
<header class="topbar">
    <div class="topbar-inner">
        <a href="{{ route('reservas.admin') }}" class="marca">
            @include('partials.logo-negocio', ['negocio' => $negocio])
            <span class="marca-texto">
                <span class="marca-nombre">{{ $negocio->name }}</span>
                <span class="marca-tag">Panel de gestión</span>
            </span>
        </a>
        <nav class="menu" aria-label="Navegación principal">
            <a href="{{ route('reservas.admin') }}" class="menu-link {{ $activo === 'admin' ? 'activo' : '' }}">
                <x-icon name="dashboard" /><span><b class="largo">Panel del día</b><b class="corto">Hoy</b></span>
            </a>
            <a href="{{ route('dashboard.index') }}" class="menu-link {{ $activo === 'dashboard' ? 'activo' : '' }}">
                <x-icon name="chart" /><span><b class="largo">Dashboard</b><b class="corto">Métricas</b></span>
            </a>
            <a href="{{ route('reservas.semana') }}" class="menu-link {{ $activo === 'semana' ? 'activo' : '' }}">
                <x-icon name="calendar-days" /><span><b class="largo">Agenda</b><b class="corto">Agenda</b></span>
            </a>
            <a href="{{ route('configuracion.index') }}" class="menu-link {{ $activo === 'configuracion' ? 'activo' : '' }}">
                <x-icon name="settings" /><span><b class="largo">Configuración</b><b class="corto">Ajustes</b></span>
            </a>
            <a href="{{ route('reservas.index', $negocio->slug) }}" class="menu-link" target="_blank">
                <x-icon name="globe" /><span><b class="largo">Mi página</b><b class="corto">Web</b></span>
            </a>
            <span class="menu-sep"></span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="menu-link"><x-icon name="log-out" /><span><b class="largo">Salir</b><b class="corto">Salir</b></span></button>
            </form>
        </nav>
        <div class="topbar-acciones">@include('partials.tema')</div>
    </div>
</header>
