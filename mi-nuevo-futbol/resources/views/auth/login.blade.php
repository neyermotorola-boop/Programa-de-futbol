<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .tarjeta {
            width: 100%;
            max-width: 380px;
            background: var(--superficie);
            border: 1px solid rgba(242,242,233,0.08);
            border-radius: 14px;
            padding: 32px 28px;
        }
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            font-size: 24px;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .logo .badge {
            width: 40px; height: 40px; border-radius: 10px;
            background: linear-gradient(135deg, var(--turf), var(--turf-claro));
            color: var(--floodlight); display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 700; box-shadow: 0 0 10px rgba(242,169,59,0.2);
        }
        .logo .acento { color: var(--floodlight); }
        .subtitulo { text-align: center; color: var(--muted); font-size: 13px; margin-bottom: 26px; }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        input[type=email], input[type=password] {
            width: 100%;
            background: var(--superficie-alta);
            border: 1px solid rgba(242,242,233,0.12);
            color: var(--linea);
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            margin-bottom: 18px;
        }
        input:focus { outline: 2px solid var(--floodlight); outline-offset: 1px; }
        button {
            width: 100%;
            background: var(--floodlight);
            color: #1a1204;
            border: none;
            padding: 13px;
            border-radius: 8px;
            font-family: 'Oswald', sans-serif;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: filter 0.15s ease;
        }
        button:hover { filter: brightness(1.08); }
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
    <div class="tarjeta">
        <div class="logo"><span class="badge">C</span> Cancha<span class="acento">Ya</span></div>
        <div class="subtitulo">Ingresa a tu panel de administración</div>

        @if ($errors->any())
            <div class="errores">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.intentar') }}">
            @csrf
            <label for="email">Correo</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>