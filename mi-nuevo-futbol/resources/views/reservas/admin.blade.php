<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de reservas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600&family=Roboto+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --fondo: #0F1C14;
            --superficie: #16241B;
            --superficie-alta: #1E3024;
            --turf: #2F6B3C;
            --turf-claro: #3F8B4E;
            --linea: #F2F2E9;
            --floodlight: #F2A93B;
            --muted: #9FB3A5;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--fondo);
            color: var(--linea);
            font-family: 'Inter', sans-serif;
            padding: 32px 16px 64px;
        }
        .contenedor { max-width: 720px; margin: 0 auto; }
        .nav-principal {
            max-width: 720px;
            margin: 0 auto 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--superficie);
            border: 1px solid rgba(242,242,233,0.08);
            border-radius: 12px;
            padding: 12px 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .nav-marca {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-marca .badge {
            width: 34px; height: 34px; border-radius: 9px;
            background: linear-gradient(135deg, var(--turf), var(--turf-claro));
            color: var(--floodlight); display: flex; align-items: center; justify-content: center;
            font-size: 17px; font-weight: 700; box-shadow: 0 0 10px rgba(242,169,59,0.2);
        }
        .nav-enlaces { display: flex; gap: 6px; flex-wrap: wrap; }
        .nav-enlaces a {
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            color: var(--muted);
            text-decoration: none;
            padding: 9px 14px;
            border-radius: 8px;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .nav-enlaces a:hover { color: var(--linea); background: var(--superficie-alta); }
        .nav-enlaces a.activo { background: var(--turf); color: var(--linea); }
        h1 {
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-size: 28px;
            margin-bottom: 4px;
        }
        .subtitulo { color: var(--muted); font-size: 15px; margin-bottom: 20px; }
        form.fecha { margin-bottom: 24px; }
        input[type=date] {
            background: var(--superficie-alta);
            border: 1px solid rgba(242,242,233,0.15);
            color: var(--linea);
            padding: 8px 12px;
            border-radius: 6px;
        }
        .reserva {
            background: var(--superficie);
            border: 1px solid rgba(242,242,233,0.08);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            transition: border-color 0.15s ease;
        }
        .reserva:hover { border-color: rgba(242,242,233,0.18); }
        .reserva .detalle .cancha {
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            color: var(--turf-claro);
            font-size: 12px;
            letter-spacing: 1px;
        }
        .reserva .detalle .hora {
            font-family: 'Roboto Mono', monospace;
            font-size: 22px;
            font-weight: 700;
        }
        .reserva .detalle .cliente { font-size: 15px; color: var(--linea); margin-top: 2px; }
        .reserva .detalle .estado {
            display: inline-block;
            margin-top: 4px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
        }
        .acciones { display: flex; gap: 8px; flex-wrap: wrap; }
        .acciones form { margin: 0; }
        .btn-confirmar, .btn-cancelar, .badge-confirmada {
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: filter 0.15s ease;
        }
        .btn-confirmar { background: var(--turf-claro); color: #0b150e; }
        .btn-confirmar:hover { filter: brightness(1.1); }
        .btn-cancelar { background: var(--superficie-alta); color: var(--muted); border: 1px solid rgba(242,242,233,0.15); }
        .btn-cancelar:hover { color: #E08674; border-color: #E08674; }
        .badge-confirmada {
            background: rgba(63,139,78,0.18);
            color: var(--turf-claro);
            cursor: default;
        }
        .mensaje-exito {
            background: rgba(63,139,78,0.18);
            border: 1px solid var(--turf-claro);
            color: var(--linea);
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .vacio { color: var(--muted); text-align: center; padding: 40px 0; }
    </style>
</head>
<body>
<nav class="nav-principal">
    <div class="nav-marca"><span class="badge">C</span> {{ strtoupper($negocio->name) }}</div>
    <div class="nav-enlaces">
        <a href="{{ route('reservas.index', $negocio->slug) }}">Reservas</a>
        <a href="{{ route('reservas.admin') }}" class="activo">Panel del día</a>
        <a href="{{ route('reservas.semana') }}">Agenda semanal</a>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" style="font-family:'Oswald',sans-serif; text-transform:uppercase; font-size:13px; letter-spacing:0.5px; color:var(--muted); background:none; border:none; padding:9px 14px; border-radius:8px; cursor:pointer; transition: background 0.15s ease, color 0.15s ease;" onmouseover="this.style.background='var(--superficie-alta)'; this.style.color='var(--linea)';" onmouseout="this.style.background='none'; this.style.color='var(--muted)';">Salir</button>
        </form>
    </div>
</nav>
<div class="contenedor">
    <h1>Panel de reservas</h1>
    <div class="subtitulo">Confirma o cancela cada reserva del día.</div>

    @if (session('exito'))
        <div class="mensaje-exito">{{ session('exito') }}</div>
    @endif

    <form class="fecha" method="GET" action="{{ route('reservas.admin') }}">
        <input type="date" name="fecha" value="{{ $fecha }}" onchange="this.form.submit()">
    </form>

    @forelse ($reservas as $reserva)
        <div class="reserva">
            <div class="detalle">
                <div class="cancha">{{ $reserva->cancha->nombre }}</div>
                <div class="hora">{{ \Carbon\Carbon::createFromFormat('H:i:s', $reserva->hora_inicio)->format('g:i A') }} – {{ \Carbon\Carbon::createFromFormat('H:i:s', $reserva->hora_fin)->format('g:i A') }}</div>
                <div class="cliente">{{ $reserva->nombre_cliente }} · {{ $reserva->telefono }}</div>
                <div class="estado">{{ $reserva->estado }}</div>
            </div>

            <div class="acciones">
                @if ($reserva->estado === 'confirmada')
                    <span class="badge-confirmada">✓ Confirmada</span>
                @else
                    <form method="POST" action="{{ route('reservas.confirmar', $reserva) }}">
                        @csrf
                        <button type="submit" class="btn-confirmar">Confirmar</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('reservas.cancelar', $reserva) }}" onsubmit="return confirm('¿Seguro que quieres cancelar esta reserva? El horario quedará libre de nuevo.');">
                    @csrf
                    <button type="submit" class="btn-cancelar">Cancelar</button>
                </form>
            </div>
        </div>
    @empty
        <div class="vacio">No hay reservas para esta fecha.</div>
    @endforelse
</div>
</body>
</html>