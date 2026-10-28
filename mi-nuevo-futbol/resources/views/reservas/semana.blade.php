@extends('layouts.app')

@section('title', 'Agenda semanal')

@section('nav')
    @include('partials.nav-negocio', ['negocio' => $negocio, 'activo' => 'semana'])
@endsection

@php
    $esSalon = $negocio->tipo_negocio === 'salon';
    $finSemana = $inicioSemana->copy()->addDays(6);
    // Abre la pestaña de hoy si la semana mostrada la incluye; si no, el lunes.
    $diaActivo = collect($dias)->search(fn ($d) => $d['fecha']->isToday());
    $diaActivo = $diaActivo === false ? 0 : $diaActivo;
@endphp

@push('styles')
<style>
    .nav-semana { display: flex; align-items: center; gap: 8px; }
    .rango-semana { font-family: var(--fuente-titulo); font-weight: 700; font-size: 15px; padding: 0 12px; white-space: nowrap; font-variant-numeric: tabular-nums; }

    .tabs { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; margin-bottom: 18px; }
    .tab {
        font-family: var(--fuente); font-size: 12px; font-weight: 600; letter-spacing: 0.6px; text-transform: uppercase;
        padding: 12px 6px; border-radius: 12px; background: var(--superficie); border: 1px solid var(--borde);
        cursor: pointer; text-align: center; color: var(--muted); transition: border-color 0.15s, background 0.15s;
        position: relative;
    }
    .tab:hover:not(.activo) { border-color: var(--borde-fuerte); color: var(--linea); }
    .tab .dia-num { display: block; font-family: var(--fuente-titulo); font-size: 22px; font-weight: 800; letter-spacing: 0; color: var(--linea); margin-top: 2px; font-variant-numeric: tabular-nums; }
    .tab .contador { display: block; margin-top: 4px; font-size: 11px; font-weight: 500; text-transform: none; letter-spacing: 0; }
    .tab.activo { background: var(--turf-claro); border-color: var(--turf-claro); color: var(--texto-primario); box-shadow: 0 8px 22px color-mix(in srgb, var(--turf) 35%, transparent); }
    .tab.activo .dia-num { color: var(--texto-primario); }
    .tab.hoy::after { content: ""; position: absolute; top: 8px; right: 8px; width: 7px; height: 7px; border-radius: 50%; background: var(--floodlight); box-shadow: 0 0 8px var(--floodlight); }

    .dia-panel { display: none; }
    .dia-panel.activo { display: block; }

    .tabla-scroll { overflow-x: auto; border-radius: var(--radio); border: 1px solid var(--borde); background: var(--superficie); -webkit-overflow-scrolling: touch; }
    table.agenda { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 100%; }
    table.agenda th {
        position: sticky; top: 0; font-size: 12px; font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase;
        color: var(--texto-suave); background: var(--superficie-alta); padding: 14px 10px; text-align: center; white-space: nowrap;
        border-bottom: 1px solid var(--borde-fuerte);
    }
    table.agenda th .icon { color: var(--primario-vis); margin-right: 4px; }
    table.agenda td { padding: 6px; text-align: center; font-size: 14px; border-top: 1px solid var(--borde); vertical-align: middle; min-width: 140px; }
    table.agenda .col-hora { position: sticky; left: 0; z-index: 1; width: 96px; min-width: 96px; background: var(--superficie-alta); }
    table.agenda th.col-hora { z-index: 2; }
    td.hora-etiqueta { font-weight: 600; color: var(--muted); font-size: 13px; font-variant-numeric: tabular-nums; white-space: nowrap; border-right: 1px solid var(--borde); }
    td.libre { color: color-mix(in srgb, var(--linea) 22%, transparent); font-size: 13px; }
    .bloque-reserva { display: block; padding: 8px 10px; border-radius: 10px; background: color-mix(in srgb, var(--acento-vis) 12%, transparent); border: 1px solid color-mix(in srgb, var(--acento-vis) 25%, transparent); text-align: left; }
    .bloque-reserva.confirmada { background: color-mix(in srgb, var(--primario-vis) 12%, transparent); border-color: color-mix(in srgb, var(--primario-vis) 28%, transparent); }
    .bloque-reserva .cliente { display: block; font-weight: 600; color: var(--linea); font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
    .bloque-reserva .estado-mini { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--acento-vis); }
    .bloque-reserva.confirmada .estado-mini { color: var(--primario-vis); }

    .leyenda { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 14px; font-size: 13px; color: var(--muted); }
    .leyenda span { display: inline-flex; align-items: center; gap: 8px; }
    .leyenda i { width: 12px; height: 12px; border-radius: 4px; display: inline-block; }

    @media (max-width: 1024px) {
        .tabs { display: flex; overflow-x: auto; padding-bottom: 4px; scroll-snap-type: x mandatory; }
        .tab { flex: 0 0 76px; scroll-snap-align: start; }
    }
    @media (max-width: 720px) {
        .nav-semana { width: 100%; justify-content: space-between; }
        .rango-semana { font-size: 14px; padding: 0 4px; }
        .tab { flex-basis: 64px; padding: 10px 4px; }
        .tab .dia-num { font-size: 19px; }
        table.agenda .col-hora { width: 74px; min-width: 74px; }
        table.agenda td { min-width: 120px; padding: 5px; }
        td.hora-etiqueta { font-size: 12px; }
    }
