/**
 * Vista previa de un diseño de uñas sobre un dedo de ejemplo.
 *
 * El cliente sube la foto de su diseño; se recorta con la forma de la uña elegida
 * (almendra, cuadrada, redonda o ballerina) sobre un dedo dibujado en SVG, con brillo y
 * cutícula. Todo ocurre en el navegador: la foto solo viaja al servidor al reservar.
 *
 *   const diseno = DisenoUnas.iniciar(elementoRaiz);
 *   diseno.limpiar();   // quita la foto (p. ej. al elegir un servicio que no admite diseño)
 *
 * La raíz debe contener: input[type=file][data-archivo], input[type=hidden][data-forma-valor],
 * [data-lienzo], [data-formas], [data-pieles], [data-zoom-grupo] con input[type=range][data-zoom],
 * button[data-subir] y button[data-quitar].
 */
(function () {
    // Contorno de cada forma: base (cutícula) en y=108, el borde libre sube hasta la punta
    const FORMAS = {
        almendra:  { nombre: 'Almendra',  d: 'M34 108 C34 70 38 40 52 20 Q60 10 68 20 C82 40 86 70 86 108 Z' },
        cuadrada:  { nombre: 'Cuadrada',  d: 'M34 108 V36 Q34 30 40 30 H80 Q86 30 86 36 V108 Z' },
        redonda:   { nombre: 'Redonda',   d: 'M34 108 V62 C34 40 46 32 60 32 C74 32 86 40 86 62 V108 Z' },
        ballerina: { nombre: 'Ballerina', d: 'M34 108 L40 48 L47 18 H73 L80 48 L86 108 Z' },
    };
    // [claro, sombra]
    const PIELES = {
        clara:  { nombre: 'Clara',  c: ['#F7DCCB', '#E8BDA3'] },
        media:  { nombre: 'Media',  c: ['#E9BC9C', '#D29673'] },
        morena: { nombre: 'Morena', c: ['#BE8159', '#996042'] },
        oscura: { nombre: 'Oscura', c: ['#80503A', '#5B3724'] },
    };
    // Caja de la uña (coincide con los contornos de arriba): x 34..86, y 10..108
    const CAJA = { x: 34, y: 10, w: 52, h: 98 };
    const NS = 'http://www.w3.org/2000/svg';
    let contador = 0;

    function el(nombre, atributos, hijos) {
        const nodo = document.createElementNS(NS, nombre);
        Object.entries(atributos || {}).forEach(([k, v]) => nodo.setAttribute(k, v));
        (hijos || []).forEach(h => nodo.appendChild(h));
        return nodo;
    }

    /** Reduce la foto a máx. 1400 px para que suba rápido (las fotos de celular pesan varios MB). */
    async function reducir(archivo) {
        try {
            const bmp = await createImageBitmap(archivo);
            const escala = Math.min(1, 1400 / Math.max(bmp.width, bmp.height));
            const lienzo = document.createElement('canvas');
            lienzo.width = Math.round(bmp.width * escala);
            lienzo.height = Math.round(bmp.height * escala);
            lienzo.getContext('2d').drawImage(bmp, 0, 0, lienzo.width, lienzo.height);
            const blob = await new Promise(ok => lienzo.toBlob(ok, 'image/jpeg', 0.86));
            if (!blob) throw new Error('sin blob');
            return {
                archivo: new File([blob], 'diseno.jpg', { type: 'image/jpeg' }),
                url: lienzo.toDataURL('image/jpeg', 0.86),
                ancho: lienzo.width,
                alto: lienzo.height,
            };
        } catch (e) {
            // Navegador sin soporte: se usa la foto original tal cual
            const url = await new Promise((ok, mal) => { const r = new FileReader(); r.onload = () => ok(r.result); r.onerror = mal; r.readAsDataURL(archivo); });
            const medidas = await new Promise(ok => { const i = new Image(); i.onload = () => ok({ ancho: i.naturalWidth, alto: i.naturalHeight }); i.src = url; });
            return { archivo, url, ...medidas };
        }
    }

    function iniciar(raiz) {
        const id = 'una' + (++contador);
        const entrada = raiz.querySelector('[data-archivo]');
        const campoForma = raiz.querySelector('[data-forma-valor]');
        const zoomGrupo = raiz.querySelector('[data-zoom-grupo]');
        const zoom = raiz.querySelector('[data-zoom]');
        const botonSubir = raiz.querySelector('[data-subir]');
        const botonQuitar = raiz.querySelector('[data-quitar]');
        const contFormas = raiz.querySelector('[data-formas]');
        const contPieles = raiz.querySelector('[data-pieles]');
        const lienzo = raiz.querySelector('[data-lienzo]');

        let forma = campoForma.value in FORMAS ? campoForma.value : 'almendra';
        let piel = 'clara';
        let foto = null;          // { url, ancho, alto }
        let pan = { x: 0, y: 0 };

        // ---------- SVG del dedo (se construye una vez y se actualiza) ----------
        const degradadoPiel = el('linearGradient', { id: id + 'piel', x1: 0, x2: 1, y1: 0, y2: 0 });
        const paradas = [0, 0.32, 0.68, 1].map(o => el('stop', { offset: o }));
        paradas.forEach(p => degradadoPiel.appendChild(p));
        const degradadoBorde = el('linearGradient', { id: id + 'fade', x1: 0, x2: 0, y1: 0, y2: 1 }, [
            el('stop', { offset: 0.45, 'stop-color': '#fff' }), el('stop', { offset: 1, 'stop-color': '#000' }),
        ]);
        const brillo = el('linearGradient', { id: id + 'brillo', x1: 0, y1: 0, x2: 1, y2: 1 }, [
            el('stop', { offset: 0, 'stop-color': '#fff', 'stop-opacity': 0.55 }),
            el('stop', { offset: 0.38, 'stop-color': '#fff', 'stop-opacity': 0.08 }),
            el('stop', { offset: 1, 'stop-color': '#000', 'stop-opacity': 0.14 }),
        ]);
        const clip = el('clipPath', { id: id + 'clip' });
        const contornoClip = el('path');
        clip.appendChild(contornoClip);
        const mascara = el('mask', { id: id + 'mask' }, [el('rect', { x: 0, y: 0, width: 120, height: 150, fill: 'url(#' + id + 'fade)' })]);
        const sombra = el('filter', { id: id + 'sombra', x: '-20%', y: '-20%', width: '140%', height: '150%' }, [
            el('feDropShadow', { dx: 0, dy: 2, stdDeviation: 2, 'flood-color': '#000', 'flood-opacity': 0.28 }),
        ]);

        const dedo = el('path', { d: 'M20 150 V90 C20 62 38 46 60 46 C82 46 100 62 100 90 V150 Z', fill: 'url(#' + id + 'piel)' });
        const pliegueIzq = el('path', { d: 'M34 108 Q30 98 36 90', fill: 'none', stroke: '#000', 'stroke-opacity': 0.09, 'stroke-width': 1.4, 'stroke-linecap': 'round' });
        const pliegueDer = el('path', { d: 'M86 108 Q90 98 84 90', fill: 'none', stroke: '#000', 'stroke-opacity': 0.09, 'stroke-width': 1.4, 'stroke-linecap': 'round' });
        const fondoUna = el('rect', { x: CAJA.x, y: 0, width: CAJA.w, height: 120, fill: '#F2C4CF' });
        const imagen = el('image', { preserveAspectRatio: 'none', x: CAJA.x, y: CAJA.y, width: CAJA.w, height: CAJA.h });
        imagen.style.display = 'none';
        const capaBrillo = el('path', { fill: 'url(#' + id + 'brillo)' });
        const reflejo = el('ellipse', { cx: 48, cy: 52, rx: 4.5, ry: 20, fill: '#fff', 'fill-opacity': 0.22, transform: 'rotate(-6 48 52)' });
        const grupoUna = el('g', { 'clip-path': 'url(#' + id + 'clip)' }, [fondoUna, imagen, capaBrillo, reflejo]);
        const borde = el('path', { fill: 'none', stroke: '#000', 'stroke-opacity': 0.22, 'stroke-width': 0.9 });
        const cuticula = el('path', { d: 'M33 109 Q60 95 87 109 Q60 118 33 109 Z' });

        const svg = el('svg', { viewBox: '6 0 108 150', role: 'img', 'aria-label': 'Vista previa de tu diseño en una uña' }, [
            el('defs', {}, [degradadoPiel, degradadoBorde, brillo, clip, mascara, sombra]),
            el('g', { mask: 'url(#' + id + 'mask)' }, [dedo, pliegueIzq, pliegueDer]),
            el('g', { filter: 'url(#' + id + 'sombra)' }, [grupoUna, borde]),
            cuticula,
        ]);
        svg.style.cssText = 'width:100%;height:100%;display:block;touch-action:none;';
        lienzo.appendChild(svg);

        // ---------- Dibujo ----------
        function ajustarImagen() {
            if (!foto) return;
            const z = (+zoom.value || 100) / 100;
            const escala = Math.max(CAJA.w / foto.ancho, CAJA.h / foto.alto) * z;
            const w = foto.ancho * escala, h = foto.alto * escala;
            const maxX = (w - CAJA.w) / 2, maxY = (h - CAJA.h) / 2;
            pan.x = Math.max(-maxX, Math.min(maxX, pan.x));
            pan.y = Math.max(-maxY, Math.min(maxY, pan.y));
            imagen.setAttribute('x', CAJA.x + (CAJA.w - w) / 2 + pan.x);
            imagen.setAttribute('y', CAJA.y + (CAJA.h - h) / 2 + pan.y);
            imagen.setAttribute('width', w);
            imagen.setAttribute('height', h);
        }

        function dibujar() {
            const d = FORMAS[forma].d;
            [contornoClip, capaBrillo, borde].forEach(p => p.setAttribute('d', d));
            const [claro, sombraPiel] = PIELES[piel].c;
            [sombraPiel, claro, claro, sombraPiel].forEach((c, i) => paradas[i].setAttribute('stop-color', c));
            cuticula.setAttribute('fill', claro);
            campoForma.value = forma;
            contFormas.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', b.dataset.k === forma));
            contPieles.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', b.dataset.k === piel));
            ajustarImagen();
        }

        // ---------- Controles: formas y tonos de piel ----------
        Object.entries(FORMAS).forEach(([k, f]) => {
            const b = document.createElement('button');
            b.type = 'button'; b.dataset.k = k; b.title = f.nombre; b.setAttribute('aria-label', 'Forma ' + f.nombre);
            const mini = el('svg', { viewBox: '28 6 64 108', width: 12, height: 21 }, [el('path', { d: f.d, fill: 'currentColor' })]);
            b.appendChild(mini);
            b.insertAdjacentHTML('beforeend', '<span>' + f.nombre + '</span>');
            b.addEventListener('click', () => { forma = k; dibujar(); });
            contFormas.appendChild(b);
        });
        Object.entries(PIELES).forEach(([k, p]) => {
            const b = document.createElement('button');
            b.type = 'button'; b.dataset.k = k; b.title = 'Tono ' + p.nombre.toLowerCase(); b.setAttribute('aria-label', 'Tono de piel ' + p.nombre.toLowerCase());
            b.style.background = 'linear-gradient(135deg,' + p.c[0] + ',' + p.c[1] + ')';
            b.addEventListener('click', () => { piel = k; dibujar(); });
            contPieles.appendChild(b);
        });

        // ---------- Foto ----------
        botonSubir.addEventListener('click', () => entrada.click());
        lienzo.addEventListener('click', () => { if (!foto) entrada.click(); });

        entrada.addEventListener('change', async () => {
            const original = entrada.files[0];
            if (!original) return;
            botonSubir.disabled = true;
            const r = await reducir(original);
            botonSubir.disabled = false;
            try {
                const lote = new DataTransfer();
                lote.items.add(r.archivo);
                entrada.files = lote.files;     // lo que se envía es la versión reducida
            } catch (e) { /* se envía el original */ }
            foto = { url: r.url, ancho: r.ancho, alto: r.alto };
            pan = { x: 0, y: 0 };
            zoom.value = 100;
            imagen.setAttribute('href', r.url);
            imagen.style.display = '';
            zoomGrupo.hidden = false;
            botonQuitar.hidden = false;
            botonSubir.lastChild.textContent = ' Cambiar foto';
            lienzo.classList.add('con-foto');
            dibujar();
        });

        function limpiar() {
            entrada.value = '';
            foto = null;
            imagen.style.display = 'none';
            zoomGrupo.hidden = true;
            botonQuitar.hidden = true;
            botonSubir.lastChild.textContent = ' Subir foto';
            lienzo.classList.remove('con-foto');
            dibujar();
        }
        botonQuitar.addEventListener('click', limpiar);
        zoom.addEventListener('input', ajustarImagen);

        // Arrastrar para encuadrar el diseño dentro de la uña
        let arrastre = null;
        svg.addEventListener('pointerdown', e => {
            if (!foto) return;
            arrastre = { x: e.clientX, y: e.clientY, px: pan.x, py: pan.y };
            svg.setPointerCapture(e.pointerId);
            lienzo.classList.add('arrastrando');
        });
        svg.addEventListener('pointermove', e => {
            if (!arrastre) return;
            const unidades = 108 / svg.getBoundingClientRect().width;
            pan.x = arrastre.px + (e.clientX - arrastre.x) * unidades;
            pan.y = arrastre.py + (e.clientY - arrastre.y) * unidades;
            ajustarImagen();
        });
        ['pointerup', 'pointercancel'].forEach(t => svg.addEventListener(t, () => { arrastre = null; lienzo.classList.remove('arrastrando'); }));

        dibujar();
        return { limpiar };
    }

    window.DisenoUnas = { iniciar };
})();
