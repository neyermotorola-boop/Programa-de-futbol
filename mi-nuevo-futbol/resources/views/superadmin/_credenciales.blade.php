{{-- Ventana con la contraseña nueva de un cliente. Solo aparece justo después de restablecerla: no se guarda en claro. --}}
@if (session('credenciales'))
    @php $c = session('credenciales'); @endphp
    @push('styles')
    <style>
        .clave-overlay { position: fixed; inset: 0; z-index: 200; display: grid; place-items: center; padding: 20px; overflow-y: auto;
            background: color-mix(in srgb, var(--fondo) 55%, rgba(0,0,0,0.55)); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            opacity: 0; transition: opacity 0.3s ease; }
        .clave-overlay.visible { opacity: 1; }
        .clave-card { width: 100%; max-width: 460px; margin: auto; padding: 30px 28px 24px; border-radius: 26px; background: var(--superficie); text-align: center;
            box-shadow: 0 40px 90px -30px rgba(0,0,0,0.6), 0 0 0 1px color-mix(in srgb, var(--linea) 8%, transparent);
            transform: translateY(22px) scale(0.96); opacity: 0; transition: transform 0.5s cubic-bezier(0.2, 0.9, 0.3, 1.15), opacity 0.35s ease; }
        .clave-overlay.visible .clave-card { transform: none; opacity: 1; }
        .clave-ico { width: 64px; height: 64px; border-radius: 20px; margin: 0 auto 16px; display: grid; place-items: center; background: color-mix(in srgb, var(--acento-vis) 16%, transparent); color: var(--acento-vis); }
        .clave-card h2 { font-size: 22px; margin-bottom: 6px; }
        .clave-card .lead { color: var(--muted); font-size: 14px; line-height: 1.5; margin: 0 0 20px; }
        .clave-datos { text-align: left; display: grid; gap: 10px; margin-bottom: 14px; }
        .clave-dato { padding: 12px 14px; border-radius: 14px; background: var(--superficie-alta); }
        .clave-dato small { display: block; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--muted); margin-bottom: 3px; }
        .clave-dato .fila { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .clave-dato b { font-size: 15px; font-weight: 600; word-break: break-all; }
        .clave-dato.secreta b { font-family: ui-monospace, 'Cascadia Mono', Consolas, monospace; font-size: 22px; letter-spacing: 2px; font-weight: 700; }
        .clave-copiar { flex: none; display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; border-radius: 9px; border: 0; cursor: pointer; font-family: var(--fuente); font-size: 12.5px; font-weight: 600; background: color-mix(in srgb, var(--linea) 9%, transparent); color: var(--texto-suave); }
        .clave-copiar:hover { background: color-mix(in srgb, var(--linea) 15%, transparent); color: var(--linea); }
        .clave-copiar.listo { color: var(--primario-vis); }
        .clave-aviso { display: flex; gap: 10px; align-items: flex-start; text-align: left; padding: 11px 13px; border-radius: 12px; font-size: 12.5px; line-height: 1.45; color: var(--texto-suave); background: color-mix(in srgb, #fab219 14%, transparent); margin-bottom: 18px; }
        .clave-aviso .icon { margin-top: 1px; flex: none; color: var(--acento-vis); }
        .clave-botones { display: grid; gap: 10px; }
        @media (prefers-reduced-motion: reduce) { .clave-overlay, .clave-card { transition: none; } }
    </style>
    @endpush

    <div class="clave-overlay" id="claveModal" role="dialog" aria-modal="true" aria-labelledby="claveTitulo">
        <div class="clave-card">
            <div class="clave-ico"><x-icon name="key" size="28" /></div>
            <h2 id="claveTitulo">Contraseña restablecida</h2>
            <p class="lead">Estos son los nuevos datos de acceso de <b>{{ $c['nombre'] }}</b>.</p>

            <div class="clave-datos">
                <div class="clave-dato"><small>Correo de acceso</small>
                    <div class="fila"><b id="claveCorreo">{{ $c['email'] }}</b></div></div>
                <div class="clave-dato secreta"><small>Contraseña nueva</small>
                    <div class="fila"><b id="claveValor">{{ $c['clave'] }}</b>
                        <button type="button" class="clave-copiar" data-copiar="#claveValor"><x-icon name="copy" size="15" /> <span>Copiar</span></button></div></div>
                <div class="clave-dato"><small>Dirección de ingreso</small>
                    <div class="fila"><b id="claveAcceso">{{ $c['acceso'] }}</b></div></div>
            </div>

            <div class="clave-aviso"><x-icon name="alert-circle" size="16" /> <span>Cópiala ahora: por seguridad <b>no se vuelve a mostrar</b>. Compártela con tu cliente. Su sesión anterior se cerró.</span></div>

            <div class="clave-botones">
                <button type="button" class="btn btn-primary" id="claveTodo"><x-icon name="copy" size="17" /> <span>Copiar todo</span></button>
                <button type="button" class="btn btn-secondary" id="claveCerrar">Listo</button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (() => {
        const modal = document.getElementById('claveModal');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => requestAnimationFrame(() => { modal.classList.add('visible'); document.getElementById('claveCerrar').focus({ preventScroll: true }); }));

        const cerrar = () => { modal.classList.remove('visible'); document.body.style.overflow = ''; setTimeout(() => modal.remove(), 320); };
        document.getElementById('claveCerrar').addEventListener('click', cerrar);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrar(); });

        async function copiar(texto, boton) {
            try { await navigator.clipboard.writeText(texto); }
            catch (e) { const t = document.createElement('textarea'); t.value = texto; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); } catch (e2) {} t.remove(); }
            const etiqueta = boton.querySelector('span'), antes = etiqueta.textContent;
            boton.classList.add('listo'); etiqueta.textContent = '¡Copiado!';
            setTimeout(() => { boton.classList.remove('listo'); etiqueta.textContent = antes; }, 1800);
        }
        modal.querySelectorAll('[data-copiar]').forEach(b => b.addEventListener('click', () => copiar(document.querySelector(b.dataset.copiar).textContent.trim(), b)));
        document.getElementById('claveTodo').addEventListener('click', e => copiar(
            'Ingreso: ' + document.getElementById('claveAcceso').textContent.trim() + '\nUsuario: ' + document.getElementById('claveCorreo').textContent.trim() + '\nContraseña: ' + document.getElementById('claveValor').textContent.trim(),
            e.currentTarget));
    })();
    </script>
    @endpush
@endif
