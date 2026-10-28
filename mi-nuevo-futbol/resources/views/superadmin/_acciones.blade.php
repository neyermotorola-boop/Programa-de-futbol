{{-- Acciones sobre la cuenta de un cliente. Recibe $negocio y, opcionalmente, $sinEditar. --}}
<div class="acciones-cuenta">
    @empty($sinEditar)
        <a href="{{ route('superadmin.editar', $negocio) }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" size="16" /> Editar</a>
    @endempty

    <form method="POST" action="{{ route('superadmin.restablecer', $negocio) }}" onsubmit="return confirm('¿Restablecer la contraseña de {{ $negocio->name }}? Se generará una contraseña nueva y se cerrará su sesión si la tiene abierta.');">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm"><x-icon name="key" size="16" /> Restablecer clave</button>
    </form>

    <form method="POST" action="{{ route('superadmin.estado', $negocio) }}" @if ($negocio->activo) onsubmit="return confirm('¿Desactivar a {{ $negocio->name }}? No podrá iniciar sesión y su página de reservas quedará en pausa. Sus datos se conservan.');" @endif>
        @csrf
        @if ($negocio->activo)
            <button type="submit" class="btn btn-secondary btn-sm"><x-icon name="power" size="16" /> Desactivar</button>
        @else
            <button type="submit" class="btn btn-success btn-sm"><x-icon name="power" size="16" /> Activar</button>
        @endif
    </form>

    <form method="POST" action="{{ route('superadmin.eliminar', $negocio) }}" onsubmit="return confirm('¿Eliminar {{ $negocio->name }} junto con TODOS sus datos (canchas, servicios y reservas)? Esto no se puede deshacer.');">
        @csrf
        <button type="submit" class="btn btn-danger btn-sm"><x-icon name="trash" size="16" /> Eliminar</button>
    </form>
</div>
