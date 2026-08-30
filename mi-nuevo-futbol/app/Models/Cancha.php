<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cancha extends Model
{
    protected $fillable = ['user_id', 'nombre', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
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
}