<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar {{ $negocio->name }}</title>
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
        h1 { font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 26px; margin: 0 0 4px; }
        h2 { font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 16px; margin: 28px 0 12px; color: var(--turf-claro); }
        .subtitulo { color: var(--muted); font-size: 13px; margin-bottom: 20px; }
        .tarjeta { background: var(--superficie); border: 1px solid rgba(242,242,233,0.08); border-radius: 14px; padding: 24px; margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password], input[type=time], input[type=number] {
            width: 100%; background: var(--superficie-alta); border: 1px solid rgba(242,242,233,0.12); color: var(--linea);
            padding: 12px 14px; border-radius: 8px; font-size: 15px; font-family: 'Inter', sans-serif; margin-bottom: 16px;
        }
        input:focus { outline: 2px solid var(--floodlight); outline-offset: 1px; }
        .fila-doble { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .ayuda { font-size: 12px; color: var(--muted); margin: -10px 0 16px; }
        button.guardar {
            width: 100%; background: var(--floodlight); color: #1a1204; border: none; padding: 14px; border-radius: 8px;
            font-family: 'Oswald', sans-serif; font-size: 16px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; cursor: pointer;
        }
        button.guardar:hover { filter: brightness(1.08); }
        .errores { background: rgba(224,134,116,0.15); border: 1px solid var(--error); color: var(--error); padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 13px; }
        .mensaje-exito { background: rgba(63,139,78,0.18); border: 1px solid var(--turf-claro); color: var(--linea); padding: 14px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
        .cancha-existente {
            display: flex; justify-content: space-between; align-items: center; background: var(--superficie-alta);
            padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px;
        }
        .cancha-existente button {
            background: none; border: none; color: var(--muted); cursor: pointer; font-size: 13px;
            font-family: 'Oswald', sans-serif; text-transform: uppercase;
        }
        .cancha-existente button:hover { color: var(--error); }
        .fila-agregar-cancha { display: flex; gap: 8px; margin-top: 12px; }
        .fila-agregar-cancha input { flex: 1; margin-bottom: 0; }
        .fila-agregar-cancha button {
            background: var(--turf); color: var(--linea); border: none; padding: 0 18px; border-radius: 8px;
            cursor: pointer; font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 13px;
        }
    </style>
</head>
<body>
<div class="contenedor">
    <a href="{{ route('superadmin.index') }}" class="enlace-volver">&larr; Volver a negocios</a>
    <h1>{{ $negocio->name }}</h1>
    <div class="subtitulo">/reservas/{{ $negocio->slug }}</div>

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

    <form method="POST" action="{{ route('superadmin.actualizar', $negocio) }}" class="tarjeta">
        @csrf
        <label for="name">Nombre del negocio</label>
        <input type="text" id="name" name="name" value="{{ old('name', $negocio->name) }}" required>

        <label for="slug">Apodo de su página pública</label>
        <input type="text" id="slug" name="slug" value="{{ old('slug', $negocio->slug) }}" required>

        <label for="email">Correo para iniciar sesión</label>
        <input type="email" id="email" name="email" value="{{ old('email', $negocio->email) }}" required>

        <label for="password">Nueva contraseña</label>
        <input type="password" id="password" name="password" placeholder="Déjalo vacío para no cambiarla">
        <div class="ayuda">Solo llena esto si quieres cambiar la contraseña de este negocio.</div>

        <div class="fila-doble">
            <div>
                <label for="hora_apertura">Hora de apertura</label>
                <input type="time" id="hora_apertura" name="hora_apertura" value="{{ old('hora_apertura', $negocio->hora_apertura) }}" required>
            </div>
            <div>
                <label for="hora_cierre">Hora de cierre</label>
                <input type="time" id="hora_cierre" name="hora_cierre" value="{{ old('hora_cierre', $negocio->hora_cierre) }}" required>
            </div>
        </div>

        <label for="max_horas_por_reserva">Máximo de horas seguidas por reserva</label>
        <input type="number" id="max_horas_por_reserva" name="max_horas_por_reserva" min="1" max="12" value="{{ old('max_horas_por_reserva', $negocio->max_horas_por_reserva) }}" required>

        <button type="submit" class="guardar">Guardar cambios</button>
    </form>

    <h2>Canchas</h2>
    <div class="tarjeta">
        @forelse ($negocio->canchas as $cancha)
            <div class="cancha-existente">
                <span>{{ $cancha->nombre }}</span>
                <form method="POST" action="{{ route('superadmin.canchas.eliminar', $cancha) }}" onsubmit="return confirm('¿Eliminar {{ $cancha->nombre }}? Sus reservas también se borrarán.');">
                    @csrf
                    <button type="submit">Eliminar</button>
                </form>
            </div>
        @empty
            <div class="ayuda">Este negocio todavía no tiene canchas.</div>
        @endforelse

        <form method="POST" action="{{ route('superadmin.canchas.agregar', $negocio) }}" class="fila-agregar-cancha">
            @csrf
            <input type="text" name="nombre" placeholder="Nombre de la nueva cancha" required>
            <button type="submit">Agregar</button>
        </form>
    </div>
</div>
</body>
</html>