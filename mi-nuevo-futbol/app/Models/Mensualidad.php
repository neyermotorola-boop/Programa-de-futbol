<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mensualidad extends Model
{
    protected $table = 'parqueadero_mensualidades';

    protected $fillable = ['user_id', 'tipo_id', 'vehiculo', 'placa', 'cliente', 'telefono', 'valor', 'inicio', 'vence', 'activa'];

    /** Desde cuántos días antes del vencimiento se avisa. */
    public const AVISO_DIAS = 5;

    protected function casts(): array
    {
        return [
            'inicio' => 'date:Y-m-d',
            'vence' => 'date:Y-m-d',
            'valor' => 'float',
            'activa' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(MensualidadPago::class, 'mensualidad_id')->orderByDesc('id');
    }

    /** El día de hoy en la zona del parqueadero, como texto Y-m-d. */
    public static function hoy(): string
    {
        return now(ParqueaderoIngreso::ZONA)->toDateString();
    }

    /** Días que le quedan (0 = vence hoy; negativo = ya venció). */
    public function diasRestantes(): int
    {
        return (int) Carbon::parse(self::hoy())->diffInDays(Carbon::parse($this->vence->toDateString()), false);
    }

    public function vigente(): bool
    {
        return $this->vence->toDateString() >= self::hoy();
    }

    /** 'inactiva' | 'vencida' | 'por_vencer' | 'vigente' */
    public function estado(): string
    {
        return match (true) {
            ! $this->activa => 'inactiva',
            ! $this->vigente() => 'vencida',
            $this->diasRestantes() <= self::AVISO_DIAS => 'por_vencer',
            default => 'vigente',
        };
    }

    /** Texto corto del tiempo que queda o que lleva vencida. */
    public function textoDias(): string
    {
        $d = $this->diasRestantes();

        return match (true) {
            $d === 0 => 'Vence hoy',
            $d === 1 => 'Vence mañana',
            $d > 1 => "Faltan {$d} días",
            $d === -1 => 'Venció ayer',
            default => 'Venció hace ' . abs($d) . ' días',
        };
    }

    /**
     * Registra un pago y extiende la vigencia un mes. Si todavía estaba vigente el mes se suma
     * a continuación del que ya tenía; si ya había vencido, cuenta desde hoy.
     */
    public function renovar(float $valor, ?int $usuarioId): MensualidadPago
    {
        $hoy = Carbon::parse(self::hoy());
        $desde = $this->vigente() ? Carbon::parse($this->vence->toDateString()) : $hoy;
        $hasta = $desde->copy()->addMonthNoOverflow();

        $pago = $this->pagos()->create([
            'user_id' => $this->user_id,
            'valor' => $valor,
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'cobrado_por' => $usuarioId,
        ]);

        $this->update(['vence' => $hasta->toDateString(), 'valor' => $valor, 'activa' => true]);

        return $pago;
    }

    /** Enlace para avisarle por WhatsApp al cliente (asume Colombia si el número tiene 10 dígitos). */
    public function urlWhatsapp(string $negocio): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $this->telefono);
        if (strlen($digitos) < 7) {
            return null;
        }
        if (strlen($digitos) === 10) {
            $digitos = '57' . $digitos;
        }

        $mensaje = $this->vigente()
            ? "Hola {$this->cliente}, tu mensualidad del parqueadero {$negocio} (vehículo {$this->placa}) vence el " . $this->vence->format('d/m/Y') . '. ¿Deseas renovarla?'
            : "Hola {$this->cliente}, tu mensualidad del parqueadero {$negocio} (vehículo {$this->placa}) venció el " . $this->vence->format('d/m/Y') . '. ¿Deseas renovarla?';

        return 'https://wa.me/' . $digitos . '?text=' . rawurlencode($mensaje);
    }
}
