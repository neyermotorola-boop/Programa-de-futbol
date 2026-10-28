@extends('layouts.app')

@section('title', 'Panel del día')

@section('nav')
    @include('partials.nav-negocio', ['negocio' => $negocio, 'activo' => 'admin'])
@endsection

@php
    $dia = \Carbon\Carbon::parse($fecha)->locale('es');
    $pendientes = $reservas->where('estado', '!=', 'confirmada')->count();
    $confirmadas = $reservas->where('estado', 'confirmada')->count();
    $esSalon = $negocio->tipo_negocio === 'salon';
    $iniciales = fn ($nombre) => collect(preg_split('/\s+/', trim($nombre)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $duracion = function ($ini, $fin) {
        $m = (strtotime($fin) - strtotime($ini)) / 60;
        $h = intdiv($m, 60);
        $r = $m % 60;
        return trim(($h ? $h . ' h ' : '') . ($r ? $r . ' min' : ''));
    };
@endphp

@push('styles')
<style>
    .selector-fecha { display: flex; align-items: center; gap: 8px; }
    .selector-fecha form { margin: 0; }
    .selector-fecha input[type=date] { width: auto; min-width: 170px; }

    /* ---------- Tarjeta de cita ---------- */
    .citas { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 360px), 1fr)); gap: 18px; }
    .cita {
        position: relative; display: flex; flex-direction: column; gap: 18px; padding: 20px; border-radius: 22px;
        background: linear-gradient(165deg, color-mix(in srgb, var(--superficie-alta) 70%, var(--superficie)), var(--superficie) 60%);
        box-shadow: 0 1px 0 color-mix(in srgb, var(--linea) 7%, transparent) inset, 0 18px 36px -22px rgba(0,0,0,0.55);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .cita:hover { transform: translateY(-3px); box-shadow: 0 1px 0 color-mix(in srgb, var(--linea) 9%, transparent) inset, 0 26px 44px -22px rgba(0,0,0,0.6); }

    .cita-fila { display: flex; gap: 16px; align-items: stretch; }
    .cita-hora {
        flex: none; width: 92px; padding: 14px 8px; border-radius: 18px; text-align: center;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
        background: color-mix(in srgb, var(--turf) 24%, var(--superficie-alta));
    }
    .cita-hora .hh { font-family: var(--fuente-titulo); font-size: 28px; font-weight: 800; letter-spacing: -1px; line-height: 1; font-variant-numeric: tabular-nums; }
    .cita-hora .ap { font-size: 11px; font-weight: 700; letter-spacing: 1.6px; color: var(--primario-vis); }
    .cita-hora .hasta { margin-top: 8px; font-size: 11px; line-height: 1.3; color: var(--muted); font-variant-numeric: tabular-nums; }

    .cita-cuerpo { min-width: 0; flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 2px; }
    .cita-estado { display: inline-flex; align-items: center; gap: 7px; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--acento-vis); margin-bottom: 6px; }
    .cita-estado::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; box-shadow: 0 0 0 4px color-mix(in srgb, currentColor 18%, transparent); }
    .cita.confirmada .cita-estado { color: var(--primario-vis); }
    .cita-nombre { font-family: var(--fuente-titulo); font-size: 18px; font-weight: 700; letter-spacing: -0.3px; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cita-servicio { color: var(--texto-suave); font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cita-dur { color: var(--muted); font-size: 12.5px; margin-top: 2px; }

    .cita-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 10px; }
    .cita-meta .dato {
        display: inline-flex; align-items: center; gap: 7px; min-width: 0; padding: 6px 12px 6px 6px; border-radius: 999px;
        background: color-mix(in srgb, var(--linea) 6%, transparent); font-size: 13px; color: var(--texto-suave); text-decoration: none;
        transition: background 0.15s, color 0.15s;
    }
    .cita-meta a.dato:hover { background: color-mix(in srgb, var(--linea) 11%, transparent); color: var(--linea); }
    .cita-meta .avatar-mini { width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; flex: none; font-size: 10.5px; font-weight: 800; letter-spacing: 0.3px; background: var(--turf-claro); color: var(--texto-primario); }
    .cita-meta .ico-mini { width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; flex: none; background: color-mix(in srgb, var(--primario-vis) 14%, transparent); color: var(--primario-vis); }
    .cita-meta .dato-diseno img { width: 26px; height: 26px; border-radius: 50%; object-fit: cover; flex: none; }
    .cita-meta .dato span:last-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .cita-acciones { display: flex; gap: 10px; align-items: center; }
    .cita-acciones form { margin: 0; display: flex; }
    .cita-acciones .principal { flex: 1; }
    .cita-acciones .principal .btn { width: 100%; }
    .btn-suave { background: color-mix(in srgb, var(--linea) 7%, transparent); color: var(--muted); border: 0; }
    .btn-suave:hover { background: color-mix(in srgb, var(--error) 14%, transparent); color: var(--error); }
    .cita-ok { flex: 1; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--primario-vis); }

    @media (max-width: 720px) {
        .selector-fecha { width: 100%; }
        .selector-fecha form { flex: 1; }
        .selector-fecha input[type=date] { width: 100%; min-width: 0; }
        .cita { padding: 16px; gap: 14px; border-radius: 20px; }
        .cita-hora { width: 78px; }
        .cita-hora .hh { font-size: 24px; }
    }
