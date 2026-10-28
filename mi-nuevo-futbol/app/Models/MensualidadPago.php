<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensualidadPago extends Model
{
    protected $table = 'parqueadero_mensualidad_pagos';

    protected $fillable = ['mensualidad_id', 'user_id', 'valor', 'desde', 'hasta', 'cobrado_por'];

    protected function casts(): array
    {
        return ['valor' => 'float', 'desde' => 'date:Y-m-d', 'hasta' => 'date:Y-m-d'];
    }

    public function mensualidad(): BelongsTo
    {
        return $this->belongsTo(Mensualidad::class, 'mensualidad_id');
    }
}
