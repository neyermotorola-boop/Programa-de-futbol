@push('styles')
<style>
    /* ---------- Pestañas arriba (igual que en la configuración del salón) ---------- */
    .cfg-tabs { display: flex; gap: 4px; overflow-x: auto; border-bottom: 1px solid var(--borde-fuerte); margin-bottom: 18px; scrollbar-width: none; }
    .cfg-tabs::-webkit-scrollbar { display: none; }
    .cfg-tab {
        position: relative; display: inline-flex; align-items: center; gap: 8px; flex: none; padding: 11px 16px; margin-bottom: -1px;
        background: none; border: 0; border-bottom: 2px solid transparent; color: var(--muted); cursor: pointer; text-decoration: none;
        font-family: var(--fuente); font-size: 14px; font-weight: 600; white-space: nowrap; border-radius: 10px 10px 0 0;
        transition: color 0.15s, background 0.15s, border-color 0.15s;
    }
    .cfg-tab:hover { color: var(--linea); background: var(--superficie-alta); }
    .cfg-tab.activo { color: var(--linea); border-bottom-color: var(--floodlight); }
    .cfg-tab.activo .icon { color: var(--acento-vis); }
    .cfg-tab:focus-visible { outline: 3px solid color-mix(in srgb, var(--floodlight) 40%, transparent); outline-offset: -3px; }
    .cfg-tab .punto { width: 8px; height: 8px; border-radius: 50%; background: var(--acento-vis); }
</style>
@endpush