</style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <div class="eyebrow"><x-icon name="calendar" size="14" /> {{ $dia->isToday() ? 'Hoy' : ucfirst($dia->translatedFormat('l')) }}</div>
            <h1>Panel de reservas</h1>
            <div class="subtitulo">{{ ucfirst($dia->translatedFormat('l j \d\e F \d\e Y')) }} · confirma o cancela cada reserva.</div>
        </div>
        <div class="selector-fecha">
            <a href="{{ route('reservas.admin', ['fecha' => $dia->copy()->subDay()->format('Y-m-d')]) }}" class="btn btn-secondary btn-icon" aria-label="Día anterior"><x-icon name="chevron-left" /></a>
            <form method="GET" action="{{ route('reservas.admin') }}">
                <input type="date" name="fecha" value="{{ $fecha }}" onchange="this.form.submit()" aria-label="Elegir fecha">
            </form>
            <a href="{{ route('reservas.admin', ['fecha' => $dia->copy()->addDay()->format('Y-m-d')]) }}" class="btn btn-secondary btn-icon" aria-label="Día siguiente"><x-icon name="chevron-right" /></a>
        </div>
    </div>

    @if (session('exito'))
        <div class="alerta alerta-exito" role="status"><x-icon name="check-circle" /> <div>{{ session('exito') }}</div></div>
    @endif

    <div class="stats">
        <div class="stat">
            <span class="ico-caja verde"><x-icon name="calendar-days" size="20" /></span>
            <div><div class="valor">{{ $reservas->count() }}</div><div class="etiqueta">Reservas del día</div></div>
        </div>
        <div class="stat">
            <span class="ico-caja"><x-icon name="hourglass" size="20" /></span>
            <div><div class="valor">{{ $pendientes }}</div><div class="etiqueta">Pendientes</div></div>
        </div>
        <div class="stat">
            <span class="ico-caja verde"><x-icon name="check-circle" size="20" /></span>
            <div><div class="valor">{{ $confirmadas }}</div><div class="etiqueta">Confirmadas</div></div>
        </div>
    </div>

    @if ($reservas->isEmpty())
        <div class="vacio">
            <div class="ico-caja"><x-icon name="inbox" size="26" /></div>
            <strong>Sin reservas para esta fecha</strong>
            Cuando un cliente reserve, aparecerá aquí.
        </div>
    @else
        <div class="citas">
            @foreach ($reservas as $reserva)
                @php
                    $confirmada = $reserva->estado === 'confirmada';
                    $inicio = \Carbon\Carbon::parse($reserva->hora_inicio);
                    $fin = \Carbon\Carbon::parse($reserva->hora_fin);
                @endphp
                <article class="cita {{ $confirmada ? 'confirmada' : '' }}">
                    <div class="cita-fila">
                        <div class="cita-hora">
                            <span class="hh">{{ $inicio->format('g:i') }}</span>
                            <span class="ap">{{ $inicio->format('A') }}</span>
                            <span class="hasta">hasta<br>{{ $fin->format('g:i A') }}</span>
                        </div>
                        <div class="cita-cuerpo">
                            <span class="cita-estado">{{ $confirmada ? 'Confirmada' : 'Pendiente' }}</span>
                            <div class="cita-nombre" title="{{ $reserva->nombre_cliente }}">{{ $reserva->nombre_cliente }}</div>
                            <div class="cita-servicio">{{ $esSalon ? $reserva->nombreServicios() : $reserva->cancha->nombre }}</div>
                            <div class="cita-dur">{{ $duracion($reserva->hora_inicio, $reserva->hora_fin) }}</div>
                        </div>
                    </div>

                    <div class="cita-meta">
                        @if ($esSalon)
                            <span class="dato"><span class="avatar-mini">{{ $iniciales($reserva->cancha->nombre) }}</span><span>{{ $reserva->cancha->nombre }}</span></span>
                        @endif
                        <a class="dato num" href="tel:{{ $reserva->telefono }}"><span class="ico-mini"><x-icon name="phone" size="13" /></span><span>{{ $reserva->telefono }}</span></a>
                        @if ($reserva->email_cliente)
                            <a class="dato" href="mailto:{{ $reserva->email_cliente }}"><span class="ico-mini"><x-icon name="mail" size="13" /></span><span>{{ $reserva->email_cliente }}</span></a>
                        @endif
                        @if ($reserva->urlDiseno())
                            <a class="dato dato-diseno" href="{{ $reserva->urlDiseno() }}" target="_blank" rel="noopener" title="Abrir la foto del diseño"><img src="{{ $reserva->urlDiseno() }}" alt="Diseño elegido"><span>Diseño{{ $reserva->diseno_forma ? ' · ' . (\App\Models\Reserva::FORMAS_UNA[$reserva->diseno_forma] ?? '') : '' }}</span></a>
                        @endif
                    </div>

                    <div class="cita-acciones">
                        @if ($confirmada)
                            <span class="cita-ok"><x-icon name="check-circle" size="18" /> Cita confirmada</span>
                        @else
                            <form method="POST" action="{{ route('reservas.confirmar', $reserva) }}" class="principal">
                                @csrf
                                <button type="submit" class="btn btn-success"><x-icon name="check" size="16" /> Confirmar cita</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('reservas.cancelar', $reserva) }}" onsubmit="return confirm('¿Seguro que quieres cancelar esta reserva? El horario quedará libre de nuevo.');">
                            @csrf
                            <button type="submit" class="btn btn-suave"><x-icon name="x" size="16" /> Cancelar</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
