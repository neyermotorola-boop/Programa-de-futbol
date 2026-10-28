<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title') — CanchaYa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Aplica el tema guardado antes de pintar, para que no haya parpadeo.
        // Sin elección guardada = automático (sigue el tema del dispositivo).
        try { const t = localStorage.getItem('tema'); if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t; } catch (e) {}
    </script>
    <style>
        /* ---------- Variables: marca (las pisa cada negocio) y medidas ---------- */
        :root {
            --turf: #2F6B3C;
            --floodlight: #F2A93B;
            --texto-acento: #1a1204;
            --texto-primario: #ffffff;
            /* Versiones del color de marca con contraste suficiente para usarse como texto */
            --acento-txt-d: #FFC56B;  --acento-txt-l: #8A5200;
            --primario-txt-d: #5FD47A; --primario-txt-l: #1E6B33;

            --turf-claro: color-mix(in srgb, var(--turf) 75%, white);
            --floodlight-claro: color-mix(in srgb, var(--floodlight) 90%, white);
            --radio: 14px;
            --radio-sm: 10px;
            --fuente-titulo: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
            --fuente: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --gutter: clamp(16px, 4vw, 40px);
            --alto-barra-movil: 64px;
        }

        /* ---------- Tema oscuro (por defecto) ---------- */
        :root, :root[data-theme="dark"] {
            color-scheme: dark;
            --fondo: color-mix(in srgb, var(--turf) 10%, #08090a);
            --superficie: color-mix(in srgb, var(--turf) 14%, #111314);
            --superficie-alta: color-mix(in srgb, var(--turf) 18%, #181b1c);
            --superficie-hover: color-mix(in srgb, var(--turf) 24%, #1f2324);
            --borde: color-mix(in srgb, var(--linea) 9%, transparent);
            --borde-fuerte: color-mix(in srgb, var(--linea) 17%, transparent);
            --linea: #F2F2E9;
            --texto-suave: #CBD3CD;
            --muted: #98A59C;
            --acento-vis: var(--acento-txt-d);
            --primario-vis: var(--primario-txt-d);
            --brillo-marca: color-mix(in srgb, var(--turf) 32%, transparent);
            --error: #E08674;
            --alerta-exito-txt: var(--alerta-exito-txt);
            --alerta-error-txt: var(--alerta-error-txt);
            --noche-txt: var(--noche-txt);
            --sombra: 0 10px 30px rgba(0,0,0,0.35);
        }

        /* ---------- Tema claro ---------- */
        :root[data-theme="light"] {
            color-scheme: light;
            --fondo: color-mix(in srgb, var(--turf) 7%, #f5f6f4);
            --superficie: #ffffff;
            --superficie-alta: color-mix(in srgb, var(--turf) 7%, #ffffff);
            --superficie-hover: color-mix(in srgb, var(--turf) 14%, #ffffff);
            --borde: rgba(20,30,24,0.10);
            --borde-fuerte: rgba(20,30,24,0.20);
            --linea: #16201A;
            --texto-suave: #384840;
            --muted: #56655C;
            --acento-vis: var(--acento-txt-l);
            --primario-vis: var(--primario-txt-l);
            --brillo-marca: color-mix(in srgb, var(--turf) 16%, transparent);
            --error: #B23B27;
            --alerta-exito-txt: #14502A;
            --alerta-error-txt: #8C2A19;
            --noche-txt: #2A5BC7;
            --sombra: 0 10px 30px rgba(20,30,24,0.12);
        }
        /* Automático: sin elección del usuario, sigue el tema del dispositivo */
        @media (prefers-color-scheme: light) {
            :root:not([data-theme]) {
                color-scheme: light;
                --fondo: color-mix(in srgb, var(--turf) 7%, #f5f6f4);
                --superficie: #ffffff;
                --superficie-alta: color-mix(in srgb, var(--turf) 7%, #ffffff);
                --superficie-hover: color-mix(in srgb, var(--turf) 14%, #ffffff);
                --borde: rgba(20,30,24,0.10);
                --borde-fuerte: rgba(20,30,24,0.20);
                --linea: #16201A;
                --texto-suave: #384840;
                --muted: #56655C;
                --acento-vis: var(--acento-txt-l);
                --primario-vis: var(--primario-txt-l);
                --brillo-marca: color-mix(in srgb, var(--turf) 16%, transparent);
                --error: #B23B27;
                --alerta-exito-txt: #14502A;
                --alerta-error-txt: #8C2A19;
                --noche-txt: #2A5BC7;
                --sombra: 0 10px 30px rgba(20,30,24,0.12);
            }
        }
        * { box-sizing: border-box; }
        [hidden] { display: none !important; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0; min-height: 100vh; color: var(--linea); font-family: var(--fuente); font-size: 15px; line-height: 1.5;
            background:
                radial-gradient(ellipse 70% 40% at 50% -10%, var(--brillo-marca), transparent 70%),
                var(--fondo);
            background-color: var(--fondo);
            -webkit-font-smoothing: antialiased;
            font-feature-settings: "cv11", "ss01";
        }
        a { color: inherit; }
        .icon { flex: none; display: inline-block; vertical-align: middle; }
        .num { font-variant-numeric: tabular-nums; }

        /* ---------- Barra superior ---------- */
        .topbar {
            position: sticky; top: 0; z-index: 50;
            background: color-mix(in srgb, var(--fondo) 80%, transparent); backdrop-filter: blur(14px) saturate(140%); -webkit-backdrop-filter: blur(14px) saturate(140%);
            border-bottom: 1px solid var(--borde);
        }
        .topbar-inner {
            max-width: 1280px; margin: 0 auto; padding: 12px var(--gutter);
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
        }
        .marca { display: flex; align-items: center; gap: 12px; text-decoration: none; min-width: 0; }
        .marca .badge {
            width: 38px; height: 38px; border-radius: 11px; flex: none; display: grid; place-items: center;
            background: var(--turf-claro); color: var(--texto-primario);
            box-shadow: 0 0 0 1px rgba(255,255,255,0.08) inset, 0 6px 18px color-mix(in srgb, var(--acento-vis) 18%, transparent);
        }
        .marca .badge-logo { background: #fff; padding: 3px; overflow: hidden; }
        .marca .badge-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: 8px; }
        .marca-texto { display: flex; flex-direction: column; min-width: 0; line-height: 1.15; }
        .marca-nombre { font-family: var(--fuente-titulo); font-weight: 800; font-size: 16px; letter-spacing: -0.2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .marca-tag { font-size: 11px; font-weight: 600; color: var(--acento-vis); letter-spacing: 1.2px; text-transform: uppercase; }

        .menu { display: flex; align-items: center; gap: 4px; }
        .menu form { margin: 0; }
        .menu-link {
            display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border-radius: 10px;
            font-family: var(--fuente); font-size: 14px; font-weight: 500; color: var(--muted); text-decoration: none;
            background: none; border: none; cursor: pointer; transition: background 0.15s, color 0.15s; white-space: nowrap;
        }
        .menu-link:hover { color: var(--linea); background: var(--superficie-alta); }
        .menu-link.activo { color: var(--linea); background: var(--superficie-alta); box-shadow: inset 0 0 0 1px var(--borde-fuerte); }
        .menu-link.activo .icon { color: var(--acento-vis); }
        .menu-link b { font-weight: inherit; }
        .menu-link .corto { display: none; }
        .menu-sep { width: 1px; height: 22px; background: var(--borde-fuerte); margin: 0 6px; }

        .topbar-acciones { display: flex; align-items: center; gap: 8px; flex: none; }
        .tema-btn {
            width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center; cursor: pointer; flex: none;
            background: var(--superficie-alta); color: var(--texto-suave); border: 1px solid var(--borde-fuerte);
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .tema-btn:hover { background: var(--superficie-hover); color: var(--linea); }
        .tema-btn:focus-visible { outline: 3px solid color-mix(in srgb, var(--floodlight) 45%, transparent); outline-offset: 2px; }
        .tema-btn > span { display: grid; place-items: center; }

        /* ---------- Contenedor ---------- */
        .contenedor { max-width: 1280px; margin: 0 auto; padding: clamp(20px, 4vw, 40px) var(--gutter) 64px; }
        .contenedor.angosto { max-width: 1080px; }

        /* ---------- Encabezado de página ---------- */
        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px 24px; flex-wrap: wrap; margin-bottom: 28px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: var(--acento-vis); margin-bottom: 8px; }
        h1, h2, h3 { font-family: var(--fuente-titulo); letter-spacing: -0.4px; margin: 0; }
        h1 { font-size: clamp(24px, 3.2vw, 34px); font-weight: 800; line-height: 1.15; }
        h2 { font-size: 18px; font-weight: 700; }
        h3 { font-size: 16px; font-weight: 700; }
        .acciones-cuenta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .acciones-cuenta form { margin: 0; display: flex; }
        .acciones-cuenta .btn { width: 100%; }
        .acciones-cab { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .subtitulo { color: var(--muted); font-size: 15px; margin-top: 6px; }
        .enlace-volver { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 14px; font-weight: 500; text-decoration: none; margin-bottom: 16px; transition: color 0.15s; }
        .enlace-volver:hover { color: var(--acento-vis); }

        /* ---------- Tarjetas ---------- */
        .tarjeta { background: var(--superficie); border: 1px solid var(--borde); border-radius: var(--radio); padding: clamp(18px, 2.4vw, 28px); }
        .tarjeta-titulo { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .tarjeta-titulo .ico-caja { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: color-mix(in srgb, var(--primario-vis) 10%, transparent); color: var(--primario-vis); flex: none; }
        .tarjeta-titulo small { display: block; color: var(--muted); font-size: 13px; font-weight: 400; font-family: var(--fuente); letter-spacing: 0; margin-top: 2px; }

        /* ---------- Rejillas ---------- */
        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .grid-auto { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 16px; }
        .layout-2col { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; align-items: start; }
        .pila { display: grid; gap: 20px; }

        /* ---------- Estadísticas ---------- */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 180px), 1fr)); gap: 14px; margin-bottom: 28px; }
        .stat { background: var(--superficie); border: 1px solid var(--borde); border-radius: var(--radio); padding: 16px 18px; display: flex; align-items: center; gap: 14px; }
        .stat .ico-caja { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; flex: none; background: color-mix(in srgb, var(--acento-vis) 12%, transparent); color: var(--acento-vis); }
        .stat .ico-caja.verde { background: color-mix(in srgb, var(--primario-vis) 12%, transparent); color: var(--primario-vis); }
        .stat .valor { font-family: var(--fuente-titulo); font-size: 24px; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .stat .etiqueta { color: var(--muted); font-size: 13px; }

        /* ---------- Formularios ---------- */
        .campo { margin-bottom: 18px; }
        label, .label { display: block; font-size: 13px; font-weight: 600; color: var(--texto-suave); margin-bottom: 8px; }
        .control { position: relative; }
        .control > .icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; transition: color 0.15s; }
        .control:focus-within > .icon { color: var(--acento-vis); }
        .control > input[type], .control > select[id], .control > select[name] { padding-left: 42px; }
        input[type=text], input[type=email], input[type=password], input[type=time], input[type=number], input[type=tel], input[type=date], select {
            width: 100%; background: var(--superficie-alta); border: 1px solid var(--borde-fuerte); color: var(--linea);
            padding: 12px 14px; border-radius: var(--radio-sm); font-size: 15px; font-family: var(--fuente);
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s; min-height: 46px;
        }
        input::placeholder { color: rgba(143,165,151,0.65); }
        input:focus, select:focus { outline: none; border-color: var(--acento-vis); background: var(--superficie-alta); box-shadow: 0 0 0 4px color-mix(in srgb, var(--acento-vis) 14%, transparent); }
        .ayuda { font-size: 13px; color: var(--muted); margin-top: 6px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .ayuda code { font-family: var(--fuente); background: var(--superficie-alta); padding: 2px 8px; border-radius: 6px; color: var(--acento-vis); }

        /* ---------- Botones ---------- */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px;
            padding: 10px 18px; border-radius: var(--radio-sm); border: 1px solid transparent;
            font-family: var(--fuente); font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; white-space: nowrap;
            transition: transform 0.12s, filter 0.15s, background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
        }
        .btn:active { transform: translateY(1px); }
        .btn:focus-visible { outline: 3px solid rgba(255,197,107,0.45); outline-offset: 2px; }
        .btn-primary { background: linear-gradient(135deg, var(--floodlight-claro), var(--floodlight) 70%); color: var(--texto-acento); box-shadow: 0 8px 22px color-mix(in srgb, var(--floodlight) 30%, transparent); }
        .btn-primary:hover { filter: brightness(1.06); box-shadow: 0 10px 28px color-mix(in srgb, var(--acento-vis) 35%, transparent); }
        .btn-primary:disabled { opacity: 0.45; cursor: not-allowed; box-shadow: none; filter: grayscale(0.3); }
        .btn-success { background: var(--turf-claro); color: #fff; }
        .btn-success:hover { filter: brightness(1.1); }
        .btn-secondary { background: var(--superficie-alta); color: var(--linea); border-color: var(--borde-fuerte); }
        .btn-secondary:hover { background: var(--superficie-hover); border-color: color-mix(in srgb, var(--linea) 28%, transparent); }
        .btn-danger { background: transparent; color: var(--muted); border-color: var(--borde-fuerte); }
        .btn-danger:hover { color: var(--error); border-color: var(--error); background: color-mix(in srgb, var(--error) 8%, transparent); }
        .btn-dashed { width: 100%; background: transparent; color: var(--muted); border: 1px dashed color-mix(in srgb, var(--linea) 25%, transparent); }
        .btn-dashed:hover { color: var(--acento-vis); border-color: var(--acento-vis); }
        .btn-block { width: 100%; min-height: 50px; font-size: 15px; }
        .btn-icon { width: 44px; padding: 0; }
        .btn-sm { min-height: 38px; padding: 7px 14px; font-size: 13px; }
        .btn-sm.btn-icon { width: 38px; }

        /* ---------- Etiquetas ---------- */
        .chip { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 999px; background: var(--superficie-alta); color: var(--texto-suave); border: 1px solid var(--borde); white-space: nowrap; }
        .chip.ambar { color: var(--acento-vis); background: color-mix(in srgb, var(--acento-vis) 10%, transparent); border-color: color-mix(in srgb, var(--acento-vis) 25%, transparent); }
        .chip.verde { color: var(--primario-vis); background: color-mix(in srgb, var(--primario-vis) 10%, transparent); border-color: color-mix(in srgb, var(--primario-vis) 25%, transparent); }

        /* ---------- Alertas ---------- */
        .alerta { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border-radius: 12px; margin-bottom: 22px; font-size: 14px; border: 1px solid; }
        .alerta .icon { margin-top: 1px; }
        .alerta-exito { background: color-mix(in srgb, var(--primario-vis) 8%, transparent); border-color: color-mix(in srgb, var(--primario-vis) 35%, transparent); color: var(--alerta-exito-txt); }
        .alerta-exito .icon { color: var(--primario-vis); }
        .alerta-error { background: color-mix(in srgb, var(--error) 10%, transparent); border-color: color-mix(in srgb, var(--error) 45%, transparent); color: var(--alerta-error-txt); }

        /* ---------- Estado vacío ---------- */
        .vacio { text-align: center; padding: clamp(36px, 6vw, 64px) 20px; border: 1px dashed var(--borde-fuerte); border-radius: var(--radio); color: var(--muted); }
        .vacio .ico-caja { width: 56px; height: 56px; border-radius: 16px; margin: 0 auto 14px; display: grid; place-items: center; background: var(--superficie-alta); color: var(--muted); }
        .vacio strong { display: block; color: var(--linea); font-family: var(--fuente-titulo); font-size: 17px; margin-bottom: 4px; }

        /* ---------- Tablet ---------- */
        @media (max-width: 1024px) {
            .layout-2col { grid-template-columns: 1fr; }
            .menu-link span { display: none; }
            .menu-link { padding: 10px; }
        }

        /* ---------- Teléfono: el menú pasa a una barra inferior ---------- */
        @media (max-width: 720px) {
            body { font-size: 14px; }
            body.con-menu { padding-bottom: calc(var(--alto-barra-movil) + env(safe-area-inset-bottom, 0px)); }
            /* backdrop-filter haría que el menú "fixed" se ubique dentro de la barra y no abajo */
            .topbar { backdrop-filter: none; -webkit-backdrop-filter: none; background: var(--fondo); }
            .topbar-inner { padding-block: 10px; }
            .marca .badge { width: 34px; height: 34px; border-radius: 10px; }
            .menu {
                position: fixed; left: 0; right: 0; bottom: 0; z-index: 60;
                display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 0;
                height: calc(var(--alto-barra-movil) + env(safe-area-inset-bottom, 0px));
                padding: 6px 6px env(safe-area-inset-bottom, 0px);
                background: color-mix(in srgb, var(--fondo) 95%, transparent); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
                border-top: 1px solid var(--borde-fuerte);
            }
            .menu form { display: contents; }
            .menu-sep { display: none; }
            .menu-link { flex-direction: column; gap: 3px; padding: 6px 2px; font-size: 11px; border-radius: 10px; width: 100%; justify-content: center; }
            .menu-link span { display: block; }
            .menu-link .largo { display: none; }
            .menu-link .corto { display: block; }
            .menu-link.activo { box-shadow: none; background: color-mix(in srgb, var(--acento-vis) 10%, transparent); color: var(--acento-vis); }
            .grid-2 { grid-template-columns: 1fr; gap: 0; }
            .page-head { margin-bottom: 22px; }
            .page-head .btn { width: 100%; }
            .acciones-cab { width: 100%; }
            .acciones-cab .btn { flex: 1; }
            .stats { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
            .stat { padding: 12px; gap: 8px; flex-direction: column; align-items: flex-start; }
            .stat .ico-caja { width: 32px; height: 32px; border-radius: 10px; }
            .stat .etiqueta { font-size: 12px; line-height: 1.3; }
            .stat .valor { font-size: 20px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="@hasSection('nav') con-menu @endif">
    @yield('nav')
    @yield('header')

    <main class="contenedor @yield('ancho')">
        @yield('content')
    </main>

    <script>
    // Botón de tema: automático → claro → oscuro → automático
    (() => {
        const boton = document.getElementById('temaBtn');
        if (!boton) return;
        const raiz = document.documentElement;
        const nombres = { auto: 'Tema automático (según tu dispositivo)', light: 'Tema claro', dark: 'Tema oscuro' };
        const siguiente = { auto: 'light', light: 'dark', dark: 'auto' };
        const actual = () => raiz.dataset.theme || 'auto';

        function pintar() {
            const modo = actual();
            boton.querySelectorAll('[data-tema-icono]').forEach(i => { i.hidden = i.dataset.temaIcono !== modo; });
            boton.title = `${nombres[modo]} · clic para cambiar`;
            boton.setAttribute('aria-label', boton.title);
        }
        boton.addEventListener('click', () => {
            const nuevo = siguiente[actual()];
            if (nuevo === 'auto') delete raiz.dataset.theme; else raiz.dataset.theme = nuevo;
            try { nuevo === 'auto' ? localStorage.removeItem('tema') : localStorage.setItem('tema', nuevo); } catch (e) {}
            pintar();
        });
        pintar();
    })();
    </script>
    @stack('scripts')
</body>
</html>
