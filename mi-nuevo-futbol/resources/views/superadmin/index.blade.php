<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — Negocios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600&family=Roboto+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --fondo: #0F1C14; --superficie: #16241B; --superficie-alta: #1E3024;
            --turf: #2F6B3C; --turf-claro: #3F8B4E; --linea: #F2F2E9;
            --floodlight: #F2A93B; --muted: #9FB3A5; --error: #E08674;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--fondo); color: var(--linea); font-family: 'Inter', sans-serif; padding: 24px 16px 64px; }
        .contenedor { max-width: 900px; margin: 0 auto; }
        .nav-principal {
            display: flex; justify-content: space-between; align-items: center;
            background: var(--superficie); border: 1px solid rgba(242,242,233,0.08);
            border-radius: 12px; padding: 12px 18px; margin-bottom: 24px; flex-wrap: wrap; gap: 10px;
        }
        .nav-marca { font-family: 'Oswald', sans-serif; font-weight: 700; letter-spacing: 1px; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .nav-marca .badge {
            width: 34px; height: 34px; border-radius: 9px;
            background: linear-gradient(135deg, var(--turf), var(--turf-claro));
            color: var(--floodlight); display: flex; align-items: center; justify-content: center;
            font-size: 17px; font-weight: 700; box-shadow: 0 0 10px rgba(242,169,59,0.2);
        }
        .nav-marca .tag { font-size: 12px; color: var(--floodlight); font-family: 'Oswald', sans-serif; letter-spacing: 1px; text-transform: uppercase; }
        .nav-enlaces button {
            font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px;
            color: var(--muted); background: none; border: none; padding: 9px 14px; border-radius: 8px; cursor: pointer;
        }
        .nav-enlaces button:hover { color: var(--linea); background: var(--superficie-alta); }
        h1 { font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 28px; margin: 0 0 4px; }
        .subtitulo { color: var(--muted); font-size: 14px; margin-bottom: 20px; }
        .mensaje-exito {
            background: rgba(63,139,78,0.18); border: 1px solid var(--turf-claro); color: var(--linea);
            padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;
        }
        .boton-crear {
            display: inline-block; font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 13px;
            letter-spacing: 0.5px; background: var(--floodlight); color: #1a1204; padding: 12px 20px;
            border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 24px;
        }
        .boton-crear:hover { filter: brightness(1.08); }
        .negocio {
            background: var(--superficie); border: 1px solid rgba(242,242,233,0.08); border-radius: 12px;
            padding: 16px 18px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;
            gap: 12px; flex-wrap: wrap;
        }
        .negocio .nombre { font-family: 'Oswald', sans-serif; font-size: 17px; font-weight: 600; }
        .negocio .detalle { color: var(--muted); font-size: 13px; margin-top: 4px; }
        .negocio .detalle a { color: var(--turf-claro); }
        .acciones { display: flex; gap: 8px; }
        .acciones a, .acciones button {
            font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;
            padding: 9px 14px; border-radius: 8px; text-decoration: none; cursor: pointer; border: none;
        }
        .btn-editar { background: var(--superficie-alta); color: var(--linea); border: 1px solid rgba(242,242,233,0.15) !important; }
        .btn-eliminar { background: var(--superficie-alta); color: var(--muted); border: 1px solid rgba(242,242,233,0.15) !important; }
        .btn-eliminar:hover { color: var(--error); border-color: var(--error) !important; }
        .vacio { color: var(--muted); text-align: center; padding: 40px 0; }
    </style>
</head>
<body>
<nav class="nav-principal">
    <div class="nav-marca"><span class="badge">C</span> Cancha<span class="tag">Ya · Super Admin</span></div>
    <div class="nav-enlaces">
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit">Salir</button>
        </form>
    </div>
</nav>

<div class="contenedor">
    <h1>Negocios</h1>
    <div class="subtitulo">Aquí administras todas las canchas registradas en la plataforma.</div>

    @if (session('exito'))
        <div class="mensaje-exito">{{ session('exito') }}</div>
    @endif

    <a href="{{ route('superadmin.crear') }}" class="boton-crear">+ Crear negocio nuevo</a>

    @forelse ($negocios as $negocio)
        <div class="negocio">
            
            <div>
                <div class="nombre">{{ $negocio->name }}</div>
                <div class="detalle">
                    {{ $negocio->canchas_count }} {{ $negocio->canchas_count === 1 ? 'cancha' : 'canchas' }} ·
                    {{ $negocio->hora_apertura }} – {{ $negocio->hora_cierre }} ·
                    <a href="{{ route('reservas.index', $negocio->slug) }}">/reservas/{{ $negocio->slug }}</a>
                </div>
            </div>
            <div class="acciones">
                <a href="{{ route('superadmin.editar', $negocio) }}" class="btn-editar">Editar</a>
                <form method="POST" action="{{ route('superadmin.eliminar', $negocio) }}" onsubmit="return confirm('¿Eliminar {{ $negocio->name }} junto con TODAS sus canchas y reservas? Esto no se puede deshacer.');">
                    @csrf
                    <button type="submit" class="btn-eliminar">Eliminar</button>
                </form>
            </div>
        </div>
    @empty
        <div class="vacio">Todavía no has creado ningún negocio.</div>
    @endforelse
</div>
</body>
</html>