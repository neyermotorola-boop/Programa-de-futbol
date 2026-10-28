/**
 * Visor 3D de una cancha (Three.js r128, global THREE).
 *
 *   const visor = Cancha3D.crear(elemento, datos);
 *   visor.actualizar({ ... });      // cambia la cancha mostrada
 *   visor.setNoche(true);           // modo noche: enciende las luces si la cancha tiene
 *   visor.setVistaFoto(true);       // muestra la foto real encima del modelo (si la cancha tiene foto)
 *
 * datos:
 *   deporte      futbol | tenis | padel | voleibol | generico      (cómo se dibuja la cancha)
 *   formato      futbol5 | futbol7 | futbol11 | individual | dobles | 6x6 | playa
 *   estilo       cesped | arena | dura | arcilla                    (textura del piso)
 *   color        color del piso, '#RRGGBB'
 *   iluminacion  torres de luz en las esquinas
 *   techada      techo sobre columnas
 *   foto         URL de la foto real (opcional)
 *   fotoModo     'entorno' (la foto rodea la cancha) | 'piso' (la foto cubre la cancha)
 */
(function () {
    // Largo y ancho (en unidades del modelo) según el deporte y su formato
    const DIM = {
        futbol: { futbol5: [8, 5], futbol7: [10, 6.5], futbol11: [12.5, 8], _: [8, 5] },
        tenis: { individual: [11, 4.6], dobles: [11, 5.5], _: [11, 5.5] },
        padel: { dobles: [9, 4.5], _: [9, 4.5] },
        voleibol: { '6x6': [9, 4.5], playa: [8, 4], _: [9, 4.5] },
        generico: { _: [8, 4.5] },
    };
    const dimensiones = (deporte, formato) => { const d = DIM[deporte] || DIM.generico; return d[formato] || d._; };

    /** Margen alrededor de la cancha hasta la malla o las paredes: [largo extra, ancho extra]. */
    const MARCO = { futbol: [1.6, 1.6], tenis: [3.2, 2.6], padel: [0.2, 0.2], voleibol: [2, 2], generico: [1.6, 1.6] };

    // ---------- Colores ----------
    const rgb = h => [1, 3, 5].map(i => parseInt(h.substr(i, 2), 16));
    const hex = c => '#' + c.map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('');
    const mezclar = (a, b, t) => { const x = rgb(a), y = rgb(b); return hex(x.map((v, i) => v * (1 - t) + y[i] * t)); };

    // ---------- Piso: color, textura y líneas del deporte ----------
    function texturaCampo(datos, largo, ancho) {
        const W = 1024, H = Math.round(1024 * ancho / largo);
        const c = document.createElement('canvas');
        c.width = W; c.height = H;
        const g = c.getContext('2d');
        const base = datos.color || '#2F8F3F';

        g.fillStyle = base; g.fillRect(0, 0, W, H);
        let ruido = 0.04;
        if (datos.estilo === 'cesped') {
            g.fillStyle = mezclar(base, '#000000', 0.1);
            for (let i = 0; i < 10; i += 2) g.fillRect(i * W / 10, 0, W / 10, H);
            ruido = 0.07;
        } else if (datos.estilo === 'arena') ruido = 0.2;
        else if (datos.estilo === 'arcilla') ruido = 0.26;
        else { // superficie dura: un leve degradado de luz, casi lisa
            const l = g.createLinearGradient(0, 0, W, H);
            l.addColorStop(0, 'rgba(255,255,255,0.07)'); l.addColorStop(1, 'rgba(0,0,0,0.1)');
            g.fillStyle = l; g.fillRect(0, 0, W, H);
        }
        for (let i = 0; i < W * H * ruido * 0.05; i++) {
            g.fillStyle = Math.random() > 0.5 ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.09)';
            g.fillRect(Math.random() * W, Math.random() * H, 2, 2);
        }

        // Líneas de juego
        const px = W / largo, m = 0.25 * px, L = W - 2 * m, A = H - 2 * m;
        g.strokeStyle = '#ffffff'; g.fillStyle = '#ffffff'; g.lineWidth = Math.max(3, px * 0.06); g.globalAlpha = 0.92;
        const trazo = (x1, y1, x2, y2) => { g.beginPath(); g.moveTo(x1, y1); g.lineTo(x2, y2); g.stroke(); };
        g.strokeRect(m, m, L, A);

        switch (datos.deporte) {
            case 'tenis': {
                const pasillo = A * 0.125, sx = L * 0.269;
                trazo(m, m + pasillo, W - m, m + pasillo); trazo(m, H - m - pasillo, W - m, H - m - pasillo);   // laterales de individuales
                trazo(W / 2 - sx, m + pasillo, W / 2 - sx, H - m - pasillo); trazo(W / 2 + sx, m + pasillo, W / 2 + sx, H - m - pasillo);   // líneas de saque
                trazo(W / 2 - sx, H / 2, W / 2 + sx, H / 2);   // línea central de saque
                trazo(m, H / 2, m + px * 0.3, H / 2); trazo(W - m, H / 2, W - m - px * 0.3, H / 2);
                g.globalAlpha = 0.5; trazo(W / 2, m, W / 2, H - m);   // bajo la red
                break;
            }
            case 'padel': {
                const sx = L * 0.3475;
                trazo(W / 2 - sx, m, W / 2 - sx, H - m); trazo(W / 2 + sx, m, W / 2 + sx, H - m);
                trazo(W / 2 - sx, H / 2, W / 2 + sx, H / 2);
                g.globalAlpha = 0.5; trazo(W / 2, m, W / 2, H - m);
                break;
            }
            case 'voleibol': {
                trazo(W / 2, m, W / 2, H - m);
                if (datos.formato !== 'playa') { const a = L * 0.1667; trazo(W / 2 - a, m, W / 2 - a, H - m); trazo(W / 2 + a, m, W / 2 + a, H - m); }
                break;
            }
            case 'generico': trazo(W / 2, m, W / 2, H - m); break;
            default: { // fútbol
                trazo(W / 2, m, W / 2, H - m);
                g.beginPath(); g.arc(W / 2, H / 2, ancho * 0.16 * px, 0, Math.PI * 2); g.stroke();
                g.beginPath(); g.arc(W / 2, H / 2, px * 0.08, 0, Math.PI * 2); g.fill();
                const areaL = largo * 0.14 * px, areaA = ancho * 0.5 * px, chicaL = largo * 0.055 * px, chicaA = ancho * 0.26 * px;
                [m, W - m].forEach((x, i) => {
                    const dir = i === 0 ? 1 : -1;
                    g.strokeRect(Math.min(x, x + dir * areaL), H / 2 - areaA / 2, areaL, areaA);
                    g.strokeRect(Math.min(x, x + dir * chicaL), H / 2 - chicaA / 2, chicaL, chicaA);
                    g.beginPath(); g.arc(x + dir * areaL * 0.72, H / 2, px * 0.06, 0, Math.PI * 2); g.fill();
                });
            }
        }

        const tex = new THREE.CanvasTexture(c);
        tex.anisotropy = 8;
        return tex;
    }

    // ---------- Piezas ----------
    function material(color, extra) {
        return new THREE.MeshStandardMaterial(Object.assign({ color, roughness: 0.6, metalness: 0.1 }, extra || {}));
    }
    function caja(w, h, d, mat) {
        const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat);
        m.castShadow = true; m.receiveShadow = true;
        return m;
    }

    function arco(x, ancho, dir) {
        const g = new THREE.Group();
        const blanco = material('#ffffff', { roughness: 0.3 });
        const anchoArco = ancho * 0.24, alto = 0.55, fondo = 0.4, t = 0.06;
        [-1, 1].forEach(lado => { const poste = caja(t, alto, t, blanco); poste.position.set(0, alto / 2, lado * anchoArco / 2); g.add(poste); });
        const travesano = caja(t, t, anchoArco + t, blanco); travesano.position.set(0, alto, 0); g.add(travesano);
        const red = new THREE.Mesh(new THREE.BoxGeometry(fondo, alto, anchoArco),
            new THREE.MeshBasicMaterial({ color: '#ffffff', wireframe: true, transparent: true, opacity: 0.35 }));
        red.position.set(dir * fondo / 2, alto / 2, 0); g.add(red);
        g.position.x = x;
        return g;
    }

    /** Red de tenis, pádel o voleibol: postes, tela cuadriculada y cinta superior. */
    function redDeJuego(grupo, ancho, alturaRed, alturaPoste, antenas) {
        const metal = material('#b8c0c6', { metalness: 0.6, roughness: 0.35 });
        const largoRed = ancho + 0.3;
        const c = document.createElement('canvas'); c.width = 128; c.height = 64;
        const g = c.getContext('2d');
        g.strokeStyle = 'rgba(255,255,255,0.8)'; g.lineWidth = 2;
        for (let x = 0; x <= 128; x += 8) { g.beginPath(); g.moveTo(x, 0); g.lineTo(x, 64); g.stroke(); }
        for (let y = 0; y <= 64; y += 8) { g.beginPath(); g.moveTo(0, y); g.lineTo(128, y); g.stroke(); }
        g.fillStyle = '#ffffff'; g.fillRect(0, 0, 128, 7);
        const tex = new THREE.CanvasTexture(c);
        tex.wrapS = THREE.RepeatWrapping; tex.repeat.set(largoRed * 2.2, 1);
        const tela = new THREE.Mesh(new THREE.PlaneGeometry(largoRed, alturaRed),
            new THREE.MeshBasicMaterial({ map: tex, transparent: true, side: THREE.DoubleSide, depthWrite: false }));
        tela.rotation.y = Math.PI / 2; tela.position.y = alturaPoste - alturaRed / 2;
        grupo.add(tela);
        [-1, 1].forEach(lado => {
            const poste = new THREE.Mesh(new THREE.CylinderGeometry(0.035, 0.045, alturaPoste, 10), metal);
            poste.position.set(0, alturaPoste / 2, lado * (ancho / 2 + 0.15)); poste.castShadow = true;
            grupo.add(poste);
            if (antenas) {
                const antena = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.012, alturaRed + 0.4, 6), material('#e53935'));
                antena.position.set(0, alturaPoste - alturaRed / 2 + 0.2 - 0.03, lado * ancho / 2);
                grupo.add(antena);
            }
        });
    }

    /** Malla perimetral con postes y red translúcida. */
    function cercado(grupo, largoM, anchoM, alto, metal) {
        const red = new THREE.MeshBasicMaterial({ color: '#c9d2cc', transparent: true, opacity: 0.12, side: THREE.DoubleSide, depthWrite: false });
        [[0, -anchoM / 2, largoM, 0], [0, anchoM / 2, largoM, 0], [-largoM / 2, 0, anchoM, Math.PI / 2], [largoM / 2, 0, anchoM, Math.PI / 2]]
            .forEach(([x, z, w, rot]) => {
                const panel = new THREE.Mesh(new THREE.PlaneGeometry(w, alto), red);
                panel.position.set(x, alto / 2, z); panel.rotation.y = rot; grupo.add(panel);
                const baranda = caja(rot ? 0.04 : w, 0.04, rot ? w : 0.04, metal);
                baranda.position.set(x, alto, z); grupo.add(baranda);
            });
        for (let i = 0; i <= 8; i++) {
            const x = -largoM / 2 + i * largoM / 8;
            [-anchoM / 2, anchoM / 2].forEach(z => { const p = caja(0.05, alto, 0.05, metal); p.position.set(x, alto / 2, z); grupo.add(p); });
        }
    }

    /** Paredes de pádel: vidrio en los fondos y los extremos de los laterales, malla en el centro. */
    function paredesPadel(grupo, largo, ancho, alto, metal) {
        const vidrio = new THREE.MeshStandardMaterial({ color: '#bfe5ff', transparent: true, opacity: 0.2, roughness: 0.05, metalness: 0.1, side: THREE.DoubleSide, depthWrite: false });
        const malla = new THREE.MeshBasicMaterial({ color: '#e8eef2', transparent: true, opacity: 0.16, side: THREE.DoubleSide, depthWrite: false });
        const tramo = (x, z, w, rot, mat) => {
            const p = new THREE.Mesh(new THREE.PlaneGeometry(w, alto), mat);
            p.position.set(x, alto / 2, z); p.rotation.y = rot; grupo.add(p);
            const riel = caja(rot ? 0.05 : w, 0.05, rot ? w : 0.05, metal); riel.position.set(x, alto, z); grupo.add(riel);
        };
        tramo(-largo / 2, 0, ancho, Math.PI / 2, vidrio); tramo(largo / 2, 0, ancho, Math.PI / 2, vidrio);   // fondos
        const vid = largo * 0.27, med = largo - 2 * vid;
        [-1, 1].forEach(lado => {
            const z = lado * ancho / 2;
            tramo(-largo / 2 + vid / 2, z, vid, 0, vidrio); tramo(largo / 2 - vid / 2, z, vid, 0, vidrio);
            tramo(0, z, med, 0, malla);
        });
        [-largo / 2, -largo / 2 + vid, largo / 2 - vid, largo / 2].forEach(x => [-1, 1].forEach(l => {
            const p = caja(0.06, alto, 0.06, metal); p.position.set(x, alto / 2, l * ancho / 2); grupo.add(p);
        }));
    }

    function crear(contenedor, datosIniciales) {
        if (!window.THREE) {
            contenedor.innerHTML = '<div class="visor-error">No se pudo cargar el visor 3D.</div>';
            return { actualizar() {}, setNoche() {}, setVistaFoto() {} };
        }

        const renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        contenedor.appendChild(renderer.domElement);

        const escena = new THREE.Scene();
        const camara = new THREE.PerspectiveCamera(38, 1, 0.1, 400);
        const cargador = new THREE.TextureLoader();

        const hemisferio = new THREE.HemisphereLight('#dff1ff', '#2b3a2f', 0.7);
        const sol = new THREE.DirectionalLight('#fff4e0', 0.75);
        sol.position.set(8, 14, 6);
        sol.castShadow = true;
        sol.shadow.mapSize.set(1024, 1024);
        Object.assign(sol.shadow.camera, { left: -12, right: 12, top: 12, bottom: -12 });
        escena.add(hemisferio, sol);

        let grupo = null, reflectores = [], focos = [], muroFoto = null, datos = null, noche = false, vistaFoto = false, capaFoto = null, version = 0;
        let angulo = 0.6, arrastrando = false, ultimoX = 0, distancia = 16, alturaCam = 0.62, miraY = -0.6;

        function limpiar() {
            if (!grupo) return;
            escena.remove(grupo);
            grupo.traverse(o => {
                if (o.geometry) o.geometry.dispose();
                if (o.material) { if (o.material.map) o.material.map.dispose(); o.material.dispose(); }
            });
            reflectores.forEach(r => { escena.remove(r); if (r.target) escena.remove(r.target); });
            reflectores = []; focos = []; muroFoto = null;
        }

        function construir() {
            limpiar();
            const miVersion = ++version;
            grupo = new THREE.Group();
            const deporte = datos.deporte in DIM ? datos.deporte : 'generico';
            const [largo, ancho] = dimensiones(deporte, datos.formato);
            const [mL, mA] = MARCO[deporte];
            const largoMarco = largo + mL, anchoMarco = ancho + mA;
            distancia = largo * 1.85;
            // Con foto de entorno la cámara baja y se abre el ángulo, para que se vea la foto alrededor
            const conEntorno = !!datos.foto && datos.fotoModo !== 'piso';
            alturaCam = conEntorno ? 0.3 : 0.62; miraY = conEntorno ? 0.9 : -0.6;
            camara.fov = conEntorno ? 50 : 38; camara.updateProjectionMatrix();
            const metal = material('#9aa3a8', { metalness: 0.6, roughness: 0.4 });

            // Piso alrededor y cancha
            const entorno = new THREE.Mesh(new THREE.PlaneGeometry(largoMarco + 2.4, anchoMarco + 2.4),
                material(mezclar(datos.color || '#2F8F3F', '#000000', 0.55), { roughness: 0.95 }));
            entorno.rotation.x = -Math.PI / 2; entorno.position.y = -0.01; entorno.receiveShadow = true;
            grupo.add(entorno);

            const campo = new THREE.Mesh(new THREE.PlaneGeometry(largo, ancho),
                new THREE.MeshStandardMaterial({ map: texturaCampo(datos, largo, ancho), roughness: datos.estilo === 'dura' ? 0.45 : 0.9 }));
            campo.rotation.x = -Math.PI / 2; campo.receiveShadow = true;
            grupo.add(campo);

            // Foto real como piso: cubre la cancha (ideal con una foto aérea)
            if (datos.foto && datos.fotoModo === 'piso') {
                cargador.load(datos.foto, tex => {
                    if (miVersion !== version) { tex.dispose(); return; }
                    const aspecto = tex.image.width / tex.image.height, cancha = largo / ancho;
                    if (aspecto > cancha) { tex.repeat.set(cancha / aspecto, 1); tex.offset.set((1 - cancha / aspecto) / 2, 0); }
                    else { tex.repeat.set(1, aspecto / cancha); tex.offset.set(0, (1 - aspecto / cancha) / 2); }
                    tex.anisotropy = 8;
                    campo.material.map.dispose(); campo.material.map = tex; campo.material.needsUpdate = true;
                });
            }

            // Foto real como entorno: un muro circular con la foto rodea la cancha
            if (datos.foto && datos.fotoModo !== 'piso') {
                cargador.load(datos.foto, tex => {
                    if (miVersion !== version) { tex.dispose(); return; }
                    const R = distancia * 1.3, H = R * 0.5;
                    const veces = Math.max(1, Math.round((2 * Math.PI * R) / (H * (tex.image.width / tex.image.height))));
                    tex.wrapS = THREE.MirroredRepeatWrapping; tex.repeat.set(veces, 1);
                    const muro = new THREE.Mesh(new THREE.CylinderGeometry(R, R, H, 64, 1, true),
                        new THREE.MeshBasicMaterial({ map: tex, side: THREE.BackSide, fog: false }));
                    muro.position.y = H / 2 - 1.2;
                    grupo.add(muro); muroFoto = muro;
                    aplicarAmbiente();
                });
            }

            // Elementos propios de cada deporte
            if (deporte === 'futbol') {
                grupo.add(arco(-largo / 2 + 0.25, ancho, -1), arco(largo / 2 - 0.25, ancho, 1));
                cercado(grupo, largoMarco, anchoMarco, 1.6, metal);
            } else if (deporte === 'tenis') {
                redDeJuego(grupo, ancho, 0.42, 0.46, false);
                cercado(grupo, largoMarco, anchoMarco, 1.5, metal);
            } else if (deporte === 'padel') {
                redDeJuego(grupo, ancho, 0.38, 0.42, false);
                paredesPadel(grupo, largo, ancho, 1.35, metal);
            } else if (deporte === 'voleibol') {
                redDeJuego(grupo, ancho, 0.6, 1.25, true);
            } else {
                cercado(grupo, largoMarco, anchoMarco, 1.0, metal);
            }

            // Torres de iluminación en las esquinas
            if (datos.iluminacion) {
                const altoTorre = 4.2;
                [[-1, -1], [1, -1], [-1, 1], [1, 1]].forEach(([sx, sz]) => {
                    const x = sx * (largoMarco / 2 + 0.4), z = sz * (anchoMarco / 2 + 0.4);
                    const torre = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.1, altoTorre, 10), metal);
                    torre.position.set(x, altoTorre / 2, z); torre.castShadow = true;
                    grupo.add(torre);

                    const lampara = new THREE.Mesh(new THREE.BoxGeometry(0.7, 0.4, 0.12),
                        new THREE.MeshStandardMaterial({ color: '#dfe6ea', emissive: '#fff6d8', emissiveIntensity: 0 }));
                    lampara.position.set(x, altoTorre, z);
                    lampara.lookAt(0, 0, 0);
                    grupo.add(lampara);
                    focos.push(lampara);

                    const luz = new THREE.SpotLight('#fff3d6', 0, 30, Math.PI / 4.2, 0.5, 1.2);
                    luz.position.set(x, altoTorre, z);
                    luz.target.position.set(sx * -1.2, 0, sz * -1.2);
                    luz.castShadow = true;
                    luz.shadow.mapSize.set(512, 512);
                    escena.add(luz, luz.target);
                    reflectores.push(luz);
                });
            }

            // Techo sobre columnas
            if (datos.techada) {
                const altoTecho = deporte === 'voleibol' ? 4.2 : 3.4;
                const techo = new THREE.Mesh(new THREE.BoxGeometry(largoMarco + 0.6, 0.08, anchoMarco + 0.6),
                    material('#cfd8dc', { transparent: true, opacity: 0.38, metalness: 0.3, depthWrite: false }));
                techo.position.y = altoTecho;
                grupo.add(techo);
                [[-1, -1], [1, -1], [-1, 1], [1, 1], [0, -1], [0, 1]].forEach(([sx, sz]) => {
                    const c = caja(0.14, altoTecho, 0.14, metal);
                    c.position.set(sx * (largoMarco / 2 + 0.2), altoTecho / 2, sz * (anchoMarco / 2 + 0.2));
                    grupo.add(c);
                });
                if (!datos.iluminacion) {
                    // Las canchas techadas sin torres igual tienen luces en el techo
                    [-largo / 4, largo / 4].forEach(x => {
                        const luz = new THREE.PointLight('#fff3d6', 0, 14, 1.5);
                        luz.position.set(x, altoTecho - 0.2, 0);
                        escena.add(luz);
                        reflectores.push(luz);
                    });
                }
            }

            escena.add(grupo);
            aplicarAmbiente();
            asegurarCapaFoto();
        }

        function aplicarAmbiente() {
            const conLuz = reflectores.length > 0;
            if (noche) {
                escena.background = new THREE.Color('#060b16');
                escena.fog = new THREE.Fog('#060b16', distancia * 1.2, distancia * 3);
                hemisferio.intensity = conLuz ? 0.18 : 0.08;
                hemisferio.color.set('#7f95c9');
                sol.intensity = conLuz ? 0.05 : 0.15;
                sol.color.set('#8fa6d9');
            } else {
                escena.background = new THREE.Color('#a9d4ef');
                escena.fog = new THREE.Fog('#a9d4ef', distancia * 1.6, distancia * 4);
                hemisferio.intensity = 0.7;
                hemisferio.color.set('#dff1ff');
                sol.intensity = 0.75;
                sol.color.set('#fff4e0');
            }
            reflectores.forEach(l => { l.intensity = noche ? (l.isSpotLight ? 1.15 : 0.9) : 0; });
            focos.forEach(f => { f.material.emissiveIntensity = noche ? 1.6 : 0; });
            // El muro con la foto no recibe luz: de noche se oscurece a mano
            if (muroFoto) muroFoto.material.color.set(noche ? '#4b5575' : '#ffffff');
        }

        /** Capa con la foto real por encima del modelo (se muestra con setVistaFoto). */
        function asegurarCapaFoto() {
            if (!datos.foto) { if (capaFoto) capaFoto.style.display = 'none'; vistaFoto = false; return; }
            if (!capaFoto) {
                capaFoto = document.createElement('div');
                capaFoto.style.cssText = 'position:absolute;inset:0;opacity:0;pointer-events:none;transition:opacity .45s ease;background-size:cover;background-position:center;';
                contenedor.insertBefore(capaFoto, contenedor.firstChild);
            }
            capaFoto.style.display = '';
            capaFoto.style.backgroundImage = `url("${datos.foto}")`;
            capaFoto.style.opacity = vistaFoto ? 1 : 0;
        }

        function redimensionar() {
            const w = contenedor.clientWidth, h = contenedor.clientHeight;
            if (!w || !h) return;
            renderer.setSize(w, h, false);
            camara.aspect = w / h;
            camara.updateProjectionMatrix();
        }

        // Arrastrar para girar la cancha (mouse o dedo)
        const lienzo = renderer.domElement;
        lienzo.style.touchAction = 'pan-y';
        lienzo.addEventListener('pointerdown', e => { arrastrando = true; ultimoX = e.clientX; lienzo.setPointerCapture(e.pointerId); });
        lienzo.addEventListener('pointermove', e => {
            if (!arrastrando) return;
            angulo -= (e.clientX - ultimoX) * 0.008;
            ultimoX = e.clientX;
        });
        ['pointerup', 'pointercancel'].forEach(t => lienzo.addEventListener(t, () => { arrastrando = false; }));

        const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let visible = true;
        new IntersectionObserver(([e]) => { visible = e.isIntersecting; }).observe(contenedor);
        new ResizeObserver(redimensionar).observe(contenedor);

        function animar() {
            requestAnimationFrame(animar);
            if (!visible) return;
            if (!arrastrando && !sinMovimiento) angulo += 0.0022;
            camara.position.set(Math.cos(angulo) * distancia, distancia * alturaCam, Math.sin(angulo) * distancia * 0.8);
            camara.lookAt(0, miraY, 0);
            renderer.render(escena, camara);
        }

        datos = Object.assign({ deporte: 'futbol', formato: 'futbol5', estilo: 'cesped', color: '#2F8F3F', iluminacion: true, techada: false, foto: null, fotoModo: 'entorno' }, datosIniciales);
        redimensionar();
        construir();
        animar();

        return {
            actualizar(nuevos) { datos = Object.assign({}, datos, nuevos); construir(); },
            setNoche(valor) { noche = !!valor; aplicarAmbiente(); },
            setVistaFoto(valor) { vistaFoto = !!valor && !!datos.foto; asegurarCapaFoto(); },
        };
    }

    window.Cancha3D = { crear };
})();
