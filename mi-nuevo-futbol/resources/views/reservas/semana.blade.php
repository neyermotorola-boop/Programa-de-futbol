<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda semanal</title>
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
            padding: 24px 16px 64px;
        }
        .contenedor { max-width: 900px; margin: 0 auto; }
        .nav-principal {
            max-width: 900px;
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
            font-size: 26px;
            margin: 0 0 4px;
        }
        .subtitulo { color: var(--muted); font-size: 15px; margin-bottom: 4px; }
        .enlace-volver {
            display: inline-block;
            color: var(--floodlight);
            font-size: 13px;
            text-decoration: none;
            margin-bottom: 16px;
        }
        .nav-semana {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 10px;
        }
        .nav-semana a {
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            color: var(--linea);
            text-decoration: none;
            background: var(--superficie-alta);
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid rgba(242,242,233,0.12);
            transition: border-color 0.15s ease;
        }
        .nav-semana a:hover { border-color: var(--floodlight); }
        .rango-semana {
            font-family: 'Roboto Mono', monospace;
            font-size: 13px;
            color: var(--muted);
        }
        .tabs {
            display: flex;
            overflow-x: auto;
            gap: 6px;
            margin-bottom: 16px;
            padding-bottom: 4px;
        }
        .tab {
            flex: 0 0 auto;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            padding: 10px 14px;
            border-radius: 8px;
            background: var(--superficie);
            border: 1px solid rgba(242,242,233,0.1);
            cursor: pointer;
            text-align: center;
            color: var(--muted);
            transition: border-color 0.15s ease;
        }
        .tab:not(.activo):hover { border-color: rgba(242,242,233,0.3); }
        .tab .dia-num { display: block; font-family: 'Roboto Mono', monospace; font-size: 17px; color: var(--linea); margin-top: 2px; }
        .tab.activo { background: var(--turf); border-color: var(--turf-claro); color: var(--linea); }
        .tab.activo .dia-num { color: var(--linea); }
        .tab.hoy:not(.activo) { border-color: var(--floodlight); }

        .dia-panel { display: none; }
        .dia-panel.activo { display: block; }

        table.agenda {
            width: 100%;
            border-collapse: collapse;
            background: var(--superficie);
            border-radius: 12px;
            overflow: hidden;
        }
        table.agenda th {
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            color: var(--turf-claro);
            background: var(--superficie-alta);
            padding: 10px 6px;
            text-align: center;
        }
        table.agenda th.col-hora { width: 80px; }
        table.agenda td {
            padding: 10px 6px;
            text-align: center;
            font-size: 14px;
            border-top: 1px solid rgba(242,242,233,0.06);
            vertical-align: middle;
        }
        td.hora-etiqueta {
            font-family: 'Roboto Mono', monospace;
            font-weight: 700;
            color: var(--muted);
            font-size: 14px;
        }
        td.libre { color: rgba(242,242,233,0.3); font-size: 13px; }
        td.ocupada {
            background: rgba(63,139,78,0.18);
        }
        td.ocupada .cliente { font-weight: 700; color: var(--linea); display: block; font-size: 15px; }
        td.ocupada .estado-mini {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--floodlight);
        }
    </style>
</head>
<body>
<nav class="nav-principal">
    <div class="nav-marca"><span class="badge">C</span> {{ strtoupper($negocio->name) }}</div>
    <div class="nav-enlaces">
        <a href="{{ route('reservas.index', $negocio->slug) }}">Reservas</a>
        <a href="{{ route('reservas.admin') }}">Panel del día</a>
        <a href="{{ route('reservas.semana') }}" class="activo">Agenda semanal</a>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" style="font-family:'Oswald',sans-serif; text-transform:uppercase; font-size:13px; letter-spacing:0.5px; color:var(--muted); background:none; border:none; padding:9px 14px; border-radius:8px; cursor:pointer; transition: background 0.15s ease, color 0.15s ease;" onmouseover="this.style.background='var(--superficie-alta)'; this.style.color='var(--linea)';" onmouseout="this.style.background='none'; this.style.color='var(--muted)';">Salir</button>
        </form>
    </div>
</nav>
<div class="contenedor">
    <h1>Agenda semanal</h1>
    <div class="subtitulo">Fútbol 5 · vista de lunes a domingo, Cancha 1 y Cancha 2</div>

    <div class="nav-semana">
        <a href="{{ route('reservas.semana', ['semana' => $inicioSemana->copy()->subWeek()->format('Y-m-d')]) }}">&larr; Semana anterior</a>
        <div class="rango-semana">{{ $inicioSemana->format('d M') }} – {{ $inicioSemana->copy()->addDays(6)->format('d M Y') }}</div>
        <a href="{{ route('reservas.semana', ['semana' => $inicioSemana->copy()->addWeek()->format('Y-m-d')]) }}">Semana siguiente &rarr;</a>
    </div>

    <div class="tabs" id="tabs">
        @foreach ($dias as $i => $dia)
            <div class="tab {{ $dia['fecha']->isToday() ? 'hoy' : '' }} {{ $i === 0 ? 'activo' : '' }}" data-dia="{{ $i }}">
                {{ substr($dia['nombre'], 0, 3) }}
                <span class="dia-num">{{ $dia['fecha']->format('d') }}</span>
            </div>
        @endforeach
    </div>

    @foreach ($dias as $i => $dia)
        <div class="dia-panel {{ $i === 0 ? 'activo' : '' }}" data-dia-panel="{{ $i }}">
            <table class="agenda">
                <thead>
                    <tr>
                        <th class="col-hora">Hora</th>
                        @foreach ($canchas as $cancha)
                            <th>{{ $cancha->nombre }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($horas as $hora)
                        <tr>
                            @php
                                [$hh, $mm] = explode(':', $hora);
                                $hh = (int) $hh;
                                $sufijo = $hh >= 12 ? 'PM' : 'AM';
                                $h12 = $hh % 12; if ($h12 === 0) $h12 = 12;
                            @endphp
                            <td class="hora-etiqueta">{{ $h12 }}:{{ $mm }} {{ $sufijo }}</td>
                            @foreach ($canchas as $cancha)
                                @php $reserva = $dia['matriz'][$cancha->id][$hora] ?? null; @endphp
                                @if ($reserva)
                                    <td class="ocupada">
                                        <span class="cliente">{{ $reserva->nombre_cliente }}</span>
                                        <span class="estado-mini">{{ $reserva->estado }}</span>
                                    </td>
                                @else
                                    <td class="libre">Libre</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>

<script>
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const i = tab.dataset.dia;
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('activo'));
        document.querySelectorAll('.dia-panel').forEach(p => p.classList.remove('activo'));
        tab.classList.add('activo');
        document.querySelector(`[data-dia-panel="${i}"]`).classList.add('activo');
    });
});
</script>
</body>
</html>