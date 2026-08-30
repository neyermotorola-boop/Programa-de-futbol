<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    protected $fillable = [
        'user_id',
        'cancha_id',
        'nombre_cliente',
        'telefono',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion_horas',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function cancha(): BelongsTo
    {
        return $this->belongsTo(Cancha::class);
    }

    public function scopeEnFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    /**
     * Genera el enlace de WhatsApp (wa.me) con el mensaje de confirmación
     * ya redactado, listo para que el administrador lo envíe con un clic.
     */
    public function getEnlaceWhatsappAttribute(): string
    {
        $telefono = preg_replace('/\D/', '', $this->telefono);

        $mensaje = "Hola {$this->nombre_cliente}, te confirmamos tu reserva en la "
            . "{$this->cancha->nombre} para el " . $this->fecha->format('d/m/Y')
            . " de " . substr($this->hora_inicio, 0, 5) . " a " . substr($this->hora_fin, 0, 5)
            . " horas. ¡Te esperamos! ⚽";

        return "https://wa.me/{$telefono}?text=" . urlencode($mensaje);
    }

    /**
     * La confirmación por WhatsApp solo debe habilitarse el mismo día
     * de la reserva, a partir de las 10:00 am.
     */
    public function getListaParaConfirmarAttribute(): bool
    {
        return $this->fecha->isToday() && now()->format('H:i') >= '10:00';
    }
}