<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva tu cancha</title>
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
            --error: #E08674;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: radial-gradient(ellipse at top, #16281C 0%, var(--fondo) 60%);
            color: var(--linea);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 32px 16px 64px;
        }
        .contenedor { max-width: 640px; margin: 0 auto; }
        .nav-principal {
            max-width: 640px;
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
        .marcador {
            text-align: center;
            margin-bottom: 28px;
        }
        .marcador .eyebrow {
            font-family: 'Roboto Mono', monospace;
            font-size: 12px;
            letter-spacing: 3px;
            color: var(--floodlight);
            text-transform: uppercase;
        }
        h1 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            font-size: 34px;
            letter-spacing: 0.5px;
            margin: 6px 0 0;
            text-transform: uppercase;
        }
        .tarjeta {
            background: var(--superficie);
            border: 1px solid rgba(242,242,233,0.08);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        input[type=text], input[type=tel], input[type=date], select {
            width: 100%;
            background: var(--superficie-alta);
            border: 1px solid rgba(242,242,233,0.12);
            color: var(--linea);
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            margin-bottom: 18px;
        }
        input:focus, select:focus {
            outline: 2px solid var(--floodlight);
            outline-offset: 1px;
        }
        .canchas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 18px;
        }
        .cancha-opcion {
            border: 1px solid rgba(242,242,233,0.15);
            background: var(--superficie-alta);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            font-family: 'Oswald', sans-serif;
            letter-spacing: 0.5px;
            transition: transform 0.1s ease, border-color 0.15s ease;
        }
        .cancha-opcion:hover { border-color: var(--turf-claro); }
        .cancha-opcion:active { transform: scale(0.98); }
        .cancha-opcion.activa {
            border-color: var(--turf-claro);
            background: var(--turf);
            box-shadow: 0 0 0 1px var(--turf-claro);
        }
        .rejilla-horas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 8px;
        }
        .hora-tile {
            position: relative;
            font-family: 'Roboto Mono', monospace;
            text-align: center;
            padding: 12px 4px;
            border-radius: 6px;
            border: 1px solid rgba(242,242,233,0.15);
            background: var(--superficie-alta);
            cursor: pointer;
            color: var(--linea);
            overflow: hidden;
            line-height: 1.2;
            transition: transform 0.1s ease, border-color 0.15s ease;
        }
        .hora-tile:not(.ocupada):hover { border-color: var(--floodlight); }
        .hora-tile:not(.ocupada):active { transform: scale(0.96); }
        .hora-tile .numero {
            display: block;
            font-weight: 700;
            font-size: 17px;
        }
        .hora-tile .meridiano {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--floodlight);
        }
        .hora-tile.seleccionada .meridiano { color: #1a1204; }
        .hora-tile.ocupada .meridiano { color: var(--muted); }
        .hora-tile.seleccionada {
            background: var(--floodlight);
            color: #1a1204;
            border-color: var(--floodlight);
        }
        .hora-tile.ocupada {
            color: var(--muted);
            cursor: not-allowed;
            opacity: 0.55;
        }
        .hora-tile.ocupada::after {
            content: 'OCUPADA';
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            letter-spacing: 1px;
            background: rgba(15,28,20,0.75);
            transform: rotate(-8deg);
        }
        .ayuda { font-size: 12px; color: var(--muted); margin: -10px 0 18px; }
        button.enviar {
            width: 100%;
            background: var(--floodlight);
            color: #1a1204;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-family: 'Oswald', sans-serif;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: filter 0.15s ease, transform 0.1s ease;
        }
        button.enviar:hover:not(:disabled) { filter: brightness(1.08); }
        button.enviar:active:not(:disabled) { transform: scale(0.99); }
        button.enviar:disabled { opacity: 0.5; cursor: not-allowed; }
        .mensaje-exito {
            background: rgba(63,139,78,0.18);
            border: 1px solid var(--turf-claro);
            color: var(--linea);
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .errores {
            background: rgba(224,134,116,0.15);
            border: 1px solid var(--error);
            color: var(--error);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 13px;
        }
    </style>
</head>
<body>
<nav class="nav-principal">
    <div class="nav-marca"><span class="badge">C</span> {{ strtoupper($negocio->name) }}</div>
    <div class="nav-enlaces">
        <a href="{{ route('reservas.index', $negocio->slug) }}" class="activo">Reservar</a>
        <a href="{{ route('reservas.admin') }}">Panel del día</a>
        <a href="{{ route('reservas.semana') }}">Agenda semanal</a>
        @auth
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" style="font-family:'Oswald',sans-serif; text-transform:uppercase; font-size:13px; letter-spacing:0.5px; color:var(--muted); background:none; border:none; padding:9px 14px; border-radius:8px; cursor:pointer; transition: background 0.15s ease, color 0.15s ease;" onmouseover="this.style.background='var(--superficie-alta)'; this.style.color='var(--linea)';" onmouseout="this.style.background='none'; this.style.color='var(--muted)';">Salir</button>
            </form>
        @else
            <a href="{{ route('login') }}">Iniciar sesión</a>
        @endauth
    </div>
</nav>
<div class="contenedor">
    <div class="marcador">
        <div class="eyebrow">Reservas de canchas · Lunes a lunes</div>
        <h1>Reserva tu cancha</h1>
    </div>

    @if (session('exito'))
        <div class="mensaje-exito">{{ session('exito') }}</div>
    @endif

    @if ($errors->any())
        <div class="errores">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('reservas.store', $negocio->slug) }}" id="formReserva">
        @csrf
        <div class="tarjeta">
            <label for="nombre_cliente">Nombre</label>
            <input type="text" id="nombre_cliente" name="nombre_cliente" value="{{ old('nombre_cliente') }}" required>

            <label for="telefono">Teléfono (con indicativo, solo números)</label>
            <input type="tel" id="telefono" name="telefono" placeholder="573001234567" value="{{ old('telefono') }}" required>

            <label>Cancha</label>
            <div class="canchas" id="selectorCanchas">
                @foreach ($canchas as $c)
                    <div class="cancha-opcion" data-id="{{ $c->id }}">{{ $c->nombre }}</div>
                @endforeach
            </div>
            <input type="hidden" name="cancha_id" id="cancha_id" value="{{ old('cancha_id') }}" required>

            <label for="fecha">Fecha</label>
            <input type="date" id="fecha" name="fecha" min="{{ now()->format('Y-m-d') }}" value="{{ old('fecha', now()->format('Y-m-d')) }}" required>

            <label>Hora de inicio</label>
            <div class="rejilla-horas" id="rejillaHoras"></div>
            <div class="ayuda">Elige la cancha y la fecha para ver la disponibilidad.</div>
            <input type="hidden" name="hora_inicio" id="hora_inicio" required>

            <label for="duracion_horas">Duración</label>
            <select id="duracion_horas" name="duracion_horas">
                @for ($i = 1; $i <= $maxHoras; $i++)
                    <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'hora' : 'horas' }}</option>
                @endfor
            </select>

            <button type="submit" class="enviar" id="botonEnviar" disabled>Confirmar reserva</button>
        </div>
    </form>
