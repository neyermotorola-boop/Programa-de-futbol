/**
 * Código QR de la página de reservas + afiche listo para imprimir.
 *
 * Usa qrcode-generator (public/js/qrcode.min.js) para calcular el código y dibuja todo
 * en un <canvas>: lo que se ve en pantalla es exactamente lo que se descarga o imprime.
 *
 *   QrReserva.iniciar(raiz, { nombre, eslogan, logo, primario, acento, tipo, direccion, telefono, agendas: [{ nombre, url }] })
 */
(function () {
    const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
    const rgb = hex => [1, 3, 5].map(i => parseInt(hex.substr(i, 2), 16));
    const hex = c => '#' + c.map(v => clamp(Math.round(v), 0, 255).toString(16).padStart(2, '0')).join('');
    const luminancia = h => {
        const [r, g, b] = rgb(h).map(v => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; });
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    };
    const contraste = (a, b) => { const [x, y] = [luminancia(a), luminancia(b)].sort((p, q) => q - p); return (x + 0.05) / (y + 0.05); };
    const textoSobre = fondo => contraste(fondo, '#14110a') >= contraste(fondo, '#ffffff') ? '#14110a' : '#ffffff';

    /** El color de la marca, oscurecido lo justo para que el QR se lea bien sobre blanco. */
    function tinta(primario) {
        let c = rgb(primario);
        for (let i = 0; i < 20 && contraste(hex(c), '#ffffff') < 9; i++) c = c.map(v => v * 0.88);
        return hex(c);
    }

    const cargarImagen = src => new Promise(ok => {
        if (!src) return ok(null);
        const img = new Image();
        img.onload = () => ok(img); img.onerror = () => ok(null);
        img.src = src;
    });

    function rect(ctx, x, y, w, h, r) { ctx.beginPath(); ctx.roundRect(x, y, w, h, r); }

    /** Dibuja el QR: módulos redondeados y marcas de esquina propias. */
    function dibujarQR(ctx, qr, x, y, tam, color) {
        const n = qr.getModuleCount(), m = tam / n;
        const enMarca = (r, c) => (r < 7 && c < 7) || (r < 7 && c >= n - 7) || (r >= n - 7 && c < 7);
        ctx.fillStyle = color;
        for (let r = 0; r < n; r++) for (let c = 0; c < n; c++) {
            if (!qr.isDark(r, c) || enMarca(r, c)) continue;
            rect(ctx, x + c * m + 0.4, y + r * m + 0.4, m - 0.8, m - 0.8, m * 0.34); ctx.fill();
        }
        [[0, 0], [0, n - 7], [n - 7, 0]].forEach(([r, c]) => {
            const px = x + c * m, py = y + r * m;
            ctx.fillStyle = color; rect(ctx, px, py, 7 * m, 7 * m, 2.1 * m); ctx.fill();
            ctx.fillStyle = '#fff'; rect(ctx, px + m, py + m, 5 * m, 5 * m, 1.4 * m); ctx.fill();
            ctx.fillStyle = color; rect(ctx, px + 2 * m, py + 2 * m, 3 * m, 3 * m, 0.95 * m); ctx.fill();
        });
    }

    /** Nivel de corrección H (30 %): permite poner el logo al centro sin perder la lectura. */
    function crearQR(texto) {
        const qr = qrcode(0, 'H');
        qr.addData(texto, 'Byte');
        qr.make();
        return qr;
    }

    function logoAlCentro(ctx, logo, cx, cy, lado) {
        if (!logo) return;
        const pad = lado * 0.12;
        ctx.fillStyle = '#fff'; rect(ctx, cx - lado / 2, cy - lado / 2, lado, lado, lado * 0.24); ctx.fill();
        ctx.save(); rect(ctx, cx - lado / 2 + pad, cy - lado / 2 + pad, lado - 2 * pad, lado - 2 * pad, lado * 0.16); ctx.clip();
        const k = Math.min((lado - 2 * pad) / logo.width, (lado - 2 * pad) / logo.height);
        ctx.drawImage(logo, cx - logo.width * k / 2, cy - logo.height * k / 2, logo.width * k, logo.height * k);
        ctx.restore();
    }

    function ajustar(ctx, texto, anchoMax) {
        if (ctx.measureText(texto).width <= anchoMax) return texto;
        while (texto.length > 4 && ctx.measureText(texto + '…').width > anchoMax) texto = texto.slice(0, -1);
        return texto + '…';
    }

    /** Solo el código (para pegarlo en un diseño propio): 1024 × 1024, fondo blanco. */
    async function dibujarSoloQR(canvas, cfg, url) {
        const logo = await cargarImagen(cfg.logo), S = 1024, borde = 72;
        canvas.width = canvas.height = S;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, S, S);
        const qr = crearQR(url);
        dibujarQR(ctx, qr, borde, borde, S - 2 * borde, tinta(cfg.primario));
        logoAlCentro(ctx, logo, S / 2, S / 2, (S - 2 * borde) * 0.2);
    }

    /** Afiche vertical 1080 × 1600 con la marca del negocio. */
    async function dibujarAfiche(canvas, cfg, url) {
        const W = 1080, H = 1600;
        canvas.width = W; canvas.height = H;
        const ctx = canvas.getContext('2d');
        const logo = await cargarImagen(cfg.logo);
        try { await Promise.all(['800 60px "Plus Jakarta Sans"', '500 30px "Inter"', '700 28px "Inter"'].map(f => document.fonts.load(f))); } catch (e) {}
        const titulo = '"Plus Jakarta Sans", "Inter", system-ui, sans-serif', cuerpo = '"Inter", system-ui, sans-serif';
        const fondoBanda = cfg.primario, tBanda = textoSobre(fondoBanda), ink = tinta(cfg.primario);

        // Fondo
        ctx.fillStyle = '#f6f4ef'; ctx.fillRect(0, 0, W, H);

        // Banda superior con la marca
        const g = ctx.createLinearGradient(0, 0, W, 360);
        g.addColorStop(0, fondoBanda); g.addColorStop(1, hex(rgb(fondoBanda).map(v => v * 0.78)));
        ctx.fillStyle = g; ctx.fillRect(0, 0, W, 360);
        ctx.fillStyle = cfg.acento; ctx.fillRect(0, 360, W, 10);

        let xTexto = 90;
        if (logo) {
            ctx.fillStyle = '#fff'; rect(ctx, 90, 110, 140, 140, 34); ctx.fill();
            ctx.save(); rect(ctx, 100, 120, 120, 120, 26); ctx.clip();
            const k = Math.min(120 / logo.width, 120 / logo.height);
            ctx.drawImage(logo, 160 - logo.width * k / 2, 180 - logo.height * k / 2, logo.width * k, logo.height * k);
            ctx.restore();
            xTexto = 270;
        }
        ctx.fillStyle = tBanda; ctx.textBaseline = 'alphabetic';
        ctx.font = `800 ${cfg.nombre.length > 22 ? 56 : 68}px ${titulo}`;
        ctx.fillText(ajustar(ctx, cfg.nombre, W - xTexto - 70), xTexto, logo ? 192 : 200);
        if (cfg.eslogan) {
            ctx.globalAlpha = 0.85; ctx.font = `500 32px ${cuerpo}`;
            ctx.fillText(ajustar(ctx, cfg.eslogan, W - xTexto - 70), xTexto, logo ? 246 : 256); ctx.globalAlpha = 1;
        }

        // Título
        ctx.textAlign = 'center'; ctx.fillStyle = '#16201a';
        ctx.font = `800 92px ${titulo}`;
        ctx.fillText(cfg.tipo === 'salon' ? 'Reserva tu cita' : 'Reserva tu cancha', W / 2, 520);
        ctx.fillStyle = '#56655c'; ctx.font = `500 38px ${cuerpo}`;
        ctx.fillText('Escanea el código con la cámara de tu celular', W / 2, 584);

        // Tarjeta del QR
        const T = 700, tx = (W - T) / 2, ty = 640;
        ctx.save(); ctx.shadowColor = 'rgba(20,30,24,0.22)'; ctx.shadowBlur = 60; ctx.shadowOffsetY = 24;
        ctx.fillStyle = '#fff'; rect(ctx, tx, ty, T, T, 56); ctx.fill(); ctx.restore();
        const pad = 56, qr = crearQR(url);
        dibujarQR(ctx, qr, tx + pad, ty + pad, T - 2 * pad, ink);
        logoAlCentro(ctx, logo, W / 2, ty + T / 2, (T - 2 * pad) * 0.2);

        // Etiqueta bajo el QR
        const msg = 'Reserva en línea, a cualquier hora';
        ctx.font = `700 32px ${cuerpo}`;
        const aMsg = ctx.measureText(msg).width + 76, yMsg = ty + T + 56;
        ctx.fillStyle = cfg.acento; rect(ctx, (W - aMsg) / 2, yMsg, aMsg, 68, 34); ctx.fill();
        ctx.fillStyle = textoSobre(cfg.acento); ctx.textBaseline = 'middle';
        ctx.fillText(msg, W / 2, yMsg + 36);

        // Pie: enlace y contacto
        ctx.textBaseline = 'alphabetic'; ctx.fillStyle = '#16201a'; ctx.font = `600 32px ${cuerpo}`;
        ctx.fillText(ajustar(ctx, url.replace(/^https?:\/\//, ''), W - 160), W / 2, H - 100);
        const contacto = [cfg.telefono, cfg.direccion].filter(Boolean).join('   ·   ');
        if (contacto) { ctx.fillStyle = '#56655c'; ctx.font = `500 28px ${cuerpo}`; ctx.fillText(ajustar(ctx, contacto, W - 160), W / 2, H - 54); }
    }

    function iniciar(raiz, cfg) {
        const lienzo = raiz.querySelector('[data-qr-lienzo]');
        const campoUrl = raiz.querySelector('[data-qr-url]');
        const chips = raiz.querySelector('[data-qr-agendas]');
        const aviso = raiz.querySelector('[data-qr-aviso]');
        const base = campoUrl.value;
        let temporizador = null;

        const url = () => campoUrl.value.trim() || base;
        const esLocal = u => { try { return ['localhost', '127.0.0.1', '::1'].includes(new URL(u).hostname); } catch (e) { return false; } };

        async function pintar() {
            const u = url();
            aviso.hidden = !esLocal(u);
            await dibujarAfiche(lienzo, cfg, u);
        }

        cfg.agendas.forEach((a, i) => {
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = a.nombre; b.dataset.url = a.url;
            b.setAttribute('aria-pressed', i === 0);
            b.addEventListener('click', () => {
                chips.querySelectorAll('button').forEach(x => x.setAttribute('aria-pressed', x === b));
                campoUrl.value = a.url;
                pintar();
            });
            chips.appendChild(b);
        });
        if (cfg.agendas.length < 2) chips.closest('[data-qr-grupo-agendas]').hidden = true;

        campoUrl.addEventListener('input', () => { clearTimeout(temporizador); temporizador = setTimeout(pintar, 250); });

        const descargar = (canvas, nombre) => canvas.toBlob(blob => {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob); a.download = nombre; document.body.appendChild(a); a.click(); a.remove();
            setTimeout(() => URL.revokeObjectURL(a.href), 4000);
        }, 'image/png');
        const nombreArchivo = sufijo => 'qr-' + cfg.slug + (sufijo ? '-' + sufijo : '') + '.png';

        raiz.querySelector('[data-qr-afiche]').addEventListener('click', () => descargar(lienzo, nombreArchivo('afiche')));
        raiz.querySelector('[data-qr-solo]').addEventListener('click', async () => {
            const c = document.createElement('canvas');
            await dibujarSoloQR(c, cfg, url());
            descargar(c, nombreArchivo(''));
        });
        raiz.querySelector('[data-qr-copiar]').addEventListener('click', async e => {
            const boton = e.currentTarget, texto = boton.querySelector('span');
            try { await navigator.clipboard.writeText(url()); } catch (err) { campoUrl.select(); document.execCommand('copy'); }
            boton.classList.add('listo'); texto.textContent = '¡Copiado!';
            setTimeout(() => { boton.classList.remove('listo'); texto.textContent = 'Copiar enlace'; }, 1800);
        });
        raiz.querySelector('[data-qr-imprimir]').addEventListener('click', () => {
            // Se imprime desde un marco oculto con solo la imagen del afiche (sin abrir ventanas emergentes)
            const marco = document.createElement('iframe');
            marco.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0';
            marco.srcdoc = '<html><head><style>@page{margin:0}html,body{margin:0}img{display:block;width:100%;height:auto}</style></head><body><img src="' + lienzo.toDataURL('image/png') + '"></body></html>';
            marco.onload = () => { marco.contentWindow.focus(); marco.contentWindow.print(); setTimeout(() => marco.remove(), 60000); };
            document.body.appendChild(marco);
        });

        return { pintar };
    }

    window.QrReserva = { iniciar, dibujarAfiche, dibujarSoloQR };
})();
