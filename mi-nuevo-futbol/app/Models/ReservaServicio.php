<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un servicio dentro de una cita (una cita puede combinar varios). Guarda el nombre, la duración
 * y el precio que tenía al reservar.
 */
class ReservaServicio extends Model
{
    protected $table = 'reserva_servicios';

    protected $fillable = ['reserva_id', 'servicio_id', 'nombre', 'duracion_minutos', 'precio', 'orden'];

    protected $casts = ['precio' => 'decimal:2'];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
