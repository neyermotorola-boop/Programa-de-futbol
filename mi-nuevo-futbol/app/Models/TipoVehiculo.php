<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoVehiculo extends Model
{
    protected $table = 'parqueadero_tipos';

    protected $fillable = ['user_id', 'nombre', 'tarifa_tipo', 'tarifa_valor', 'orden'];

    /** Tipos con los que arranca cada parqueadero nuevo; luego el cliente los edita o agrega más. */
    public const INICIALES = ['Carro', 'Moto', 'Cicla'];

    protected function casts(): array
    {
        return ['tarifa_valor' => 'float'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(ParqueaderoIngreso::class, 'tipo_id');
    }

    /** Crea Carro, Moto y Cicla (sin tarifa todavía) para un parqueadero nuevo. */
    public static function crearIniciales(User $negocio): void
    {
        foreach (self::INICIALES as $i => $nombre) {
            self::create(['user_id' => $negocio->id, 'nombre' => $nombre, 'orden' => $i + 1]);
        }
    }

    /** Ícono según el nombre: moto, bicicleta o, por defecto, carro. */
    public function icono(): string
    {
        return self::iconoPara($this->nombre);
    }

    public static function iconoPara(?string $nombre): string
    {
        $n = mb_strtolower((string) $nombre);

        return match (true) {
            str_contains($n, 'moto') => 'moto',
            str_contains($n, 'cicla'), str_contains($n, 'bici') => 'bike',
            default => 'car',
        };
    }

    public function textoTarifa(): string
    {
        return '$ ' . number_format($this->tarifa_valor, 0, ',', '.') . ' por ' . ($this->tarifa_tipo === 'minuto' ? 'minuto' : 'hora');
    }
}
