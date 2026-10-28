<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected $fillable = ['user_id', 'nombre', 'descripcion', 'icono', 'duracion_minutos', 'precio', 'activo', 'confirmacion_automatica', 'permite_diseno', 'margen_minutos'];

    protected $casts = [
        'activo' => 'boolean',
        'confirmacion_automatica' => 'boolean',
        'permite_diseno' => 'boolean',
        'precio' => 'decimal:2',
    ];

    /** Íconos que el negocio puede asignar a un servicio (clave del ícono => nombre). */
    public const ICONOS = [
        'nail' => 'Esmalte', 'hand' => 'Manos', 'scissors' => 'Corte', 'droplet' => 'Color',
        'brow' => 'Cejas', 'lipstick' => 'Maquillaje', 'leaf' => 'Spa', 'eraser' => 'Retiro',
        'plus-circle' => 'Adicional', 'star' => 'Premium', 'heart' => 'Favorito', 'sparkles' => 'Brillo',
    ];

    /**
     * Ícono del servicio: el que eligió el negocio o, si no eligió ninguno,
     * el que mejor le calza según su nombre (uñas, corte, tinte, cejas...).
     */
    public function icono(): string
    {
        return $this->icono && isset(self::ICONOS[$this->icono]) ? $this->icono : self::detectarIcono($this->nombre);
    }

    public static function detectarIcono(string $nombre): string
    {
        $reglas = [
            '/retiro|remoci|quitar/iu' => 'eraser',
            '/\badicional\b|\bextra\b/iu' => 'plus-circle',
            '/acr[ií]lic|press ?on|poly ?gel|\btips\b|extensi|dipping|rubber|semipermanente|esmalt|gel\b|nail/iu' => 'nail',
            '/manicur|pedicur|manos|pies|u[ñn]as/iu' => 'hand',
            '/ceja|pesta[ñn]|lifting|laminad/iu' => 'brow',
            '/maquillaj|makeup|labios|novia/iu' => 'lipstick',
            '/tinte|color|mecha|balayage|henna|bronde|reflejo|decolor/iu' => 'droplet',
            '/corte|peinado|cabello|brushing|barba|keratina|alisado|trenza|recogido|planchad/iu' => 'scissors',
            '/masaje|spa\b|facial|relaj|depilaci|cera\b|hidrata|limpieza facial/iu' => 'leaf',
        ];

        foreach ($reglas as $patron => $icono) {
            if (preg_match($patron, $nombre)) {
                return $icono;
            }
        }

        return 'sparkles';
    }

    /**
     * El negocio (salón) dueño de este servicio.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Minutos que quedan libres después de este servicio: el suyo o, si no tiene, el del negocio. */
    public function margenPara(User $negocio): int
    {
        return (int) ($this->margen_minutos ?? $negocio->margen_minutos ?? 0);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }
}