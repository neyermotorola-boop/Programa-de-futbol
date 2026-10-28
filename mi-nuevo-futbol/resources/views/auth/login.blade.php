@php
    // null = portal para elegir la línea de negocio; 'canchas' o 'salon' = login con ese fondo
    $esSalon = $tipo === 'salon';
    $esParq = $tipo === 'parqueadero';
    $iconoLinea = $esParq ? 'car' : ($esSalon ? 'scissors' : 'pitch');
    $nombreLinea = $esParq ? 'Parqueadero' : ($esSalon ? 'Salón de belleza' : 'Canchas deportivas');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tipo ? 'Iniciar sesión' : 'Bienvenido' }} — {{ $esSalon ? 'Salones de belleza' : 'CanchaYa' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ---------- Paleta por línea de negocio ---------- */
        :root, .tema-canchas {
            --fondo: #07120C;
            --turf: #2F6B3C;
            --turf-claro: #3F8B4E;
            --turf-neon: #5FD47A;
            --linea: #F2F2E9;
            --floodlight: #F2A93B;
            --floodlight-claro: #FFC56B;
            --boton-fin: #E08F1E;
            --texto-boton: #1a1204;
            --muted: #9FB3A5;
            --texto-suave: #C9D8CD;
            --placeholder: rgba(159,179,165,0.6);
            --error: #E08674;
            --alerta-error-txt: #F3B3A5;
            --acento-vis: #F2A93B;
            --primario-vis: #5FD47A;
            --tarjeta-1: rgba(22,36,27,0.82);
            --tarjeta-2: rgba(12,22,16,0.88);
            --input: rgba(30,48,36,0.7);
            --input-foco: rgba(30,48,36,0.95);
        }
        .tema-salon {
            --fondo: #160A12;
            --turf: #7A2E5C;
            --turf-claro: #9C4A7B;
            --turf-neon: #F4B6CF;
            --linea: #FBF1F4;
            --floodlight: #E9B8A0;
            --floodlight-claro: #F7DCCB;
            --boton-fin: #C98B72;
            --texto-boton: #2A1018;
            --muted: #C7A8B6;
            --texto-suave: #EBD3DD;
            --placeholder: rgba(199,168,182,0.6);
            --acento-vis: #F0C3AE;
            --primario-vis: #F4B6CF;
            --tarjeta-1: rgba(46,20,37,0.78);
            --tarjeta-2: rgba(26,10,21,0.86);
            --input: rgba(62,28,50,0.62);
            --input-foco: rgba(62,28,50,0.9);
        }

        .tema-parqueadero {
            --fondo: #070E1A;
            --turf: #1F4E8C;
            --turf-claro: #2F6FBF;
            --turf-neon: #7FB6FF;
            --linea: #EEF3FA;
            --floodlight: #FFC247;
            --floodlight-claro: #FFDB85;
            --boton-fin: #E8A01E;
            --texto-boton: #1a1204;
            --muted: #9DB2CC;
            --texto-suave: #C8D6E8;
            --placeholder: rgba(157,178,204,0.6);
            --acento-vis: #FFC247;
            --primario-vis: #7FB6FF;
            --tarjeta-1: rgba(16,30,52,0.82);
            --tarjeta-2: rgba(9,18,33,0.9);
            --input: rgba(24,42,70,0.7);
            --input-foco: rgba(24,42,70,0.95);
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body { margin: 0; color: var(--linea); font-family: 'Inter', system-ui, sans-serif; min-height: 100vh; background: var(--fondo); overflow-x: hidden; }
        [hidden] { display: none !important; }
        .titulo-f { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }

        /* =====================================================================
           PORTAL: fondo difuminado tipo cine con las dos líneas de negocio
           ===================================================================== */
        .portal-fondo { position: fixed; inset: 0; overflow: hidden; background: #0b0a0d; }
        .portal-fondo .mitad { position: absolute; top: -10%; bottom: -10%; width: 62%; filter: blur(46px) saturate(130%); transform: scale(1.1); }
        .portal-fondo .mitad.cancha { left: -8%; filter: blur(15px) saturate(125%);
            background:
                linear-gradient(100deg, rgba(6,40,18,0.72), rgba(8,52,26,0.5) 60%, rgba(8,52,26,0.32)),
                url('{{ asset('img/estadio.jpg') }}') 36% 52% / cover no-repeat, #145226; }
        .portal-fondo .mitad.parq { left: 22%; width: 56%; z-index: 0;
            filter: blur(15px) saturate(125%);
            background:
                linear-gradient(170deg, rgba(31,78,140,0.6), rgba(7,14,26,0.55)),
                url('{{ asset('img/parqueadero.png') }}') 50% 55% / cover no-repeat, #0f2a50; }
        .portal-fondo .mitad.salon { right: -8%;
            background:
                radial-gradient(circle at 62% 24%, rgba(255,226,200,0.55), transparent 20%),
                radial-gradient(circle at 34% 64%, rgba(244,182,207,0.6), transparent 24%),
                linear-gradient(160deg, #8f3f6c, #5b2147 55%, #2e1028); }
        /* luces fuera de foco que flotan despacio (bokeh) */
        .bokeh { position: absolute; border-radius: 50%; mix-blend-mode: screen; filter: blur(2px); opacity: 0.55; animation: flotar 14s ease-in-out infinite alternate; }
        @keyframes flotar { from { transform: translate3d(0, 0, 0) scale(1); } to { transform: translate3d(var(--dx), var(--dy), 0) scale(1.18); } }
        /* viñeta y grano de película */
        .portal-fondo::after { content: ""; position: absolute; inset: 0;
            background: radial-gradient(ellipse 75% 70% at 50% 50%, transparent 30%, rgba(5,4,6,0.82) 100%), linear-gradient(180deg, rgba(5,4,6,0.35), transparent 30%, transparent 70%, rgba(5,4,6,0.55)); }
        .grano { position: fixed; inset: -50%; pointer-events: none; opacity: 0.09; z-index: 1;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            animation: grano 1.2s steps(6) infinite; }
        @keyframes grano { 0% { transform: translate(0, 0); } 33% { transform: translate(-3%, 2%); } 66% { transform: translate(2%, -3%); } 100% { transform: translate(0, 0); } }

        .portal { position: relative; z-index: 2; min-height: 100vh; display: grid; place-items: center; padding: 40px 20px; }
        .portal-caja {
            width: 100%; max-width: 1060px; padding: 38px 38px 30px; border-radius: 32px; text-align: center;
            background: linear-gradient(180deg, rgba(24,20,26,0.55), rgba(14,12,16,0.7));
            backdrop-filter: blur(22px) saturate(150%); -webkit-backdrop-filter: blur(22px) saturate(150%);
            box-shadow: 0 50px 120px -40px rgba(0,0,0,0.85), 0 0 0 1px rgba(255,255,255,0.07) inset;
            animation: aparecer 0.8s cubic-bezier(0.2, 0.9, 0.3, 1.1) both;
        }
        @keyframes aparecer { from { opacity: 0; transform: translateY(26px) scale(0.97); } to { opacity: 1; transform: none; } }
        .portal-caja .saludo { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; letter-spacing: 2.2px; text-transform: uppercase; color: rgba(255,240,230,0.7); margin-bottom: 14px; }
        .portal-caja .saludo i { width: 6px; height: 6px; border-radius: 50%; background: #fff; box-shadow: 0 0 10px #fff; }
        .portal-caja h1 { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: clamp(28px, 4.2vw, 42px); font-weight: 800; letter-spacing: -1.2px; line-height: 1.1; margin: 0 0 10px; color: #fff; }
        .portal-caja > p { color: rgba(255,240,235,0.68); font-size: 16px; margin: 0 0 30px; }

        .negocios { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
        .negocio {
            position: relative; display: flex; flex-direction: column; justify-content: flex-end; aspect-ratio: 3 / 3.6;
            border-radius: 24px; overflow: hidden; text-align: left; text-decoration: none; color: #fff; isolation: isolate;
            box-shadow: 0 24px 50px -24px rgba(0,0,0,0.8), 0 0 0 1px rgba(255,255,255,0.08) inset;
            transition: transform 0.45s cubic-bezier(0.2, 0.9, 0.3, 1.2), box-shadow 0.35s;
            animation: aparecer 0.8s cubic-bezier(0.2, 0.9, 0.3, 1.1) both;
        }
        .negocio:nth-child(1) { animation-delay: 0.15s; } .negocio:nth-child(2) { animation-delay: 0.25s; } .negocio:nth-child(3) { animation-delay: 0.35s; }
        .negocio:hover, .negocio:focus-visible { transform: translateY(-6px); box-shadow: 0 34px 60px -24px rgba(0,0,0,0.85), 0 0 0 2px var(--brillo) inset; outline: none; }
        .negocio .arte { position: absolute; inset: 0; z-index: -1; transition: transform 0.8s cubic-bezier(0.2, 0.8, 0.3, 1); }
        .negocio:hover .arte, .negocio:focus-visible .arte { transform: scale(1.06); }
        .negocio .arte::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 35%, rgba(0,0,0,0.78)); }
        .negocio.cancha { --brillo: rgba(255,197,107,0.75); }
        .negocio.salon { --brillo: rgba(244,182,207,0.8); }
        .negocio.parqueadero { --brillo: rgba(127,182,255,0.8); }
        .negocio .arte.parqueadero { background:
                linear-gradient(180deg, rgba(8,24,52,0.45), rgba(5,14,30,0.4)),
                url('{{ asset('img/parqueadero.png') }}') 62% 50% / cover no-repeat, #12305a; }
        .negocio:hover .arte.parqueadero { filter: saturate(1.12) brightness(1.06); }
        .negocio .arte.cancha { background:
                linear-gradient(180deg, rgba(5,48,22,0.5), rgba(4,32,15,0.42)),
                url('{{ asset('img/estadio.jpg') }}') 41% 60% / cover no-repeat, #12401f; }
        .negocio:hover .arte.cancha { filter: saturate(1.12) brightness(1.06); }
        .negocio .arte.salon { background:
                radial-gradient(circle at 78% 16%, rgba(255,226,206,0.6), transparent 26%),
                radial-gradient(circle at 18% 40%, rgba(244,182,207,0.45), transparent 30%),
                linear-gradient(165deg, #a04a7b, #6b2852 50%, #2b0f24); }
        .negocio .arte svg { position: absolute; left: 50%; transform: translateX(-50%); }
        .negocio .ico { width: 54px; height: 54px; border-radius: 16px; display: grid; place-items: center; margin-bottom: 14px;
            background: rgba(255,255,255,0.16); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 0 0 1px rgba(255,255,255,0.22) inset; }
        .negocio .cuerpo { padding: 24px; }
        .negocio h2 { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 23px; font-weight: 800; letter-spacing: -0.4px; margin: 0 0 4px; }
        .negocio p { margin: 0 0 16px; font-size: 14px; color: rgba(255,255,255,0.78); line-height: 1.45; }
        .negocio .ir { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; padding: 10px 16px; border-radius: 999px; background: #fff; color: #17110f; transition: gap 0.2s; }
        .negocio:hover .ir { gap: 12px; }
        .negocio .ultimo { position: absolute; top: 16px; right: 16px; padding: 6px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; letter-spacing: 0.3px; background: rgba(0,0,0,0.42); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); box-shadow: 0 0 0 1px rgba(255,255,255,0.2) inset; }
        .portal-pie { margin-top: 24px; font-size: 13px; color: rgba(255,240,235,0.5); display: flex; justify-content: center; align-items: center; gap: 8px; }
        .portal .errores { text-align: left; margin: -8px auto 22px; max-width: 520px; }

        /* Telón de color que cubre la pantalla al elegir (y se retira al llegar al login) */
        .telon { position: fixed; inset: 0; z-index: 50; pointer-events: none; background: var(--telon, #0b0a0d); clip-path: circle(0 at var(--x, 50%) var(--y, 50%)); transition: clip-path 0.6s cubic-bezier(0.7, 0, 0.3, 1); }
        .telon.cubre { clip-path: circle(150% at var(--x, 50%) var(--y, 50%)); }
        .llegada { position: fixed; inset: 0; z-index: 50; pointer-events: none; background: var(--fondo); animation: retirar 0.7s cubic-bezier(0.7, 0, 0.3, 1) 0.05s both; }
        @keyframes retirar { to { opacity: 0; visibility: hidden; } }

        /* =====================================================================
           LOGIN: fondos por línea de negocio
           ===================================================================== */
        .escenario { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
        /* -- Canchas -- */
        .escenario.cancha { background: #06140B; }
        .escenario.cancha .foto { position: absolute; inset: -4%;
            background: url('{{ asset('img/estadio.jpg') }}') 8% 50% / cover no-repeat; filter: saturate(0.92) contrast(1.06) brightness(0.78);
            animation: lento 42s ease-in-out infinite alternate; }
        @keyframes lento { from { transform: scale(1) translate(0, 0); } to { transform: scale(1.09) translate(-1.6%, 1.2%); } }
        /* velo verde de la marca: une la foto con el resto de la plataforma y deja leer el formulario */
        .escenario.cancha::before { content: ""; position: absolute; inset: 0; z-index: 1;
            background:
                radial-gradient(ellipse 70% 55% at 85% 55%, rgba(4,20,10,0.72), transparent 75%),
                linear-gradient(100deg, rgba(5,32,15,0.78) 0%, rgba(8,52,26,0.5) 42%, rgba(4,22,11,0.78) 100%),
                linear-gradient(180deg, rgba(3,14,8,0.4), transparent 30%, transparent 62%, rgba(2,10,6,0.75)); }
        .escenario.cancha::after { z-index: 2; }
        .escenario::after { content: ""; position: absolute; inset: 0; background: radial-gradient(ellipse at center, transparent 40%, rgba(3,6,4,0.75) 100%); }
        @keyframes titilar { 0%,100% { opacity: 0.85; } 50% { opacity: 1; } }
        /* -- Salón de belleza: luz cálida de tocador, espejo con bombillos y destellos -- */
        .escenario.salon { background:
                radial-gradient(ellipse 50% 40% at 85% 10%, rgba(255,220,196,0.32), transparent 70%),
                radial-gradient(ellipse 55% 45% at 10% 90%, rgba(244,182,207,0.22), transparent 70%),
                linear-gradient(155deg, #2a0f22 0%, #4a1a3b 40%, #34122b 70%, #160a12 100%); }
        .escenario.salon::before { content: ""; position: absolute; inset: 0; opacity: 0.5;
            background:
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.05) 0 1px, transparent 2px) 0 0 / 46px 46px,
                linear-gradient(115deg, transparent 40%, rgba(255,236,224,0.06) 50%, transparent 60%); }
        .espejo { position: absolute; right: -4%; top: 50%; transform: translateY(-50%); height: 118%; max-height: 1150px; opacity: 0.5; filter: blur(0.4px); }
        .espejo .bombillo { animation: titilar 5s ease-in-out infinite; }
        .destello { position: absolute; width: 10px; height: 10px; opacity: 0; animation: destellar 4.5s ease-in-out infinite; }
        .destello::before, .destello::after { content: ""; position: absolute; left: 50%; top: 50%; background: #fff; border-radius: 2px; transform: translate(-50%, -50%); box-shadow: 0 0 8px #fff; }
        .destello::before { width: 2px; height: 14px; } .destello::after { width: 14px; height: 2px; }
        @keyframes destellar { 0%, 70%, 100% { opacity: 0; transform: scale(0.4) rotate(0); } 82% { opacity: 0.9; transform: scale(1) rotate(45deg); } }

        /* -- Parqueadero: calzada de noche con líneas de plaza y faros -- */
        .escenario.parqueadero { background: #070e1a; }
        .escenario.parqueadero .foto { position: absolute; inset: -4%;
            background: url('{{ asset('img/parqueadero.png') }}') 50% 55% / cover no-repeat; filter: saturate(0.95) contrast(1.05) brightness(0.72);
            animation: lento 42s ease-in-out infinite alternate; }
        /* velo azul de la marca: deja leer el formulario sobre la foto */
        .escenario.parqueadero::before { content: ""; position: absolute; inset: 0; z-index: 1;
            background:
                radial-gradient(ellipse 70% 55% at 85% 55%, rgba(5,12,26,0.74), transparent 75%),
                linear-gradient(100deg, rgba(7,18,38,0.8) 0%, rgba(16,40,78,0.5) 42%, rgba(5,12,26,0.8) 100%),
                linear-gradient(180deg, rgba(3,8,18,0.4), transparent 30%, transparent 62%, rgba(2,6,14,0.75)); }
        .escenario.parqueadero::after { z-index: 2; }

        /* ---------- Layout del login ---------- */
        .layout { position: relative; z-index: 1; min-height: 100vh; display: grid; grid-template-columns: 1.1fr 1fr; align-items: center; max-width: 1240px; margin: 0 auto; padding: 40px 32px; gap: 48px; }
        .hero { padding-right: 24px; }
        .marca { display: flex; align-items: center; gap: 14px; margin-bottom: 40px; }
        .badge { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, var(--turf-claro), var(--turf)); color: var(--acento-vis); display: grid; place-items: center;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.08) inset, 0 10px 30px color-mix(in srgb, var(--acento-vis) 25%, transparent); }
        .marca-texto { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
        .acento { color: var(--acento-vis); }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: var(--acento-vis);
            background: color-mix(in srgb, var(--acento-vis) 12%, transparent); border: 1px solid color-mix(in srgb, var(--acento-vis) 30%, transparent); padding: 7px 14px; border-radius: 999px; margin-bottom: 22px; }
        .eyebrow .punto { width: 7px; height: 7px; border-radius: 50%; background: var(--turf-neon); box-shadow: 0 0 10px var(--turf-neon); }
        .hero h1 { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-weight: 800; font-size: clamp(38px, 5vw, 60px); line-height: 1.05; margin: 0 0 18px; letter-spacing: -1.5px; }
        .hero h1 .resalte { background: linear-gradient(90deg, var(--floodlight-claro), var(--floodlight)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .hero p { color: var(--texto-suave); font-size: 17px; line-height: 1.6; max-width: 480px; margin: 0 0 34px; }
        .features { display: grid; gap: 14px; max-width: 460px; }
        .feature { display: flex; align-items: center; gap: 14px; color: var(--linea); font-size: 15px; }
        .feature .ico { flex: none; width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center;
            background: color-mix(in srgb, var(--primario-vis) 12%, transparent); border: 1px solid color-mix(in srgb, var(--primario-vis) 25%, transparent); color: var(--primario-vis); }
        .feature small { display: block; color: var(--muted); font-size: 13px; margin-top: 2px; }

        .tarjeta { position: relative; width: 100%; max-width: 440px; justify-self: end;
            background: linear-gradient(180deg, var(--tarjeta-1), var(--tarjeta-2));
            backdrop-filter: blur(18px) saturate(140%); -webkit-backdrop-filter: blur(18px) saturate(140%);
            border: 1px solid color-mix(in srgb, var(--linea) 10%, transparent); border-radius: 22px; padding: 34px 38px 34px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.55), 0 0 0 1px rgba(255,255,255,0.03) inset; }
        .tarjeta::before { content: ""; position: absolute; top: 0; left: 38px; right: 38px; height: 2px; border-radius: 2px; background: linear-gradient(90deg, transparent, var(--floodlight), transparent); }
        .cambiar { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 22px; padding: 7px 12px 7px 9px; border-radius: 999px; font-size: 13px; font-weight: 600; color: var(--texto-suave); text-decoration: none;
            background: color-mix(in srgb, var(--linea) 7%, transparent); transition: background 0.15s, color 0.15s; }
        .cambiar:hover { background: color-mix(in srgb, var(--linea) 13%, transparent); color: var(--linea); }
        .cambiar .mini { width: 22px; height: 22px; border-radius: 7px; display: grid; place-items: center; background: linear-gradient(135deg, var(--turf-claro), var(--turf)); color: var(--acento-vis); }
        .tarjeta .marca { display: none; }
        .tarjeta h2 { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-weight: 800; font-size: 30px; letter-spacing: -0.5px; margin: 0 0 6px; }
        .tarjeta .subtitulo { color: var(--muted); font-size: 14px; margin: 0 0 28px; }

        .campo { margin-bottom: 20px; }
        label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px; }
        .control { position: relative; }
        .control .ico-input { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; transition: color 0.2s; }
        .control input { width: 100%; background: var(--input); border: 1px solid color-mix(in srgb, var(--linea) 12%, transparent); color: var(--linea);
            padding: 14px 46px; border-radius: 12px; font-size: 15px; font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s, background 0.2s; }
        .control input::placeholder { color: var(--placeholder); }
        .control input:focus { outline: none; border-color: var(--acento-vis); background: var(--input-foco); box-shadow: 0 0 0 4px color-mix(in srgb, var(--acento-vis) 15%, transparent); }
        .control:focus-within .ico-input { color: var(--acento-vis); }
        .ver-clave { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--muted); cursor: pointer; width: 36px; height: 36px; border-radius: 8px; display: grid; place-items: center; }
        .ver-clave:hover { color: var(--linea); background: rgba(255,255,255,0.06); }
        .opciones { display: flex; justify-content: space-between; align-items: center; margin: 4px 0 26px; font-size: 14px; }
        .recordar { display: flex; align-items: center; gap: 10px; color: var(--texto-suave); cursor: pointer; user-select: none; }
        .recordar input { appearance: none; -webkit-appearance: none; width: 18px; height: 18px; margin: 0; border: 1.5px solid color-mix(in srgb, var(--linea) 30%, transparent); border-radius: 5px; cursor: pointer; display: grid; place-items: center; transition: all 0.15s; }
        .recordar input:checked { background: var(--floodlight); border-color: var(--acento-vis); }
        .recordar input:checked::after { content: ""; width: 5px; height: 9px; border: solid var(--texto-boton); border-width: 0 2px 2px 0; transform: rotate(45deg) translate(-1px,-1px); }
        .recordar input:focus-visible { box-shadow: 0 0 0 3px color-mix(in srgb, var(--acento-vis) 30%, transparent); }
        .boton { position: relative; width: 100%; border: none; cursor: pointer; background: linear-gradient(135deg, var(--floodlight-claro), var(--floodlight) 55%, var(--boton-fin));
            color: var(--texto-boton); padding: 15px; border-radius: 12px; font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 16px; font-weight: 700; letter-spacing: 0.2px;
            display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 12px 28px color-mix(in srgb, var(--acento-vis) 30%, transparent); transition: transform 0.15s, box-shadow 0.15s, filter 0.15s; }
        .boton:hover { transform: translateY(-1px); filter: brightness(1.05); box-shadow: 0 16px 36px color-mix(in srgb, var(--acento-vis) 40%, transparent); }
        .boton:active { transform: translateY(0); }
        .boton:focus-visible { outline: 3px solid color-mix(in srgb, var(--floodlight-claro) 60%, transparent); outline-offset: 3px; }
        .boton .flecha { transition: transform 0.2s; } .boton:hover .flecha { transform: translateX(3px); }
        .boton.cargando { pointer-events: none; filter: saturate(0.7); }
        .boton.cargando .texto, .boton.cargando .flecha { display: none; }
        .spinner { display: none; width: 20px; height: 20px; border: 2.5px solid color-mix(in srgb, var(--texto-boton) 25%, transparent); border-top-color: var(--texto-boton); border-radius: 50%; animation: girar 0.7s linear infinite; }
        .boton.cargando .spinner { display: block; }
        @keyframes girar { to { transform: rotate(360deg); } }
        .errores { display: flex; gap: 10px; align-items: flex-start; background: color-mix(in srgb, var(--error) 12%, transparent); border: 1px solid color-mix(in srgb, var(--error) 45%, transparent); color: var(--alerta-error-txt);
            padding: 13px 16px; border-radius: 12px; margin-bottom: 22px; font-size: 14px; animation: sacudir 0.4s ease; }
        @keyframes sacudir { 0%,100% { transform: translateX(0); } 25% { transform: translateX(-6px); } 75% { transform: translateX(6px); } }
        .pie { margin-top: 28px; padding-top: 20px; border-top: 1px solid color-mix(in srgb, var(--linea) 8%, transparent); display: flex; align-items: center; justify-content: center; gap: 8px; color: var(--muted); font-size: 13px; }

        /* ---------- Barra superior con el botón para volver al inicio ---------- */
        .barra-superior { position: fixed; top: 0; left: 0; right: 0; z-index: 6; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 26px; pointer-events: none; }
        .barra-superior > * { pointer-events: auto; }
        .volver { display: inline-flex; align-items: center; gap: 12px; padding: 9px 20px 9px 10px; border-radius: 999px; text-decoration: none; color: var(--linea);
            background: color-mix(in srgb, var(--fondo) 62%, rgba(0,0,0,0.3)); backdrop-filter: blur(14px) saturate(140%); -webkit-backdrop-filter: blur(14px) saturate(140%);
            border: 1px solid color-mix(in srgb, var(--linea) 22%, transparent); box-shadow: 0 10px 30px -12px rgba(0,0,0,0.7);
            transition: background 0.2s, transform 0.2s, border-color 0.2s; }
        .volver .flecha-v { width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; flex: none; background: var(--floodlight); color: var(--texto-boton); transition: transform 0.25s cubic-bezier(0.3, 1.4, 0.5, 1); }
        .volver b { display: block; font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 15px; font-weight: 800; letter-spacing: -0.2px; line-height: 1.15; }
        .volver small { display: block; font-size: 12px; color: var(--muted); line-height: 1.2; margin-top: 1px; }
        .volver:hover { background: color-mix(in srgb, var(--fondo) 45%, rgba(0,0,0,0.2)); border-color: color-mix(in srgb, var(--acento-vis) 55%, transparent); transform: translateX(-2px); }
        .volver:hover .flecha-v { transform: translateX(-3px); }
        .volver:focus-visible { outline: 3px solid color-mix(in srgb, var(--floodlight-claro) 70%, transparent); outline-offset: 3px; }
        .linea-actual { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; color: var(--texto-suave);
            background: color-mix(in srgb, var(--fondo) 55%, rgba(0,0,0,0.25)); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid color-mix(in srgb, var(--linea) 14%, transparent); }
        .linea-actual .icon { color: var(--acento-vis); }

        /* ---------- Responsive ---------- */
        @media (max-width: 900px) {
            .negocios { grid-template-columns: 1fr; }
            .negocio { aspect-ratio: auto; min-height: 210px; }
        }
        @media (max-width: 960px) {
            .layout { padding-top: 92px; }
            .barra-superior { padding: 14px 16px; }
            .layout { grid-template-columns: 1fr; padding: 32px 18px; gap: 0; }
            .hero { display: none; }
            .tarjeta { justify-self: center; padding: 30px 24px 28px; }
            .tarjeta .marca { display: flex; justify-content: center; margin-bottom: 24px; }
            .tarjeta h2, .tarjeta .subtitulo { text-align: center; }
            .espejo { opacity: 0.28; right: -30%; }
        }
        @media (max-width: 560px) {
            .linea-actual { display: none; }
            .volver { padding-right: 16px; }
            .volver small { display: none; }
        }
        @media (max-width: 680px) {
            .portal { padding: 20px 14px; align-items: start; }
            .portal-caja { padding: 28px 18px 22px; border-radius: 26px; }
            .portal-caja > p { margin-bottom: 22px; font-size: 15px; }
            .negocios { grid-template-columns: 1fr; gap: 14px; }
            .negocio { aspect-ratio: auto; min-height: 210px; }
            .negocio .cuerpo { padding: 20px; }
            .negocio .arte svg { opacity: 0.22; }
            .negocio .arte svg.figura { left: auto; right: 5%; transform: none; width: 30%; top: 9% !important; opacity: 0.95; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body class="{{ $tipo ? 'tema-' . $tipo : 'eligiendo' }}">

@if (! $tipo)
    {{-- ===================== PORTAL ===================== --}}
    <div class="portal-fondo" aria-hidden="true">
        <div class="mitad cancha"></div>
        <div class="mitad parq"></div>
        <div class="mitad salon"></div>
        @foreach ([[8, 14, 90, '255,205,120', '40px', '30px'], [22, 70, 140, '95,212,122', '-30px', '-40px'], [38, 30, 60, '255,230,190', '20px', '50px'], [58, 18, 110, '255,214,190', '-40px', '30px'], [72, 62, 150, '244,182,207', '30px', '-30px'], [88, 34, 80, '255,240,225', '-20px', '40px'], [48, 84, 120, '240,195,174', '40px', '-20px']] as $i => [$x, $y, $t, $c, $dx, $dy])
            <span class="bokeh" style="left:{{ $x }}%; top:{{ $y }}%; width:{{ $t }}px; height:{{ $t }}px; background: radial-gradient(circle, rgba({{ $c }},0.9), rgba({{ $c }},0) 70%); --dx:{{ $dx }}; --dy:{{ $dy }}; animation-delay: -{{ $i * 2 }}s;"></span>
        @endforeach
    </div>
    <div class="grano" aria-hidden="true"></div>

    <main class="portal">
        <section class="portal-caja" aria-labelledby="portalTitulo">
            <div class="saludo"><i></i> Bienvenido</div>
            <h1 id="portalTitulo">¿Qué tipo de negocio administras?</h1>
            <p>Elige tu línea de negocio para entrar a tu panel.</p>

            @if ($errors->any())
                <div class="errores" role="alert">
                    <x-icon name="alert-circle" size="18" />
                    <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                </div>
            @endif

            <div class="negocios">
                <a href="{{ route('login', ['tipo' => 'canchas']) }}" class="negocio cancha" data-tipo="canchas" data-color="#0B2415">
                    <div class="arte cancha" aria-hidden="true">
                    </div>
                    <span class="ultimo" data-ultimo hidden>Último usado</span>
                    <div class="cuerpo">
                        <span class="ico"><x-icon name="pitch" size="26" /></span>
                        <h2>Canchas deportivas</h2>
                        <p>Fútbol 5, 7 y 11. Reservas por hora, iluminación y agenda de cada cancha.</p>
                        <span class="ir">Entrar <x-icon name="arrow-right" size="16" /></span>
                    </div>
                </a>

                <a href="{{ route('login', ['tipo' => 'salon']) }}" class="negocio salon" data-tipo="salon" data-color="#2a0f22">
                    <div class="arte salon" aria-hidden="true">
                        <svg class="figura" viewBox="0 0 200 220" width="64%" style="top:7%">
                            <defs><linearGradient id="vidrio" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".35"/><stop offset="1" stop-color="#fff" stop-opacity=".05"/></linearGradient></defs>
                            <path d="M40 210V90a60 60 0 0 1 120 0v120z" fill="url(#vidrio)" stroke="#F7DCCB" stroke-width="3"/>
                            @foreach ([[40,110],[40,140],[40,170],[160,110],[160,140],[160,170],[62,52],[100,30],[138,52]] as [$bx, $by])
                                <circle cx="{{ $bx }}" cy="{{ $by }}" r="6.5" fill="#FFF4E6"/><circle cx="{{ $bx }}" cy="{{ $by }}" r="13" fill="#FFE2C4" opacity=".28"/>
                            @endforeach
                            <path d="M70 120l40-40M82 128l36-36" stroke="#fff" stroke-opacity=".35" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="ultimo" data-ultimo hidden>Último usado</span>
                    <div class="cuerpo">
                        <span class="ico"><x-icon name="scissors" size="26" /></span>
                        <h2>Salón de belleza</h2>
                        <p>Uñas, cabello y estética. Citas por servicio, tu equipo y sus horarios.</p>
                        <span class="ir">Entrar <x-icon name="arrow-right" size="16" /></span>
                    </div>
                </a>

                <a href="{{ route('login', ['tipo' => 'parqueadero']) }}" class="negocio parqueadero" data-tipo="parqueadero" data-color="#0b1830">
                    <div class="arte parqueadero" aria-hidden="true"></div>
                    <span class="ultimo" data-ultimo hidden>Último usado</span>
                    <div class="cuerpo">
                        <span class="ico"><x-icon name="car" size="26" /></span>
                        <h2>Parqueadero</h2>
                        <p>Placa y hora de entrada, y cobro automático por hora o por minuto.</p>
                        <span class="ir">Entrar <x-icon name="arrow-right" size="16" /></span>
                    </div>
                </a>
            </div>

            <div class="portal-pie"><x-icon name="lock" size="14" /> Conexión segura · CanchaYa © {{ date('Y') }}</div>
        </section>
    </main>
    <div class="telon" id="telon" aria-hidden="true"></div>

    <script>
    (() => {
        // Marca la línea de negocio usada la última vez
        let ultimo = null;
        try { ultimo = localStorage.getItem('tipoNegocio'); } catch (e) {}
        if (ultimo) document.querySelector(`.negocio[data-tipo="${ultimo}"] [data-ultimo]`)?.removeAttribute('hidden');

        // Al elegir: un telón del color de ese negocio cubre la pantalla desde el clic y luego se abre su login
        const telon = document.getElementById('telon');
        const sinMovimiento = matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('.negocio').forEach(tarjeta => tarjeta.addEventListener('click', e => {
            if (e.metaKey || e.ctrlKey || e.shiftKey) return;
            try { localStorage.setItem('tipoNegocio', tarjeta.dataset.tipo); } catch (err) {}
            if (sinMovimiento) return;
            e.preventDefault();
            const r = tarjeta.getBoundingClientRect();
            telon.style.setProperty('--x', (e.clientX || r.left + r.width / 2) + 'px');
            telon.style.setProperty('--y', (e.clientY || r.top + r.height / 2) + 'px');
            telon.style.setProperty('--telon', tarjeta.dataset.color);
            requestAnimationFrame(() => telon.classList.add('cubre'));
            setTimeout(() => { location.href = tarjeta.href; }, 560);
        }));
    })();
    </script>
@else
    {{-- ===================== LOGIN (con el fondo de la línea elegida) ===================== --}}
    @if ($esSalon)
        <div class="escenario salon" aria-hidden="true">
            <svg class="espejo" viewBox="0 0 520 900" fill="none">
                <defs>
                    <linearGradient id="vidrioL" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFE9DD" stop-opacity=".16"/><stop offset=".55" stop-color="#FFE9DD" stop-opacity=".03"/><stop offset="1" stop-color="#FFE9DD" stop-opacity=".1"/></linearGradient>
                    <radialGradient id="foco"><stop offset="0" stop-color="#FFF6EC"/><stop offset=".4" stop-color="#FFE0C2" stop-opacity=".7"/><stop offset="1" stop-color="#FFD3B0" stop-opacity="0"/></radialGradient>
                </defs>
                <path d="M60 880V300a200 200 0 0 1 400 0v580z" fill="url(#vidrioL)" stroke="#F0C3AE" stroke-opacity=".55" stroke-width="5"/>
                <path d="M86 880V306a174 174 0 0 1 348 0v574" stroke="#F0C3AE" stroke-opacity=".22" stroke-width="2"/>
                @foreach ([[60,380],[60,480],[60,580],[60,680],[60,780],[460,380],[460,480],[460,580],[460,680],[460,780],[92,190],[160,128],[260,100],[360,128],[428,190]] as $i => [$bx, $by])
                    <g class="bombillo" style="animation-delay: -{{ $i * 0.4 }}s"><circle cx="{{ $bx }}" cy="{{ $by }}" r="44" fill="url(#foco)" opacity=".55"/><circle cx="{{ $bx }}" cy="{{ $by }}" r="11" fill="#FFF7EE"/></g>
                @endforeach
                <path d="M170 360l120-120M200 400l130-130" stroke="#fff" stroke-opacity=".08" stroke-width="10" stroke-linecap="round"/>
            </svg>
            @foreach ([[12, 18], [28, 72], [46, 12], [62, 84], [8, 52], [54, 40], [36, 92]] as $i => [$x, $y])
                <span class="destello" style="left:{{ $x }}%; top:{{ $y }}%; animation-delay: {{ $i * 0.65 }}s"></span>
            @endforeach
        </div>
    @elseif ($esParq)
        <div class="escenario parqueadero" aria-hidden="true">
            <div class="foto"></div>
        </div>
    @else
        <div class="escenario cancha" aria-hidden="true">
            <div class="foto"></div>
        </div>
    @endif

    <nav class="barra-superior" aria-label="Navegación">
        <a href="{{ route('login') }}" class="volver" title="Volver a la pantalla de inicio para elegir otro tipo de negocio">
            <span class="flecha-v"><x-icon name="arrow-left" size="19" /></span>
            <span><b>Volver al inicio</b><small>Elegir otro tipo de negocio</small></span>
        </a>
        <span class="linea-actual"><x-icon :name="$iconoLinea" size="16" /> {{ $nombreLinea }}</span>
    </nav>

    <main class="layout">
        <section class="hero">
            <div class="marca">
                <span class="badge"><x-icon :name="$iconoLinea" size="26" /></span>
                @if ($esSalon)
                    <span class="marca-texto">Panel de <span class="acento">belleza</span></span>
                @elseif ($esParq)
                    <span class="marca-texto">Panel de <span class="acento">parqueadero</span></span>
                @else
                    <span class="marca-texto">Cancha<span class="acento">Ya</span></span>
                @endif
            </div>
            <span class="eyebrow"><span class="punto"></span> {{ $esParq ? 'Parqueaderos' : ($esSalon ? 'Salones de belleza' : 'Panel de administración') }}</span>
            @if ($esSalon)
                <h1>Tu salón,<br><span class="resalte">siempre impecable.</span></h1>
                <p>Organiza las citas, tu equipo y tus servicios en un solo lugar. Tus clientas reservan en línea, a cualquier hora.</p>
            @elseif ($esParq)
                <h1>Tu parqueadero,<br><span class="resalte">cuentas claras.</span></h1>
                <p>Registra la placa al entrar y cobra por el tiempo exacto que cada vehículo estuvo. Sin cuadernos ni cálculos a mano.</p>
            @else
                <h1>Tu cancha,<br><span class="resalte">siempre en juego.</span></h1>
                <p>Gestiona reservas, horarios y canchas desde un solo lugar. Rápido, claro y sin complicaciones.</p>
            @endif
            <div class="features">
                @foreach ($esSalon
                    ? [['sparkles', 'Citas por servicio', 'Cada servicio con su duración y su precio'], ['users', 'Agenda de tu equipo', 'Los días y horarios de cada profesional'], ['shield', 'Acceso seguro', 'Cada negocio ve solo su información']]
                    : ($esParq
                    ? [['car', 'Ingreso por placa', 'Anota la placa y la hora de entrada'], ['timer', 'Cobro por tiempo', 'Tarifa por hora o por minuto, calculada sola'], ['shield', 'Acceso seguro', 'Cada negocio ve solo su información']]
                    : [['calendar', 'Reservas en tiempo real', 'Confirma o cancela con un clic'], ['clock', 'Agenda semanal', 'Visualiza la ocupación de cada cancha'], ['shield', 'Acceso seguro', 'Cada negocio ve solo su información']]) as [$ico, $titulo, $detalle])
                    <div class="feature"><span class="ico"><x-icon :name="$ico" size="20" /></span><div>{{ $titulo }}<small>{{ $detalle }}</small></div></div>
                @endforeach
            </div>
        </section>

        <section class="tarjeta">
            <a href="{{ route('login') }}" class="cambiar"><span class="mini"><x-icon :name="$iconoLinea" size="13" /></span> {{ $nombreLinea }} · Cambiar</a>
            <div class="marca">
                <span class="badge"><x-icon :name="$iconoLinea" size="26" /></span>
                @if ($esSalon)
                    <span class="marca-texto">Panel de <span class="acento">belleza</span></span>
                @elseif ($esParq)
                    <span class="marca-texto">Panel de <span class="acento">parqueadero</span></span>
                @else
                    <span class="marca-texto">Cancha<span class="acento">Ya</span></span>
                @endif
            </div>
            <h2>Bienvenido</h2>
            <p class="subtitulo">Ingresa tus credenciales para continuar</p>

            @if ($errors->any())
                <div class="errores" role="alert">
                    <x-icon name="alert-circle" size="18" />
                    <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                </div>
            @endif

            <form method="POST" action="{{ route('login.intentar') }}" id="form-login">
                @csrf
                <div class="campo">
                    <label for="email">Correo electrónico</label>
                    <div class="control">
                        <x-icon name="mail" size="18" class="ico-input" />
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com" autocomplete="email" required autofocus>
                    </div>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <div class="control">
                        <x-icon name="lock" size="18" class="ico-input" />
                        <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="ver-clave" id="ver-clave" aria-label="Mostrar contraseña">
                            <svg id="ojo" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="opciones">
                    <label class="recordar" style="text-transform:none;letter-spacing:0;font-size:14px;font-weight:500;margin:0">
                        <input type="checkbox" name="recordar" value="1"> Recordar sesión
                    </label>
                </div>

                <button type="submit" class="boton" id="boton-entrar">
                    <span class="texto">Entrar</span>
                    <svg class="flecha" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    <span class="spinner"></span>
                </button>
            </form>

            <div class="pie"><x-icon name="lock" size="14" /> Conexión segura · CanchaYa © {{ date('Y') }}</div>
        </section>
    </main>
    <div class="llegada" aria-hidden="true"></div>

    <script>
        try { localStorage.setItem('tipoNegocio', @json($tipo)); } catch (e) {}

        const clave = document.getElementById('password');
        const verClave = document.getElementById('ver-clave');
        verClave.addEventListener('click', () => {
            const oculta = clave.type === 'password';
            clave.type = oculta ? 'text' : 'password';
            verClave.setAttribute('aria-label', oculta ? 'Ocultar contraseña' : 'Mostrar contraseña');
            document.getElementById('ojo').innerHTML = oculta
                ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M1 1l22 22"/>'
                : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        });

        document.getElementById('form-login').addEventListener('submit', () => {
            document.getElementById('boton-entrar').classList.add('cargando');
        });
    </script>
@endif
</body>
</html>
