{{-- Comprobante de salida (tarjeta emergente). Recibe $recibo (datos guardados en la sesión al registrar la salida) y $negocio. --}}
@push('styles')
<style>
    .rec-overlay {
        position: fixed; inset: 0; z-index: 200; display: grid; place-items: center; padding: 20px;
        background: color-mix(in srgb, var(--fondo) 60%, rgba(0,0,0,0.5));
        backdrop-filter: blur(10px) saturate(130%); -webkit-backdrop-filter: blur(10px) saturate(130%);
        opacity: 0; transition: opacity 0.4s ease; overflow-y: auto;
    }
    .rec-overlay.visible { opacity: 1; }
    .rec-card {
        position: relative; width: 100%; max-width: 420px; margin: auto; padding: 30px 28px 24px; text-align: center;
        border-radius: 28px; background: var(--superficie);
        box-shadow: 0 40px 90px -30px rgba(0,0,0,0.65), 0 0 0 1px color-mix(in srgb, var(--linea) 8%, transparent);
        opacity: 0; transform: translateY(28px) scale(0.94);
        transition: opacity 0.45s ease, transform 0.6s cubic-bezier(0.2, 0.9, 0.3, 1.25);
    }
    .rec-overlay.visible .rec-card { opacity: 1; transform: none; }
    .rec-overlay.saliendo { opacity: 0; }
    .rec-overlay.saliendo .rec-card { opacity: 0; transform: translateY(16px) scale(0.96); transition-duration: 0.28s; transition-timing-function: ease-in; }

    .rec-card .escalon { opacity: 0; transform: translateY(12px); }
    .rec-overlay.visible .escalon { animation: recSube 0.55s cubic-bezier(0.2, 0.8, 0.3, 1) forwards; animation-delay: calc(var(--n) * 80ms + 300ms); }
    @keyframes recSube { to { opacity: 1; transform: none; } }

    .rec-negocio { font-size: 11.5px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }

    /* Sello de verificación que se dibuja solo */
    .rec-sello { position: relative; width: 78px; height: 78px; margin: 0 auto 14px; }
    .rec-sello::before, .rec-sello::after { content: ""; position: absolute; inset: 0; border-radius: 50%; background: var(--floodlight); opacity: 0; }
    .rec-overlay.visible .rec-sello::before { animation: recOnda 1.8s ease-out 0.4s 2; }
    .rec-overlay.visible .rec-sello::after { animation: recOnda 1.8s ease-out 1s 2; }
    @keyframes recOnda { 0% { transform: scale(0.8); opacity: 0.35; } 100% { transform: scale(1.8); opacity: 0; } }
    .rec-sello svg { position: relative; width: 100%; height: 100%; overflow: visible; }
    .rec-sello .disco { fill: var(--floodlight); transform-origin: 50% 50%; transform: scale(0); }
    .rec-overlay.visible .rec-sello .disco { animation: recDisco 0.6s cubic-bezier(0.3, 1.5, 0.5, 1) 0.15s forwards; }
    @keyframes recDisco { to { transform: scale(1); } }
    .rec-sello .tilde { fill: none; stroke: var(--texto-acento); stroke-width: 6; stroke-linecap: round; stroke-linejoin: round; stroke-dasharray: 50; stroke-dashoffset: 50; }
    .rec-overlay.visible .rec-sello .tilde { animation: recTilde 0.5s ease-out 0.55s forwards; }
    @keyframes recTilde { to { stroke-dashoffset: 0; } }

    .rec-card h2 { font-size: clamp(22px, 6vw, 27px); font-weight: 800; letter-spacing: -0.6px; line-height: 1.15; margin: 0 0 12px; }
    .rec-placa {
        display: inline-flex; align-items: center; gap: 10px; padding: 8px 16px 8px 12px; border-radius: 12px;
        font-family: var(--fuente-titulo); font-size: 22px; font-weight: 800; letter-spacing: 3px;
        background: color-mix(in srgb, var(--acento-vis) 14%, transparent); border: 1px solid color-mix(in srgb, var(--acento-vis) 32%, transparent);
    }
    .rec-placa .icon { color: var(--acento-vis); }
    .rec-tipo { display: block; margin-top: 8px; font-size: 13px; color: var(--muted); }

    .rec-total {
        margin: 20px 0 16px; padding: 16px 18px 14px; border-radius: 18px; text-align: left;
        background: linear-gradient(160deg, color-mix(in srgb, var(--floodlight) 14%, var(--superficie-alta)), var(--superficie-alta));
    }
    .rec-total small { display: block; font-size: 11px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; color: var(--muted); margin-bottom: 2px; }
    .rec-total strong { display: block; font-family: var(--fuente-titulo); font-size: clamp(34px, 9vw, 42px); font-weight: 800; letter-spacing: -1px; line-height: 1.1; }

    .rec-detalle { display: grid; gap: 11px; text-align: left; margin: 0 0 18px; padding: 0 4px; font-size: 14px; }
    .rec-detalle div { display: flex; align-items: center; gap: 12px; color: var(--texto-suave); }
    .rec-detalle .icon { color: var(--acento-vis); flex: none; }
    .rec-detalle span { margin-left: auto; color: var(--linea); font-weight: 600; text-align: right; }

    .rec-botones { display: grid; grid-template-columns: auto 1fr; gap: 10px; }

    @media (prefers-reduced-motion: reduce) {
        .rec-overlay, .rec-card, .rec-overlay.visible .escalon { transition: none !important; animation: none !important; opacity: 1; transform: none; }
        .rec-sello .disco { transform: none; animation: none !important; } .rec-sello .tilde { stroke-dashoffset: 0; animation: none !important; }
        .rec-sello::before, .rec-sello::after { display: none; }
    }

    /* Al imprimir sale solo el comprobante, en blanco y negro, como un ticket */
    @media print {
        body * { visibility: hidden !important; }
        .rec-overlay, .rec-overlay * { visibility: visible !important; }
        .rec-overlay { position: absolute; inset: 0 auto auto 0; width: 100%; display: block; padding: 0; background: #fff; backdrop-filter: none; opacity: 1; overflow: visible; }
        .rec-card { max-width: 340px; margin: 0 auto; padding: 18px 8px; border-radius: 0; box-shadow: none; opacity: 1; transform: none;
            --superficie: #fff; --superficie-alta: #f2f2f2; --linea: #000; --texto-suave: #222; --muted: #555; --acento-vis: #000; --floodlight: #000; --texto-acento: #fff; background: #fff; color: #000; }
        .rec-card .escalon { opacity: 1; transform: none; animation: none; }
        .rec-sello::before, .rec-sello::after { display: none; }
        .rec-total { background: #f2f2f2; border: 1px solid #000; }
        .rec-botones { display: none !important; }
    }
</style>
@endpush

<div class="rec-overlay" id="recOverlay" role="dialog" aria-modal="true" aria-labelledby="recTitulo">
    <div class="rec-card">
        <div class="rec-negocio escalon" style="--n:0">{{ $negocio->name }}</div>

        <div class="rec-sello" aria-hidden="true">
            <svg viewBox="0 0 92 92"><circle class="disco" cx="46" cy="46" r="42"/><path class="tilde" d="M28 47 L41 60 L65 34"/></svg>
        </div>

        <h2 id="recTitulo" class="escalon" style="--n:1">Salida registrada</h2>
        <div class="escalon" style="--n:2">
            <span class="rec-placa"><x-icon :name="\App\Models\TipoVehiculo::iconoPara($recibo['vehiculo'])" size="22" /> {{ $recibo['placa'] }}</span>
            <span class="rec-tipo">{{ $recibo['vehiculo'] }}</span>
        </div>

        <div class="rec-total escalon" style="--n:3">
            <small>Total a cobrar</small>
            <strong>$ {{ number_format($recibo['valor'], 0, ',', '.') }}</strong>
        </div>

        <div class="rec-detalle escalon" style="--n:4">
            <div><x-icon name="log-in" size="17" /> Entrada <span>{{ $recibo['entrada'] }}</span></div>
            <div><x-icon name="log-out" size="17" /> Salida <span>{{ $recibo['salida'] }}</span></div>
            <div><x-icon name="clock" size="17" /> Tiempo <span>{{ $recibo['duracion'] }}</span></div>
            <div><x-icon name="tag" size="17" /> Tarifa <span>{{ $recibo['tarifa'] }}</span></div>
            @if (! empty($recibo['atendio']))
                <div><x-icon name="user" size="17" /> Atendió <span>{{ $recibo['atendio'] }}</span></div>
            @endif
        </div>

        <div class="rec-botones escalon" style="--n:5">
            <button type="button" class="btn btn-secondary" id="recImprimir"><x-icon name="receipt" size="16" /> Imprimir</button>
            <button type="button" class="btn btn-primary" id="recCerrar"><x-icon name="check" /> Entendido</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const overlay = document.getElementById('recOverlay');
    const cerrar = document.getElementById('recCerrar');
    const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.body.style.overflow = 'hidden';

    // Entrada: un frame después de pintar, para que corran las transiciones
    requestAnimationFrame(() => requestAnimationFrame(() => { overlay.classList.add('visible'); cerrar.focus({ preventScroll: true }); }));

    function salir() {
        document.removeEventListener('keydown', teclas);
        overlay.classList.add('saliendo');
        document.body.style.overflow = '';
        setTimeout(() => overlay.remove(), sinMovimiento ? 0 : 320);
    }
    function teclas(e) {
        if (e.key === 'Escape') salir();
        if (e.key === 'Tab') {   // el foco se queda dentro del comprobante
            const f = [...overlay.querySelectorAll('button')];
            const i = f.indexOf(document.activeElement);
            e.preventDefault();
            f[(i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
        }
    }
    document.addEventListener('keydown', teclas);
    cerrar.addEventListener('click', salir);
    overlay.addEventListener('click', e => { if (e.target === overlay) salir(); });
    document.getElementById('recImprimir').addEventListener('click', () => window.print());
})();
</script>
@endpush
