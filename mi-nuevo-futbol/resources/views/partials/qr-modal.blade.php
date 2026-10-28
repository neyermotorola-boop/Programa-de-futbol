{{-- Ventana con el código QR de la página de reservas. Se abre con cualquier botón [data-abrir-qr]. Recibe $negocio. --}}
@php
    $qrEsSalon = $negocio->tipo_negocio === 'salon';
    $qrBase = route('reservas.index', $negocio->slug);
    $qrAgendas = collect([['nombre' => 'Página principal', 'url' => $qrBase]])
        ->concat($negocio->canchas->where('activa', true)->map(fn ($c) => ['nombre' => $c->nombre, 'url' => $qrBase . '?' . ($qrEsSalon ? 'profesional' : 'cancha') . '=' . $c->id]))
        ->values();
    $qrPaleta = $negocio->paletaCorreo();
    $qrConfig = [
        'slug' => $negocio->slug,
        'nombre' => $negocio->name,
        'eslogan' => $negocio->eslogan,
        'logo' => $negocio->urlLogo(),
        'primario' => $qrPaleta['primario'],
        'acento' => $qrPaleta['acento'],
        'tipo' => $negocio->tipo_negocio,
        'telefono' => $negocio->telefono_contacto,
        'direccion' => $negocio->direccion,
        'agendas' => $qrAgendas,
    ];
@endphp