</style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <div class="eyebrow"><x-icon name="calendar-days" size="14" /> Vista semanal</div>
            <h1>Agenda semanal</h1>
            <div class="subtitulo">Ocupación de lunes a domingo · {{ $canchas->count() }} {{ $esSalon ? ($canchas->count() === 1 ? 'profesional' : 'profesionales') : ($canchas->count() === 1 ? 'cancha' : 'canchas') }}</div>
        </div>
        <div class="nav-semana">
            <a href="{{ route('reservas.semana', ['semana' => $inicioSemana->copy()->subWeek()->format('Y-m-d')]) }}" class="btn btn-secondary btn-icon" aria-label="Semana anterior"><x-icon name="chevron-left" /></a>
            <span class="rango-semana">{{ $inicioSemana->locale('es')->translatedFormat('j M') }} – {{ $finSemana->locale('es')->translatedFormat('j M Y') }}</span>
            <a href="{{ route('reservas.semana', ['semana' => $inicioSemana->copy()->addWeek()->format('Y-m-d')]) }}" class="btn btn-secondary btn-icon" aria-label="Semana siguiente"><x-icon name="chevron-right" /></a>
        </div>
    </div>

    @if ($canchas->isEmpty())
        <div class="vacio">
            <div class="ico-caja"><x-icon name="inbox" size="26" /></div>
            <strong>No hay {{ $esSalon ? 'profesionales' : 'canchas' }} activas</strong>
            Pide al administrador que registre al menos una para ver la agenda.
        </div>
    @else
        <div class="tabs" id="tabs" role="tablist">
            @foreach ($dias as $i => $dia)
                @php $total = collect($dia['matriz'])->flatten()->filter()->unique('id')->count(); @endphp
                <button type="button" role="tab" class="tab {{ $dia['fecha']->isToday() ? 'hoy' : '' }} {{ $i === $diaActivo ? 'activo' : '' }}" data-dia="{{ $i }}">
                    {{ mb_substr($dia['nombre'], 0, 3) }}
                    <span class="dia-num">{{ $dia['fecha']->format('d') }}</span>
                    <span class="contador">{{ $total }} {{ $total === 1 ? 'reserva' : 'reservas' }}</span>
                </button>
            @endforeach
        </div>

        @foreach ($dias as $i => $dia)
            <div class="dia-panel {{ $i === $diaActivo ? 'activo' : '' }}" data-dia-panel="{{ $i }}" role="tabpanel">
                <div class="tabla-scroll">
                    <table class="agenda">
                        <thead>
                            <tr>
                                <th class="col-hora">Hora</th>
                                @foreach ($canchas as $cancha)
                                    <th><x-icon :name="$esSalon ? 'user' : 'pitch'" size="14" />{{ $cancha->nombre }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($horas as $hora)
                                <tr>
                                    <td class="hora-etiqueta col-hora">{{ \Carbon\Carbon::createFromFormat('H:i', $hora)->format('g:i A') }}</td>
                                    @foreach ($canchas as $cancha)
                                        @php $reserva = $dia['matriz'][$cancha->id][$hora] ?? null; @endphp
                                        @if ($reserva)
                                            <td>
                                                <span class="bloque-reserva {{ $reserva->estado === 'confirmada' ? 'confirmada' : '' }}">
                                                    <span class="cliente">{{ $reserva->nombre_cliente }}</span>
                                                    <span class="estado-mini">{{ $reserva->estado }}</span>
                                                </span>
                                            </td>
                                        @else
                                            <td class="libre">Libre</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="leyenda">
            <span><i style="background:color-mix(in srgb, var(--acento-vis) 50%, transparent)"></i> Pendiente</span>
            <span><i style="background:color-mix(in srgb, var(--primario-vis) 50%, transparent)"></i> Confirmada</span>
            <span><i style="background:var(--floodlight);border-radius:50%;width:8px;height:8px"></i> Hoy</span>
        </div>
    @endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const i = tab.dataset.dia;
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('activo'));
        document.querySelectorAll('.dia-panel').forEach(p => p.classList.remove('activo'));
        tab.classList.add('activo');
        document.querySelector(`[data-dia-panel="${i}"]`).classList.add('activo');
    });
});
document.querySelector('.tab.activo')?.scrollIntoView({ block: 'nearest', inline: 'center' });
</script>
@endpush
