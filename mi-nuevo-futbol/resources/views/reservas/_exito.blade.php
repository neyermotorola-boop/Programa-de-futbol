{{-- Pop-up de agradecimiento tras reservar. Recibe $ok (datos de la reserva guardados en la sesión). --}}
@push('styles')
<style>
    .exito-overlay {
        position: fixed; inset: 0; z-index: 200; display: grid; place-items: center; padding: 20px;
        background: color-mix(in srgb, var(--fondo) 60%, rgba(0,0,0,0.5));
        backdrop-filter: blur(10px) saturate(130%); -webkit-backdrop-filter: blur(10px) saturate(130%);
        opacity: 0; transition: opacity 0.4s ease; overflow-y: auto;
    }
    .exito-overlay.visible { opacity: 1; }
    .exito-confeti { position: fixed; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 203; }

    .exito-card {
        position: relative; z-index: 202; width: 100%; max-width: 440px; margin: auto; padding: 34px 30px 26px; text-align: center;
        border-radius: 28px; background: var(--superficie);
        box-shadow: 0 40px 90px -30px rgba(0,0,0,0.65), 0 0 0 1px color-mix(in srgb, var(--linea) 8%, transparent);
        opacity: 0; transform: translateY(28px) scale(0.93);
        transition: opacity 0.45s ease, transform 0.6s cubic-bezier(0.2, 0.9, 0.3, 1.25);
    }
    .exito-overlay.visible .exito-card { opacity: 1; transform: none; }
    .exito-overlay.saliendo { opacity: 0; }
    .exito-overlay.saliendo .exito-card { opacity: 0; transform: translateY(16px) scale(0.96); transition-duration: 0.28s; transition-timing-function: ease-in; }

    /* Aparición escalonada del contenido */
    .exito-card .escalon { opacity: 0; transform: translateY(12px); }
    .exito-overlay.visible .escalon { animation: exitoSube 0.55s cubic-bezier(0.2, 0.8, 0.3, 1) forwards; animation-delay: calc(var(--n) * 90ms + 380ms); }
    @keyframes exitoSube { to { opacity: 1; transform: none; } }

    /* Marca de verificación que se dibuja sola */
    .exito-sello { position: relative; width: 92px; height: 92px; margin: 0 auto 18px; }
    .exito-sello::before, .exito-sello::after {
        content: ""; position: absolute; inset: 0; border-radius: 50%; background: var(--floodlight); opacity: 0;
    }
    .exito-overlay.visible .exito-sello::before { animation: exitoOnda 1.8s ease-out 0.5s 2; }
    .exito-overlay.visible .exito-sello::after { animation: exitoOnda 1.8s ease-out 1.1s 2; }
    @keyframes exitoOnda { 0% { transform: scale(0.8); opacity: 0.35; } 100% { transform: scale(1.9); opacity: 0; } }
    .exito-sello svg { position: relative; width: 100%; height: 100%; overflow: visible; }
    .exito-sello .disco { fill: var(--floodlight); transform-origin: 50% 50%; transform: scale(0); }
    .exito-overlay.visible .exito-sello .disco { animation: exitoDisco 0.6s cubic-bezier(0.3, 1.5, 0.5, 1) 0.2s forwards; }
    @keyframes exitoDisco { to { transform: scale(1); } }
    .exito-sello .tilde { fill: none; stroke: var(--texto-acento); stroke-width: 6; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 50; stroke-dashoffset: 50; }
    .exito-overlay.visible .exito-sello .tilde { animation: exitoTilde 0.5s ease-out 0.65s forwards; }
    @keyframes exitoTilde { to { stroke-dashoffset: 0; } }

    .exito-card h2 { font-size: clamp(24px, 6vw, 30px); font-weight: 800; letter-spacing: -0.8px; line-height: 1.12; margin-bottom: 8px; }
    .exito-card .lead { color: var(--texto-suave); font-size: 15px; line-height: 1.5; margin: 0; }
    .exito-estado { display: inline-flex; align-items: center; gap: 8px; margin-top: 14px; padding: 6px 14px 6px 10px; border-radius: 999px; font-size: 12.5px; font-weight: 700; letter-spacing: 0.3px; background: color-mix(in srgb, var(--primario-vis) 13%, transparent); color: var(--texto-suave); }
    .exito-estado::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--primario-vis); box-shadow: 0 0 0 4px color-mix(in srgb, var(--primario-vis) 20%, transparent); }
    .exito-estado.pendiente { background: color-mix(in srgb, var(--acento-vis) 13%, transparent); }
    .exito-estado.pendiente::before { background: var(--acento-vis); box-shadow: 0 0 0 4px color-mix(in srgb, var(--acento-vis) 20%, transparent); }

    .exito-radicado {
        margin: 22px 0 18px; padding: 16px 16px 14px; border-radius: 18px; text-align: left;
        background: linear-gradient(160deg, color-mix(in srgb, var(--floodlight) 14%, var(--superficie-alta)), var(--superficie-alta));
    }
    .exito-radicado small { display: block; font-size: 11px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; color: var(--muted); margin-bottom: 4px; }
    .exito-radicado .fila { display: flex; align-items: center; justify-content: space-between; gap: 8px 10px; flex-wrap: wrap; }
    .exito-radicado strong { font-family: var(--fuente-titulo); font-size: clamp(20px, 5.4vw, 27px); font-weight: 800; letter-spacing: 0.3px; white-space: nowrap; }
    .exito-copiar { flex: none; display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 10px; border: 0; cursor: pointer; font-family: var(--fuente); font-size: 12.5px; font-weight: 600; background: color-mix(in srgb, var(--linea) 9%, transparent); color: var(--texto-suave); transition: background 0.15s, color 0.15s; }
    .exito-copiar:hover { background: color-mix(in srgb, var(--linea) 15%, transparent); color: var(--linea); }
    .exito-copiar.listo { background: color-mix(in srgb, var(--primario-vis) 16%, transparent); color: var(--primario-vis); }

    .exito-detalle { display: grid; gap: 10px; text-align: left; margin: 0 0 16px; padding: 0 4px; font-size: 14px; }
    .exito-detalle div { display: flex; align-items: center; gap: 12px; color: var(--texto-suave); }
    .exito-detalle .icon { color: var(--acento-vis); }
    .exito-detalle span { margin-left: auto; color: var(--linea); font-weight: 600; text-align: right; }
    .exito-nota { font-size: 12.5px; color: var(--muted); margin: 0 0 18px; line-height: 1.5; }
    .exito-card .btn-block { margin-top: 2px; }

    @media (prefers-reduced-motion: reduce) {
        .exito-overlay, .exito-card, .exito-overlay.visible .escalon, .exito-sello .disco, .exito-sello .tilde { transition: none !important; animation: none !important; opacity: 1; transform: none; stroke-dashoffset: 0; }
        .exito-sello::before, .exito-sello::after { display: none; }
    }
