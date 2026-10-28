<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cancha extends Model
{
    protected $fillable = [
        'user_id', 'nombre', 'activa',
        // Ficha de cancha: deporte y superficie son categorías que define cada negocio;
        // "tamano" guarda el formato de juego (fútbol 5, dobles, 6 contra 6...).
        'deporte_id', 'superficie_id', 'foto_path', 'foto_modo', 'superficie', 'tamano', 'iluminacion', 'techada', 'precio_hora',
        // Ficha de profesional (salones)
        'especialidad', 'dias_disponibles', 'hora_inicio_trabajo', 'hora_fin_trabajo', 'descanso_inicio', 'descanso_fin',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'iluminacion' => 'boolean',
        'techada' => 'boolean',
        'precio_hora' => 'decimal:2',
        'dias_disponibles' => 'array',
    ];

    /**
     * El negocio/usuario dueño de esta cancha.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function deporte(): BelongsTo
    {
        return $this->belongsTo(CategoriaCancha::class, 'deporte_id');
    }

    public function superficieCategoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaCancha::class, 'superficie_id');
    }

    /** Diseño del deporte ('futbol', 'tenis', 'padel', 'voleibol' o 'generico'). */
    public function baseDeporte(): string
    {
        return $this->deporte?->base ?? 'futbol';
    }

    /** Nombre del formato de juego ("Fútbol 5", "Dobles"...), si el deporte tiene formatos. */
    public function nombreFormato(): ?string
    {
        return CategoriaCancha::FORMATOS[$this->baseDeporte()][$this->tamano] ?? null;
    }

    public function iconoDeporte(): string
    {
        return $this->deporte?->icono() ?? 'pitch';
    }

    /** "Pádel · Dobles · Grama sintética": una línea para listas y tarjetas. */
    public function resumen(): string
    {
        return collect([$this->deporte?->nombre, $this->nombreFormato(), $this->superficieCategoria?->nombre])->filter()->implode(' · ') ?: 'Sin categoría';
    }

    /**
     * (Profesionales) ¿Trabaja este día de la semana? Nulo = todos los días.
     */
    public function disponibleEl(int $diaIso): bool
    {
        return empty($this->dias_disponibles) || in_array($diaIso, $this->dias_disponibles);
    }

    /** (Profesionales) Horario de trabajo [inicio, fin] en 'HH:MM': el propio o, si no tiene, el del negocio. */
    public function ventanaTrabajo(User $negocio): array
    {
        return [
            substr($this->hora_inicio_trabajo ?: $negocio->hora_apertura, 0, 5),
            substr($this->hora_fin_trabajo ?: $negocio->hora_cierre, 0, 5),
        ];
    }

    /** (Profesionales) Descanso o almuerzo [inicio, fin] en 'HH:MM', o null si no tiene. */
    public function descanso(): ?array
    {
        return $this->descanso_inicio && $this->descanso_fin
            ? [substr($this->descanso_inicio, 0, 5), substr($this->descanso_fin, 0, 5)]
            : null;
    }

    /** Texto corto del horario propio para mostrar al cliente ("9:00 – 15:00"), o null si usa el del negocio. */
    public function textoHorarioPropio(): ?string
    {
        return $this->hora_inicio_trabajo && $this->hora_fin_trabajo
            ? substr($this->hora_inicio_trabajo, 0, 5) . ' – ' . substr($this->hora_fin_trabajo, 0, 5)
            : null;
    }

    /**
     * Datos que necesita el visor 3D de la página pública.
     */
    public function datos3d(): array
    {
        return [
            'deporte' => $this->baseDeporte(),
            'formato' => $this->tamano,
            'estilo' => $this->superficieCategoria?->base ?? 'cesped',
            'color' => $this->superficieCategoria?->color ?: '#2F8F3F',
            'iluminacion' => (bool) $this->iluminacion,
            'techada' => (bool) $this->techada,
            'foto' => $this->urlFoto(),
            'fotoModo' => $this->foto_modo ?: 'entorno',
        ];
    }

    public function urlFoto(): ?string
    {
        return $this->foto_path ? asset($this->foto_path) : null;
    }
}