@push('styles')
<style>
    .qr-overlay { position: fixed; inset: 0; z-index: 150; display: grid; place-items: center; padding: 20px; overflow-y: auto;
        background: color-mix(in srgb, var(--fondo) 55%, rgba(0,0,0,0.55)); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        opacity: 0; transition: opacity 0.3s ease; }
    .qr-overlay.visible { opacity: 1; }
    .qr-panel { position: relative; width: 100%; max-width: 940px; margin: auto; padding: 28px; border-radius: 26px; background: var(--superficie);
        box-shadow: 0 40px 90px -30px rgba(0,0,0,0.6), 0 0 0 1px color-mix(in srgb, var(--linea) 8%, transparent);
        transform: translateY(22px) scale(0.96); opacity: 0; transition: transform 0.5s cubic-bezier(0.2, 0.9, 0.3, 1.15), opacity 0.35s ease; }
    .qr-overlay.visible .qr-panel { transform: none; opacity: 1; }
    .qr-cerrar { position: absolute; top: 14px; right: 14px; width: 38px; height: 38px; border-radius: 11px; border: 0; cursor: pointer; display: grid; place-items: center;
        background: color-mix(in srgb, var(--linea) 8%, transparent); color: var(--muted); transition: background 0.15s, color 0.15s; }
    .qr-cerrar:hover { background: color-mix(in srgb, var(--linea) 15%, transparent); color: var(--linea); }
    .qr-cols { display: grid; grid-template-columns: minmax(0, 330px) minmax(0, 1fr); gap: 30px; align-items: start; }
    .qr-vista canvas { display: block; width: 100%; height: auto; border-radius: 18px; box-shadow: 0 18px 40px -20px rgba(0,0,0,0.5); background: #f6f4ef; }
    .qr-ctrl h2 { font-size: 22px; margin: 4px 0 6px; padding-right: 40px; }
    .qr-ctrl > p { color: var(--muted); font-size: 14px; margin: 0 0 20px; line-height: 1.5; }
    .qr-ctrl .campo { margin-bottom: 16px; }
    .qr-chips { display: flex; flex-wrap: wrap; gap: 6px; max-height: 112px; overflow-y: auto; }
    .qr-chips button { padding: 7px 13px; border-radius: 999px; border: 1px solid transparent; background: var(--superficie-alta); color: var(--texto-suave); font-family: var(--fuente); font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
    .qr-chips button:hover { color: var(--linea); }
    .qr-chips button[aria-pressed="true"] { color: var(--acento-vis); border-color: var(--floodlight); background: color-mix(in srgb, var(--floodlight) 10%, transparent); }
    .qr-ctrl input[type=url] { font-size: 14px; }
    .qr-aviso { display: flex; gap: 10px; align-items: flex-start; padding: 11px 13px; border-radius: 12px; font-size: 12.5px; line-height: 1.45; color: var(--texto-suave); background: color-mix(in srgb, var(--espera, #fab219) 14%, transparent); margin-bottom: 16px; }
    .qr-aviso .icon { margin-top: 1px; flex: none; color: var(--acento-vis); }
    .qr-botones { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .qr-botones .btn { width: 100%; }
    .qr-botones .ancho { grid-column: 1 / -1; }
    .qr-botones .btn.listo { color: var(--primario-vis); }
    @media (max-width: 820px) {
        .qr-cols { grid-template-columns: 1fr; gap: 20px; }
        .qr-vista { max-width: 300px; margin: 0 auto; }
        .qr-panel { padding: 20px 18px; border-radius: 22px; }
        .qr-botones { grid-template-columns: 1fr; }
    }
    @media (prefers-reduced-motion: reduce) { .qr-overlay, .qr-panel { transition: none; } }
</style>
@endpush

<div class="qr-overlay" id="qrModal" hidden role="dialog" aria-modal="true" aria-labelledby="qrTitulo">
    <div class="qr-panel">
        <button type="button" class="qr-cerrar" data-qr-cerrar aria-label="Cerrar"><x-icon name="x" size="18" /></button>
        <div class="qr-cols">
            <div class="qr-vista"><canvas data-qr-lienzo aria-label="Vista previa del afiche con el código QR"></canvas></div>
            <div class="qr-ctrl">
                <h2 id="qrTitulo">Código QR de tu página</h2>
                <p>Imprime el afiche y ponlo en tu local, o compártelo por redes. Tus clientes escanean el código con la cámara del celular y reservan al instante.</p>

                <div class="campo" data-qr-grupo-agendas>
                    <span class="label">¿A dónde lleva el código?</span>
                    <div class="qr-chips" data-qr-agendas></div>
                </div>

                <div class="campo">
                    <label for="qrUrl">Enlace</label>
                    <div class="control"><x-icon name="link" /><input type="url" id="qrUrl" data-qr-url value="{{ $qrAgendas[0]['url'] }}" spellcheck="false"></div>
                </div>

                <div class="qr-aviso" data-qr-aviso hidden>
                    <x-icon name="alert-circle" size="16" />
                    <span>Este enlace solo funciona en este equipo. Cuando publiques tu página con su dominio, el QR usará esa dirección. Para probarlo con tu celular, escribe aquí la dirección de tu computador en la red (por ejemplo <b>http://192.168.1.20:8000/…</b>).</span>
                </div>

                <div class="qr-botones">
                    <button type="button" class="btn btn-primary ancho" data-qr-afiche><x-icon name="download" size="17" /> Descargar afiche</button>
                    <button type="button" class="btn btn-secondary" data-qr-solo><x-icon name="qr" size="17" /> Solo el código</button>
                    <button type="button" class="btn btn-secondary" data-qr-imprimir><x-icon name="receipt" size="17" /> Imprimir</button>
                    <button type="button" class="btn btn-secondary ancho" data-qr-copiar><x-icon name="copy" size="17" /> <span>Copiar enlace</span></button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script src="{{ asset('js/qr-reserva.js') }}"></script>
<script>
(() => {
    const modal = document.getElementById('qrModal');
    let qr = null, ultimoFoco = null;

    function abrir() {
        ultimoFoco = document.activeElement;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        if (!qr) {
            qr = QrReserva.iniciar(modal, @json($qrConfig));
        }
        qr.pintar();
        requestAnimationFrame(() => requestAnimationFrame(() => { modal.classList.add('visible'); modal.querySelector('[data-qr-cerrar]').focus({ preventScroll: true }); }));
        document.addEventListener('keydown', teclas);
    }
    function cerrar() {
        modal.classList.remove('visible');
        document.body.style.overflow = '';
        document.removeEventListener('keydown', teclas);
        setTimeout(() => { modal.hidden = true; ultimoFoco?.focus?.({ preventScroll: true }); }, 320);
    }
    function teclas(e) { if (e.key === 'Escape') cerrar(); }

    document.querySelectorAll('[data-abrir-qr]').forEach(b => b.addEventListener('click', abrir));
    modal.querySelector('[data-qr-cerrar]').addEventListener('click', cerrar);
    modal.addEventListener('click', e => { if (e.target === modal) cerrar(); });
})();
</script>
@endpush
