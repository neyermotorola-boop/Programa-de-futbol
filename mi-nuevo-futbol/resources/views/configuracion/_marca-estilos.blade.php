@push('styles')
<style>
    .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    .grid-2 { gap: 12px; }
    /* ----- Marca ----- */
    .marca-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .logo-fila { display: flex; align-items: center; gap: 12px; }
    .logo-preview { width: 62px; height: 62px; border-radius: 14px; background: #fff; display: grid; place-items: center; overflow: hidden; flex: none; border: 1px solid var(--borde-fuerte); color: #8a948f; }
    .logo-preview img { width: 100%; height: 100%; object-fit: contain; }
    .logo-fila input[type=file] { display: none; }
    .logo-fila .ayuda { margin: 3px 0 0; font-size: 11.5px; }
    .colores { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .color-campo { display: flex; align-items: center; gap: 10px; background: var(--superficie-alta); border: 1px solid var(--borde-fuerte); border-radius: var(--radio-sm); padding: 5px 12px 5px 5px; margin: 0; cursor: pointer; }
    .color-campo input[type=color] { width: 38px; height: 32px; border: 0; padding: 0; background: none; cursor: pointer; border-radius: 8px; min-height: 0; }
    .color-campo input[type=color]::-webkit-color-swatch-wrapper { padding: 0; }
    .color-campo input[type=color]::-webkit-color-swatch { border: 0; border-radius: 8px; }
    .color-campo b { display: block; font-size: 13px; color: var(--linea); line-height: 1.2; }
    .color-campo code { font-size: 12px; color: var(--muted); text-transform: uppercase; }
    .paletas { display: flex; gap: 6px; flex-wrap: wrap; margin: 10px 0 14px; align-items: center; }
    .paletas > span { font-size: 12px; color: var(--muted); font-weight: 600; margin-right: 2px; }
    .paleta { display: inline-flex; align-items: center; gap: 7px; padding: 4px 10px 4px 4px; border-radius: 999px; background: var(--superficie-alta); border: 1px solid var(--borde); color: var(--texto-suave); font-size: 12px; font-weight: 600; cursor: pointer; font-family: var(--fuente); }
    .paleta:hover { border-color: var(--borde-fuerte); color: var(--linea); }
    .paleta i { width: 18px; height: 18px; border-radius: 50%; display: inline-block; box-shadow: inset 0 0 0 2px rgba(255,255,255,0.2); }

    .vista-previa { border-radius: var(--radio); overflow: hidden; border: 1px solid var(--borde-fuerte); background: #0c1210; box-shadow: var(--sombra); color: #f2f2e9; }
    .vp-barra { display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: color-mix(in srgb, var(--vp-primario) 60%, #08090a); }
    .vp-logo { width: 34px; height: 34px; border-radius: 10px; background: #fff; display: grid; place-items: center; overflow: hidden; color: var(--vp-primario); flex: none; }
    .vp-logo img { width: 100%; height: 100%; object-fit: contain; }
    .vp-nombre { font-family: var(--fuente-titulo); font-weight: 800; font-size: 14px; }
    .vp-eslogan { font-size: 11px; color: color-mix(in srgb, var(--vp-acento) 70%, white); font-weight: 600; }
    .vp-cuerpo { padding: 20px 16px; text-align: center; background: radial-gradient(ellipse at top, color-mix(in srgb, var(--vp-primario) 45%, transparent), transparent 70%); }
    .vp-cuerpo h3 { font-size: 19px; margin-bottom: 4px; }
    .vp-cuerpo p { color: #a9b5ad; font-size: 12px; margin: 0 0 14px; }
    .vp-horas { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 12px; }
    .vp-horas span { padding: 7px 0; border-radius: 8px; font-size: 12px; font-weight: 700; border: 1px solid rgba(242,242,233,0.17); background: rgba(242,242,233,0.07); }
    .vp-horas span.sel { background: var(--vp-acento); border-color: var(--vp-acento); color: var(--vp-texto); }
    .vp-boton { padding: 11px; border-radius: 10px; background: var(--vp-acento); color: var(--vp-texto); font-weight: 700; font-size: 13px; }
    .vp-etiqueta { text-align: center; font-size: 12px; color: #a9b5ad; padding: 9px; border-top: 1px solid rgba(242,242,233,0.1); }
    @media (max-width: 1024px) { .marca-grid { grid-template-columns: 1fr; } }
    @media (max-width: 720px) { .grid-3 { grid-template-columns: 1fr; gap: 0; } .colores { grid-template-columns: 1fr; } }
</style>
@endpush
