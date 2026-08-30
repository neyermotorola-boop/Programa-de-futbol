<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear negocio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --fondo: #0F1C14; --superficie: #16241B; --superficie-alta: #1E3024;
            --turf: #2F6B3C; --turf-claro: #3F8B4E; --linea: #F2F2E9;
            --floodlight: #F2A93B; --muted: #9FB3A5; --error: #E08674;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--fondo); color: var(--linea); font-family: 'Inter', sans-serif; padding: 24px 16px 64px; }
        .contenedor { max-width: 560px; margin: 0 auto; }
        .enlace-volver { display: inline-block; color: var(--floodlight); font-size: 13px; text-decoration: none; margin-bottom: 16px; }
        h1 { font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 26px; margin: 0 0 20px; }
        .tarjeta { background: var(--superficie); border: 1px solid rgba(242,242,233,0.08); border-radius: 14px; padding: 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password], input[type=time], input[type=number] {
            width: 100%; background: var(--superficie-alta); border: 1px solid rgba(242,242,233,0.12); color: var(--linea);
            padding: 12px 14px; border-radius: 8px; font-size: 15px; font-family: 'Inter', sans-serif; margin-bottom: 16px;
        }
        input:focus { outline: 2px solid var(--floodlight); outline-offset: 1px; }
        .fila-doble { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .ayuda { font-size: 12px; color: var(--muted); margin: -10px 0 16px; }
        .cancha-fila { display: flex; gap: 8px; align-items: center; }
        .cancha-fila input { flex: 1; }
        .quitar-cancha {
            background: var(--superficie-alta); border: 1px solid rgba(242,242,233,0.15); color: var(--muted);
            border-radius: 8px; width: 42px; height: 42px; cursor: pointer; font-size: 16px; margin-bottom: 16px; flex-shrink: 0;
        }
        .quitar-cancha:hover { color: var(--error); border-color: var(--error); }
        .agregar-cancha {
            background: none; border: 1px dashed rgba(242,242,233,0.25); color: var(--muted); width: 100%;
            padding: 10px; border-radius: 8px; cursor: pointer; font-family: 'Inter', sans-serif; margin-bottom: 20px;
        }
        .agregar-cancha:hover { border-color: var(--floodlight); color: var(--floodlight); }
        button.guardar {
            width: 100%; background: var(--floodlight); color: #1a1204; border: none; padding: 14px; border-radius: 8px;
            font-family: 'Oswald', sans-serif; font-size: 16px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; cursor: pointer;
        }
        button.guardar:hover { filter: brightness(1.08); }
        .errores { background: rgba(224,134,116,0.15); border: 1px solid var(--error); color: var(--error); padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 13px; }
    </style>
</head>
<body>
<div class="contenedor">
    <a href="{{ route('superadmin.index') }}" class="enlace-volver">&larr; Volver a negocios</a>
    <h1>Crear negocio nuevo</h1>

    @if ($errors->any())
        <div class="errores">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('superadmin.guardar') }}" class="tarjeta">
        @csrf
        <label for="name">Nombre del negocio</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Ej: Complejo El Golazo" required>

        <label for="slug">Apodo para su página pública (sin espacios ni tildes)</label>
        <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="Ej: el-golazo" required>
        <div class="ayuda">Su página quedará en: tudominio.com/reservas/<span id="preview-slug">apodo</span></div>

        <label for="email">Correo para iniciar sesión</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <div class="fila-doble">
            <div>
                <label for="hora_apertura">Hora de apertura</label>
                <input type="time" id="hora_apertura" name="hora_apertura" value="{{ old('hora_apertura', '06:00') }}" required>
            </div>
            <div>
                <label for="hora_cierre">Hora de cierre</label>
                <input type="time" id="hora_cierre" name="hora_cierre" value="{{ old('hora_cierre', '23:00') }}" required>
            </div>
        </div>

        <label for="max_horas_por_reserva">Máximo de horas seguidas por reserva</label>
        <input type="number" id="max_horas_por_reserva" name="max_horas_por_reserva" min="1" max="12" value="{{ old('max_horas_por_reserva', 4) }}" required>

        <label>Canchas de este negocio</label>
        <div id="listaCanchas">
            <div class="cancha-fila">
                <input type="text" name="canchas[]" placeholder="Ej: Cancha 1" value="Cancha 1" required>
            </div>
            <div class="cancha-fila">
                <input type="text" name="canchas[]" placeholder="Ej: Cancha 2" value="Cancha 2" required>
            </div>
        </div>
        <button type="button" class="agregar-cancha" id="agregarCancha">+ Agregar otra cancha</button>

        <button type="submit" class="guardar">Crear negocio</button>
    </form>
</div>

<script>
const inputNombre = document.getElementById('name');
const inputSlug = document.getElementById('slug');
let slugEditadoManualmente = false;

function generarSlug(texto) {
    return texto
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // quita tildes
        .replace(/[^a-z0-9\s-]/g, '') // quita símbolos raros
        .trim()
        .replace(/\s+/g, '-'); // espacios -> guiones
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

document.getElementById('agregarCancha').addEventListener('click', () => {
    const lista = document.getElementById('listaCanchas');
    const numero = lista.children.length + 1;
    const fila = document.createElement('div');
    fila.className = 'cancha-fila';
    fila.innerHTML = `
        <input type="text" name="canchas[]" placeholder="Ej: Cancha ${numero}">
        <button type="button" class="quitar-cancha">&times;</button>
    `;
    fila.querySelector('.quitar-cancha').addEventListener('click', () => fila.remove());
    lista.appendChild(fila);
});
</script>
</body>
</html>