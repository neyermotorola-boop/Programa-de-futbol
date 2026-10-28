    // ---------- Marca: vista previa en vivo ----------
    const vista = document.getElementById('vistaPrevia');
    const colorP = document.getElementById('colorPrimario');
    const colorA = document.getElementById('colorAcento');
    const textoSobre = hex => {
        const [r, g, b] = [1, 3, 5].map(i => parseInt(hex.substr(i, 2), 16));
        return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.6 ? '#1a1204' : '#ffffff';
    };
    function pintarColores() {
        vista.style.setProperty('--vp-primario', colorP.value);
        vista.style.setProperty('--vp-acento', colorA.value);
        vista.style.setProperty('--vp-texto', textoSobre(colorA.value));
        document.getElementById('codPrimario').textContent = colorP.value;
        document.getElementById('codAcento').textContent = colorA.value;
    }
    [colorP, colorA].forEach(i => i.addEventListener('input', pintarColores));
    document.querySelectorAll('.paleta').forEach(b => b.addEventListener('click', () => {
        colorP.value = b.dataset.primario; colorA.value = b.dataset.acento; pintarColores();
    }));
    pintarColores();
    document.getElementById('name').addEventListener('input', e => { document.getElementById('vpNombre').textContent = e.target.value || 'Tu negocio'; });
    document.getElementById('eslogan').addEventListener('input', e => { const vp = document.getElementById('vpEslogan'); vp.textContent = e.target.value || vp.dataset.vacio; });
    document.getElementById('inputLogo').addEventListener('change', e => {
        const archivo = e.target.files[0];
        if (!archivo) return;
        const url = URL.createObjectURL(archivo);
        document.getElementById('logoPreview').innerHTML = `<img src="${url}" alt="Nuevo logo">`;
        document.getElementById('vpLogo').innerHTML = `<img src="${url}" alt="">`;
    });
