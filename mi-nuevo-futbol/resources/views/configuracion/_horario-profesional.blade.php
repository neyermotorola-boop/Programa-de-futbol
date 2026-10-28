{{-- Horario propio y descanso de una profesional. Recibe $recurso (null al crear) y $negocio. Vacío = usa el horario del negocio. --}}
<span class="label">Horario de trabajo <small style="font-weight:500;color:var(--muted)">(vacío = el del negocio: {{ substr($negocio->hora_apertura, 0, 5) }} a {{ substr($negocio->hora_cierre, 0, 5) }})</small></span>
<div class="grid-2" style="margin-bottom:12px">
    <div class="control"><x-icon name="clock" /><input type="time" name="hora_inicio_trabajo" value="{{ $recurso ? substr((string) $recurso->hora_inicio_trabajo, 0, 5) : '' }}" aria-label="Empieza a las"></div>
    <div class="control"><x-icon name="clock" /><input type="time" name="hora_fin_trabajo" value="{{ $recurso ? substr((string) $recurso->hora_fin_trabajo, 0, 5) : '' }}" aria-label="Termina a las"></div>
</div>
<span class="label">Descanso o almuerzo <small style="font-weight:500;color:var(--muted)">(no se le agenda nada)</small></span>
<div class="grid-2" style="margin-bottom:14px">
    <div class="control"><x-icon name="clock" /><input type="time" name="descanso_inicio" value="{{ $recurso ? substr((string) $recurso->descanso_inicio, 0, 5) : '' }}" aria-label="Descanso desde"></div>
    <div class="control"><x-icon name="clock" /><input type="time" name="descanso_fin" value="{{ $recurso ? substr((string) $recurso->descanso_fin, 0, 5) : '' }}" aria-label="Descanso hasta"></div>
</div>
