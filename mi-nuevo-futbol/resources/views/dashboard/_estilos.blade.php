@push('styles')
<style>
    /* Colores de estado: fijos, con ícono y texto siempre al lado (nunca solo color) */
    .dash { --ok: #0ca30c; --espera: #fab219; --mal: #d03b3b; --serie: var(--primario-vis); }

    .dash-filtros { display: flex; align-items: center; gap: 10px 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .segmentos { display: inline-flex; flex-wrap: wrap; gap: 4px; padding: 4px; border-radius: 12px; background: var(--superficie-alta); }
    .segmentos a { padding: 8px 14px; border-radius: 9px; font-size: 13.5px; font-weight: 600; color: var(--muted); text-decoration: none; transition: background 0.15s, color 0.15s; white-space: nowrap; }
    .segmentos a:hover { color: var(--linea); background: color-mix(in srgb, var(--linea) 7%, transparent); }
    .segmentos a.activo { color: var(--linea); background: var(--superficie-hover); box-shadow: 0 1px 2px rgba(0,0,0,0.25); }
    .rango-propio { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto; }
    .rango-propio form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 0; }
    .rango-propio input[type=date] { width: auto; min-height: 40px; padding: 7px 12px; font-size: 13.5px; }
    .rango-propio .sep { color: var(--muted); font-size: 13px; }

    /* Cifra principal + tarjetas de métricas */
    .dash-cima { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1.5fr); gap: 16px; margin-bottom: 16px; }
    .hero { display: flex; flex-direction: column; justify-content: space-between; gap: 18px; padding: 24px 26px; border-radius: 22px;
        background: linear-gradient(155deg, color-mix(in srgb, var(--floodlight) 16%, var(--superficie-alta)), var(--superficie) 70%);
        box-shadow: 0 1px 0 color-mix(in srgb, var(--linea) 7%, transparent) inset, 0 20px 40px -26px rgba(0,0,0,0.5); }
    .hero .etiqueta { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--texto-suave); }
    .hero .etiqueta .ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: color-mix(in srgb, var(--acento-vis) 16%, transparent); color: var(--acento-vis); }
    .hero .cifra { font-family: var(--fuente-titulo); font-size: clamp(40px, 6vw, 58px); font-weight: 800; letter-spacing: -2px; line-height: 1; }
    .variacion { display: inline-flex; align-items: center; gap: 6px; margin-top: 12px; padding: 5px 11px; border-radius: 999px; font-size: 12.5px; font-weight: 600; background: color-mix(in srgb, var(--linea) 8%, transparent); color: var(--texto-suave); }
    .variacion.sube .icon { color: var(--ok); }
    .variacion.baja .icon { color: var(--mal); }
    .hero .pie { display: flex; gap: 8px 22px; flex-wrap: wrap; font-size: 13px; color: var(--muted); }
    .hero .pie b { color: var(--linea); font-weight: 600; font-variant-numeric: tabular-nums; }

    .tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .tile { padding: 18px 20px; border-radius: 20px; background: var(--superficie); display: flex; flex-direction: column; gap: 12px; }
    .tile .arriba { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .tile .nombre { font-size: 13.5px; font-weight: 600; color: var(--texto-suave); }
    .tile .ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: none; background: color-mix(in srgb, var(--c) 15%, transparent); color: var(--c); }
    .tile .valor { font-family: var(--fuente-titulo); font-size: clamp(30px, 3.6vw, 38px); font-weight: 800; letter-spacing: -1px; line-height: 1; }
    .tile .nota { font-size: 12.5px; color: var(--muted); }
    .tile.ok { --c: var(--ok); } .tile.espera { --c: var(--espera); } .tile.mal { --c: var(--mal); } .tile.neutro { --c: var(--primario-vis); }

    /* Tarjetas de gráficas */
    .dash-rejilla { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .grafica { padding: 20px 22px 22px; border-radius: 22px; background: var(--superficie); min-width: 0; }
    .grafica.ancha { grid-column: 1 / -1; }
    .grafica-cab { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .grafica-cab h2 { font-size: 16.5px; }
    .grafica-cab p { margin: 3px 0 0; font-size: 13px; color: var(--muted); }
    .vista-toggle { display: inline-flex; padding: 3px; border-radius: 10px; background: var(--superficie-alta); flex: none; }
    .vista-toggle button { display: inline-flex; align-items: center; gap: 6px; padding: 6px 11px; border: 0; border-radius: 8px; background: none; color: var(--muted); font-family: var(--fuente); font-size: 12.5px; font-weight: 600; cursor: pointer; }
    .vista-toggle button[aria-pressed="true"] { background: var(--superficie-hover); color: var(--linea); }
    .vista-tabla { display: none; }
    .grafica.ver-tabla .vista-grafica { display: none; }
    .grafica.ver-tabla .vista-tabla { display: block; }

    .leyenda { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-bottom: 14px; font-size: 13px; color: var(--texto-suave); }
    .leyenda span { display: inline-flex; align-items: center; gap: 7px; }
    .leyenda i { width: 10px; height: 10px; border-radius: 3px; background: var(--c); flex: none; }
    .leyenda .ok { --c: var(--ok); } .leyenda .espera { --c: var(--espera); } .leyenda .mal { --c: var(--mal); }
    .leyenda b { color: var(--linea); font-variant-numeric: tabular-nums; }

    /* Barra apilada de estado (parte de un todo) */
    .estado-barra { display: flex; gap: 2px; height: 22px; margin-bottom: 18px; }
    .estado-barra > div { min-width: 6px; background: var(--c); transition: filter 0.15s; cursor: default; }
    .estado-barra > div:first-child { border-radius: 4px 0 0 4px; } .estado-barra > div:last-child { border-radius: 0 4px 4px 0; }
    .estado-barra > div:only-child { border-radius: 4px; }
    .estado-barra > div:hover { filter: brightness(1.15); }
    .estado-barra .vacia { flex: 1; background: var(--superficie-alta); border-radius: 4px; }
    .estado-filas { display: grid; gap: 4px; }
    .estado-fila { display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; background: color-mix(in srgb, var(--linea) 4%, transparent); font-size: 14px; }
    .estado-fila .ico { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: color-mix(in srgb, var(--c) 16%, transparent); color: var(--c); }
    .estado-fila .num { font-family: var(--fuente-titulo); font-size: 18px; font-weight: 800; font-variant-numeric: tabular-nums; }
    .estado-fila .p { min-width: 44px; text-align: right; color: var(--muted); font-size: 13px; font-variant-numeric: tabular-nums; }

    /* Barras horizontales: una sola serie */
    .hbars { display: grid; gap: 12px; }
    .hbar { display: grid; grid-template-columns: minmax(96px, 34%) 1fr auto; align-items: center; gap: 12px; border-radius: 10px; padding: 3px 0; outline-offset: 3px; }
    .hbar:hover .pista > span, .hbar:focus-visible .pista > span { filter: brightness(1.18); }
    .hbar .nom { font-size: 13.5px; color: var(--texto-suave); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hbar .pista { height: 14px; border-radius: 0 4px 4px 0; display: flex; }
    .hbar .pista > span { height: 100%; background: var(--serie); border-radius: 0 4px 4px 0; min-width: 3px; transition: filter 0.15s, width 0.5s cubic-bezier(0.2, 0.8, 0.3, 1); }
    .hbar .val { font-size: 13.5px; font-weight: 700; font-variant-numeric: tabular-nums; text-align: right; min-width: 78px; }
    .hbar .val small { display: block; font-weight: 500; font-size: 11.5px; color: var(--muted); }
    .hbar.otros .pista > span { background: color-mix(in srgb, var(--serie) 45%, transparent); }
    /* Barras apiladas por estado */
    .hbar.apilada .pista { gap: 2px; background: none; }
    .hbar.apilada .pista > span { min-width: 4px; }
    .hbar.apilada .pista > span:first-child { border-radius: 0; }
    .hbar.apilada .pista > span:last-child { border-radius: 0 4px 4px 0; }
    .s-ok { background: var(--ok) !important; } .s-espera { background: var(--espera) !important; } .s-mal { background: var(--mal) !important; }

    /* Columnas por día */
    .columnas { display: grid; grid-template-columns: 34px 1fr; gap: 8px; }
    .eje-y { position: relative; height: 190px; font-size: 11.5px; color: var(--muted); font-variant-numeric: tabular-nums; }
    .eje-y span { position: absolute; right: 0; transform: translateY(50%); }
    .plano { position: relative; }
    .plano .rejilla { position: relative; height: 190px; }
    .plano .rejilla i { position: absolute; left: 0; right: 0; height: 1px; background: var(--borde); }
    .plano .cols { position: absolute; inset: 0; display: flex; align-items: stretch; justify-content: space-between; gap: 4px; }
    .col { flex: 1; max-width: 24px; margin: 0 auto; display: flex; flex-direction: column-reverse; justify-content: flex-start; height: 100%; outline-offset: 2px; border-radius: 4px; cursor: default; }
    .col:hover > span, .col:focus-visible > span { filter: brightness(1.2); }
    .col > span { display: block; width: 100%; }
    .col > span + span { margin-bottom: 2px; }
    .col > span:last-child { border-radius: 4px 4px 0 0; }
    .col > span:first-child:last-child { border-radius: 4px 4px 0 0; }
    .col .hueco { flex: 1; }
    .col .base { order: 99; }
    .ejex { display: flex; justify-content: space-between; gap: 4px; margin-top: 8px; border-top: 1px solid var(--borde-fuerte); padding-top: 6px; }
    .ejex span { flex: 1; text-align: center; font-size: 11.5px; color: var(--muted); font-variant-numeric: tabular-nums; min-height: 1em; }

    /* Tablas */
    .tabla-wrap { overflow-x: auto; margin: 0 -4px; padding: 0 4px; }
    table.datos { width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 460px; }
    table.datos th { text-align: right; font-size: 11.5px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; color: var(--muted); padding: 8px 10px; border-bottom: 1px solid var(--borde-fuerte); white-space: nowrap; }
    table.datos th:first-child, table.datos td:first-child { text-align: left; }
    table.datos td { text-align: right; padding: 10px 10px; border-bottom: 1px solid var(--borde); font-variant-numeric: tabular-nums; }
    table.datos td:first-child { color: var(--linea); font-weight: 600; }
    table.datos tfoot td { font-weight: 800; border-bottom: 0; color: var(--linea); }

    /* Tooltip */
    .tip { position: fixed; z-index: 300; pointer-events: none; padding: 10px 12px; border-radius: 12px; min-width: 150px; max-width: 260px; background: var(--superficie-hover); box-shadow: 0 12px 30px rgba(0,0,0,0.4), 0 0 0 1px var(--borde-fuerte); opacity: 0; transform: translateY(4px); transition: opacity 0.12s, transform 0.12s; }
    .tip.visible { opacity: 1; transform: none; }
    .tip .t { font-size: 12.5px; font-weight: 700; margin-bottom: 6px; color: var(--linea); }
    .tip .f { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted); padding: 1px 0; }
    .tip .f i { width: 10px; height: 3px; border-radius: 2px; background: var(--c, var(--serie)); flex: none; }
    .tip .f b { margin-left: auto; color: var(--linea); font-weight: 700; font-variant-numeric: tabular-nums; padding-left: 10px; }

    .vacio-dash { text-align: center; padding: 56px 20px; border-radius: 22px; background: var(--superficie); color: var(--muted); }
    .vacio-dash .ico-caja { width: 56px; height: 56px; border-radius: 16px; margin: 0 auto 14px; display: grid; place-items: center; background: var(--superficie-alta); }
    .vacio-dash strong { display: block; color: var(--linea); font-family: var(--fuente-titulo); font-size: 18px; margin-bottom: 4px; }
    .aviso-precio { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 14px; font-size: 13px; margin-bottom: 16px; color: var(--texto-suave); background: color-mix(in srgb, var(--espera) 12%, transparent); }
    .aviso-precio .icon { margin-top: 1px; color: var(--acento-vis); }

    @media (max-width: 1024px) {
        .dash-cima, .dash-rejilla { grid-template-columns: 1fr; }
        .rango-propio { margin-left: 0; width: 100%; }
    }
    @media (max-width: 720px) {
        .dash-filtros { gap: 12px; }
        .segmentos { width: 100%; }
        .segmentos a { flex: 1; text-align: center; padding: 8px 6px; font-size: 13px; }
        .tiles { gap: 10px; }
        .tile { padding: 14px; border-radius: 18px; gap: 10px; }
        .hero { padding: 20px; border-radius: 20px; }
        .grafica { padding: 16px; border-radius: 20px; }
        .hbar { grid-template-columns: 1fr auto; gap: 4px 12px; }
        .hbar .pista { grid-column: 1 / -1; grid-row: 2; }
        .columnas { grid-template-columns: 28px 1fr; }
        .estado-fila { grid-template-columns: auto 1fr auto; } .estado-fila .p { display: none; }
    }
    @media (prefers-reduced-motion: reduce) { .hbar .pista > span, .tip { transition: none; } }
</style>
@endpush
