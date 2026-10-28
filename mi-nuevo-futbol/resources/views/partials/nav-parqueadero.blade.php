{{-- Barra de navegación del panel de un parqueadero. Recibe $negocio. --}}
<style>:root { {!! $negocio->variablesMarca() !!} }</style>
<header class="topbar">
    <div class="topbar-inner">
        <a href="{{ route('parqueadero.index') }}" class="marca">
            @include('partials.logo-negocio', ['negocio' => $negocio])
            <span class="marca-texto">
                <span class="marca-nombre">{{ $negocio->name }}</span>
                <span class="marca-tag">{{ auth()->user()->esOperador() ? 'Operador · ' . auth()->user()->name : 'Parqueadero' }}</span>
            </span>
        </a>
        <nav class="menu" aria-label="Navegación principal">
            <a href="{{ route('parqueadero.index') }}" class="menu-link {{ ($activo ?? 'panel') === 'panel' ? 'activo' : '' }}">
                <x-icon name="car" /><span><b class="largo">Panel del parqueadero</b><b class="corto">Panel</b></span>
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
