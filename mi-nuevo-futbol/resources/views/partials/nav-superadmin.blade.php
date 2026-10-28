{{-- Barra de navegación del super admin. Recibe $activo. --}}
<header class="topbar">
    <div class="topbar-inner">
        <a href="{{ route('superadmin.index') }}" class="marca">
            <span class="badge"><x-icon name="pitch" size="20" /></span>
            <span class="marca-texto">
                <span class="marca-nombre">CanchaYa</span>
                <span class="marca-tag">Super Admin</span>
            </span>
        </a>
        <nav class="menu" aria-label="Navegación principal">
            <a href="{{ route('superadmin.index') }}" class="menu-link {{ $activo === 'negocios' ? 'activo' : '' }}">
                <x-icon name="building" /><span>Negocios</span>
            </a>
            <a href="{{ route('superadmin.crear') }}" class="menu-link {{ $activo === 'crear' ? 'activo' : '' }}">
                <x-icon name="plus" /><span>Crear negocio</span>
            </a>
            <span class="menu-sep"></span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="menu-link"><x-icon name="log-out" /><span>Salir</span></button>
            </form>
        </nav>
        <div class="topbar-acciones">@include('partials.tema')</div>
    </div>
</header>
