<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParqueaderoIngreso extends Model
{
    protected $table = 'parqueadero_ingresos';

    protected $fillable = [
        'user_id', 'tipo_id', 'mensualidad_id', 'vehiculo', 'placa', 'registrado_por', 'cobrado_por', 'entrada', 'salida', 'minutos', 'tarifa_tipo', 'tarifa_valor', 'valor_cobrado',
    ];

    /** Zona horaria en que se muestran las horas de entrada y salida. */
    public const ZONA = 'America/Bogota';

    protected function casts(): array
    {
        return [
            'entrada' => 'datetime',
            'salida' => 'datetime',
            'tarifa_valor' => 'float',
            'valor_cobrado' => 'float',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Quién cobró la salida (dueño u operador). */
    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cobrado_por');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo_id');
    }

    /** Placa sin espacios ni guiones y en mayúsculas, para que "abc 123" y "ABC-123" sean la misma. */
    public static function normalizarPlaca(string $placa): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placa));
    }

    /**
     * Minutos que lleva (o llevó) el carro. Mínimo 1: un carro que entró cuenta al menos un minuto.
     */
    public function minutosTranscurridos(?Carbon $hasta = null): int
    {
        $fin = $this->salida ?? $hasta ?? now();

        return max(1, (int) ceil($this->entrada->diffInSeconds($fin, true) / 60));
    }

    /**
     * Cuánto se cobra por $minutos con una tarifa dada.
     * 'minuto': minutos exactos × valor. 'hora': cada hora o fracción cuenta como hora completa.
     */
    public static function calcularValor(int $minutos, string $tipo, float $valor): float
    {
        $unidades = $tipo === 'minuto' ? $minutos : (int) ceil($minutos / 60);

        return round($unidades * $valor, 2);
    }

    public function scopeDentro($query)
    {
        return $query->whereNull('salida');
    }

    public function entradaLocal(): Carbon
    {
        return $this->entrada->copy()->timezone(self::ZONA);
    }

    public function salidaLocal(): ?Carbon
    {
        return $this->salida?->copy()->timezone(self::ZONA);
    }

    /** "2 h 15 min" a partir de minutos. */
    public static function textoDuracion(int $minutos): string
    {
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        return trim(($h ? $h . ' h ' : '') . ($m || ! $h ? $m . ' min' : ''));
    }
}