</div>

<script>
const horaApertura = "{{ $horaApertura }}";
const horaCierre = "{{ $horaCierre }}";
const rejilla = document.getElementById('rejillaHoras');
const inputCancha = document.getElementById('cancha_id');
const inputHora = document.getElementById('hora_inicio');
const inputFecha = document.getElementById('fecha');
const botonEnviar = document.getElementById('botonEnviar');
let ocupadas = [];

document.querySelectorAll('.cancha-opcion').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.cancha-opcion').forEach(o => o.classList.remove('activa'));
        el.classList.add('activa');
        inputCancha.value = el.dataset.id;
        cargarDisponibilidad();
    });
});

inputFecha.addEventListener('change', cargarDisponibilidad);

function horasDelDia() {
    const horas = [];
    let [h] = horaApertura.split(':').map(Number);
    const [hCierre] = horaCierre.split(':').map(Number);
    while (h < hCierre) {
        horas.push(String(h).padStart(2, '0') + ':00');
        h++;
    }
    return horas;
}

function seCruza(hora) {
    return ocupadas.some(o => hora >= o.inicio && hora < o.fin);
}

function horaAMPM(hora) {
    let [h, m] = hora.split(':').map(Number);
    const sufijo = h >= 12 ? 'PM' : 'AM';
    let h12 = h % 12;
    if (h12 === 0) h12 = 12;
    return { numero: `${h12}:${String(m).padStart(2, '0')}`, sufijo };
}

function pintarRejilla() {
    rejilla.innerHTML = '';
    horasDelDia().forEach(hora => {
        const div = document.createElement('div');
        div.className = 'hora-tile';
        const { numero, sufijo } = horaAMPM(hora);
        div.innerHTML = `<span class="numero">${numero}</span><span class="meridiano">${sufijo}</span>`;
        if (seCruza(hora)) {
            div.classList.add('ocupada');
        } else {
            div.addEventListener('click', () => {
                document.querySelectorAll('.hora-tile').forEach(t => t.classList.remove('seleccionada'));
                div.classList.add('seleccionada');
                inputHora.value = hora;
                actualizarBoton();
            });
        }
        rejilla.appendChild(div);
    });
}

function actualizarBoton() {
    botonEnviar.disabled = !(inputCancha.value && inputHora.value && inputFecha.value);
}

function cargarDisponibilidad() {
    if (!inputCancha.value || !inputFecha.value) return;
    fetch(`{{ route('reservas.disponibilidad', $negocio->slug) }}?fecha=${inputFecha.value}&cancha_id=${inputCancha.value}`)
        .then(r => r.json())
        .then(data => {
            ocupadas = data.ocupadas;
            inputHora.value = '';
            pintarRejilla();
            actualizarBoton();
        });
}

pintarRejilla();
</script>
</body>
</html>