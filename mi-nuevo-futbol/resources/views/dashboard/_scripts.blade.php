@push('scripts')
<script>
(() => {
    // ---------- Gráfica / tabla: cada gráfica tiene su equivalente en tabla ----------
    document.querySelectorAll('[data-grafica]').forEach(tarjeta => {
        tarjeta.querySelectorAll('[data-vista]').forEach(boton => boton.addEventListener('click', () => {
            tarjeta.classList.toggle('ver-tabla', boton.dataset.vista === 'tabla');
            tarjeta.querySelectorAll('[data-vista]').forEach(b => b.setAttribute('aria-pressed', b === boton));
        }));
    });

    // ---------- Tooltip: título y filas "color|nombre|valor" (todo con textContent) ----------
    const tip = document.getElementById('tip');
    const colores = { ok: 'var(--ok)', espera: 'var(--espera)', mal: 'var(--mal)' };

    function mostrar(el, x, y) {
        tip.replaceChildren();
        const titulo = document.createElement('div');
        titulo.className = 't';
        titulo.textContent = el.dataset.t;
        tip.appendChild(titulo);
        (el.dataset.l || '').split(';').filter(Boolean).forEach(fila => {
            const [color, nombre, valor] = fila.split('|');
            const f = document.createElement('div');
            f.className = 'f';
            if (color) { const marca = document.createElement('i'); marca.style.setProperty('--c', colores[color] || color); f.appendChild(marca); }
            f.appendChild(document.createTextNode(nombre));
            const v = document.createElement('b');
            v.textContent = valor;
            f.appendChild(v);
            tip.appendChild(f);
        });
        tip.classList.add('visible');
        colocar(x, y);
    }
    function colocar(x, y) {
        const a = tip.offsetWidth, h = tip.offsetHeight, m = 14;
        tip.style.left = Math.max(8, Math.min(x + m, innerWidth - a - 8)) + 'px';
        tip.style.top = Math.max(8, Math.min(y - h - m < 8 ? y + m : y - h - m, innerHeight - h - 8)) + 'px';
    }
    function ocultar() { tip.classList.remove('visible'); }

    document.querySelectorAll('[data-t]').forEach(el => {
        el.addEventListener('pointermove', e => mostrar(el, e.clientX, e.clientY));
        el.addEventListener('pointerleave', ocultar);
        el.addEventListener('focus', () => { const r = el.getBoundingClientRect(); mostrar(el, r.left + r.width / 2, r.top); });
        el.addEventListener('blur', ocultar);
    });
    addEventListener('scroll', ocultar, { passive: true });
})();
</script>
@endpush