</style>
@endpush

<div class="exito-overlay" id="exitoOverlay" role="dialog" aria-modal="true" aria-labelledby="exitoTitulo" aria-describedby="exitoTexto">
    <canvas class="exito-confeti" id="exitoConfeti" aria-hidden="true"></canvas>
    <div class="exito-card">
        <div class="exito-sello" aria-hidden="true">
            <svg viewBox="0 0 92 92"><circle class="disco" cx="46" cy="46" r="42"/><path class="tilde" d="M28 47 L41 60 L65 34"/></svg>
        </div>

        <h2 id="exitoTitulo" class="escalon" style="--n:0">¡Gracias por elegirnos!</h2>
        <p id="exitoTexto" class="lead escalon" style="--n:1">
            {{ $ok['cliente'] }}, tu {{ ($ok['etiqueta'] ?? '') === 'Cancha' ? 'cancha' : 'cita' }} ha sido <b>reservada exitosamente</b>.
        </p>
        <div class="escalon" style="--n:2">
            <span class="exito-estado {{ $ok['confirmada'] ? '' : 'pendiente' }}">{{ $ok['confirmada'] ? 'Confirmada' : 'Pendiente de confirmación' }}</span>
        </div>

        <div class="exito-radicado escalon" style="--n:3">
            <small>Número de radicado</small>
            <div class="fila">
                <strong id="exitoNumero">{{ $ok['radicado'] }}</strong>
                <button type="button" class="exito-copiar" id="exitoCopiar" aria-label="Copiar número de radicado"><x-icon name="copy" size="15" /> <span>Copiar</span></button>
            </div>
        </div>

        <div class="exito-detalle escalon" style="--n:4">
            <div><x-icon :name="($ok['etiqueta'] ?? '') === 'Cancha' ? 'pitch' : 'sparkles'" size="17" /> {{ $ok['etiqueta'] ?? 'Reserva' }} <span>{{ $ok['recurso'] }}</span></div>
            @if (! empty($ok['profesional']))
                <div><x-icon name="user" size="17" /> Profesional <span>{{ $ok['profesional'] }}</span></div>
            @endif
            <div><x-icon name="calendar" size="17" /> Fecha <span>{{ $ok['fecha'] }}</span></div>
            <div><x-icon name="clock" size="17" /> Horario <span>{{ $ok['hora'] }}</span></div>
        </div>

        <p class="exito-nota escalon" style="--n:5">
            Guarda tu número de radicado: lo necesitarás si quieres consultar o cambiar tu cita.
            @if ($ok['correo'])<br>Te enviamos la confirmación a <b>{{ $ok['correo'] }}</b>.@elseif (! $ok['confirmada'])<br>El negocio confirmará tu cita muy pronto.@endif
        </p>

        <button type="button" class="btn btn-primary btn-block escalon" style="--n:6" id="exitoCerrar"><x-icon name="check" /> Entendido</button>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const overlay = document.getElementById('exitoOverlay');
    const cerrar = document.getElementById('exitoCerrar');
    const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const previo = document.activeElement;
    document.body.style.overflow = 'hidden';

    // Entrada: un frame después de pintar, para que corran las transiciones
    requestAnimationFrame(() => requestAnimationFrame(() => { overlay.classList.add('visible'); cerrar.focus({ preventScroll: true }); }));

    function salir() {
        document.removeEventListener('keydown', teclas);
        overlay.classList.add('saliendo');
        document.body.style.overflow = '';
        setTimeout(() => { overlay.remove(); previo?.focus?.({ preventScroll: true }); }, sinMovimiento ? 0 : 320);
    }
    function teclas(e) {
        if (e.key === 'Escape') salir();
        if (e.key === 'Tab') { e.preventDefault(); cerrar.focus(); }   // el foco se queda dentro del pop-up
    }
    document.addEventListener('keydown', teclas);
    cerrar.addEventListener('click', salir);
    overlay.addEventListener('click', e => { if (e.target === overlay) salir(); });

    // Copiar el radicado
    const copiar = document.getElementById('exitoCopiar');
    copiar.addEventListener('click', async () => {
        const numero = document.getElementById('exitoNumero').textContent.trim();
        try { await navigator.clipboard.writeText(numero); }
        catch (e) {
            const t = document.createElement('textarea'); t.value = numero; document.body.appendChild(t); t.select();
            try { document.execCommand('copy'); } catch (e2) {} t.remove();
        }
        copiar.classList.add('listo');
        copiar.querySelector('span').textContent = '¡Copiado!';
        setTimeout(() => { copiar.classList.remove('listo'); copiar.querySelector('span').textContent = 'Copiar'; }, 1800);
    });

    // Confeti con los colores de la marca
    if (sinMovimiento) return;
    const lienzo = document.getElementById('exitoConfeti');
    const ctx = lienzo.getContext('2d');
    const estilo = getComputedStyle(document.documentElement);
    const colores = [estilo.getPropertyValue('--floodlight'), estilo.getPropertyValue('--turf-claro'), '#ffffff', estilo.getPropertyValue('--acento-vis'), estilo.getPropertyValue('--primario-vis')]
        .map(c => c.trim()).filter(Boolean);
    let ancho, alto;
    function medir() { const r = window.devicePixelRatio || 1; ancho = lienzo.width = innerWidth * r; alto = lienzo.height = innerHeight * r; ctx.setTransform(1, 0, 0, 1, 0, 0); ctx.scale(r, r); }
    medir();

    const piezas = Array.from({ length: 90 }, () => {
        const angulo = -Math.PI / 2 + (Math.random() - 0.5) * 1.5;
        const fuerza = 7 + Math.random() * 9;
        return {
            x: innerWidth / 2, y: innerHeight * 0.34,
            vx: Math.cos(angulo) * fuerza, vy: Math.sin(angulo) * fuerza,
            g: 0.28 + Math.random() * 0.12, r: Math.random() * Math.PI, vr: (Math.random() - 0.5) * 0.35,
            w: 6 + Math.random() * 7, h: 3 + Math.random() * 4, c: colores[Math.floor(Math.random() * colores.length)],
            redonda: Math.random() < 0.3,
        };
    });
    const inicio = performance.now() + 450;   // arranca cuando la tarjeta ya apareció
    function cuadro(t) {
        if (!document.body.contains(lienzo)) return;
        const vida = t - inicio;
        if (vida < 0) return requestAnimationFrame(cuadro);
        ctx.clearRect(0, 0, innerWidth, innerHeight);
        let vivas = 0;
        piezas.forEach(p => {
            p.vy += p.g; p.vx *= 0.992; p.x += p.vx; p.y += p.vy; p.r += p.vr;
            const opacidad = Math.max(0, 1 - vida / 2600);
            if (opacidad <= 0 || p.y > innerHeight + 20) return;
            vivas++;
            ctx.save(); ctx.globalAlpha = opacidad; ctx.translate(p.x, p.y); ctx.rotate(p.r); ctx.fillStyle = p.c;
            if (p.redonda) { ctx.beginPath(); ctx.arc(0, 0, p.h, 0, 6.3); ctx.fill(); } else ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
        });
        if (vivas) requestAnimationFrame(cuadro); else lienzo.remove();
    }
    requestAnimationFrame(cuadro);
    addEventListener('resize', medir);
})();
</script>
@endpush
