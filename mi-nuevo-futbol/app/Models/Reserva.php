<?php

namespace App\Models;

use App\Mail\ConfirmacionReserva;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class Reserva extends Model
{
    protected $fillable = [
        'user_id',
        'cancha_id',
        'servicio_id',
        'nombre_cliente',
        'telefono',
        'email_cliente',
        'diseno_path',
        'diseno_forma',
        'radicado',
        'precio',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion_horas',
        'estado',
        'margen_minutos',
    ];

    protected $casts = [
        'fecha' => 'date',
        'precio' => 'decimal:2',
    ];

    /**
     * La cancha (o profesional/estación, en un salón) reservada.
     */
    public function cancha(): BelongsTo
    {
        return $this->belongsTo(Cancha::class);
    }

    /**
     * El servicio reservado (solo aplica a negocios tipo 'salon').
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    /**
     * Todos los servicios de la cita (puede combinar varios). El primero es también `servicio_id`.
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(ReservaServicio::class)->orderBy('orden')->orderBy('id');
    }

    /** "Manicure + Pedicure": los nombres de los servicios de la cita, o el del servicio único. */
    public function nombreServicios(string $porDefecto = 'Servicio'): string
    {
        $items = $this->relationLoaded('servicios') ? $this->servicios : $this->servicios()->get();

        return $items->isNotEmpty()
            ? $items->pluck('nombre')->implode(' + ')
            : ($this->servicio?->nombre ?? $porDefecto);
    }

    public const FORMAS_UNA = ['almendra' => 'Almendra', 'cuadrada' => 'Cuadrada', 'redonda' => 'Redonda', 'ballerina' => 'Ballerina'];

    /**
     * Número de radicado: iniciales del negocio + año + número de cita,
     * por ejemplo "BW-2026-00031". Como incluye el id, nunca se repite.
     */
    public static function radicadoPara(User $negocio, int $id): string
    {
        $palabras = preg_split('/\s+/', trim(preg_replace('/[^A-Za-z ]/', '', Str::ascii($negocio->name))), -1, PREG_SPLIT_NO_EMPTY);
        $prefijo = count($palabras) > 1
            ? implode('', array_map(fn ($p) => $p[0], array_slice($palabras, 0, 3)))
            : substr($palabras[0] ?? 'RSV', 0, 3);

        return strtoupper($prefijo) . '-' . now()->format('Y') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Reserva de ejemplo para este negocio. Nunca se guarda: sirve para mostrar
     * la plantilla del correo con datos creíbles.
     */
    public static function deMuestra(User $negocio): self
    {
        $esSalon = $negocio->tipo_negocio === 'salon';
        $servicio = $esSalon ? ($negocio->servicios->first() ?? new Servicio(['nombre' => 'Servicio de ejemplo', 'duracion_minutos' => 60])) : null;
        $minutos = $esSalon ? $servicio->duracion_minutos : 60;

        $muestra = new self([
            'nombre_cliente' => 'María Gómez',
            'fecha' => now()->addDay(),
            'hora_inicio' => '16:00:00',
            'hora_fin' => date('H:i:s', strtotime('16:00') + $minutos * 60),
            'radicado' => self::radicadoPara($negocio, 123),
        ]);
        $muestra->setRelation('usuario', $negocio);
        $muestra->setRelation('cancha', $negocio->canchas->first() ?? new Cancha(['nombre' => $esSalon ? 'Profesional' : 'Cancha 1']));
        $muestra->setRelation('servicio', $servicio);

        return $muestra;
    }

    public function urlDiseno(): ?string
    {
        return $this->diseno_path ? asset($this->diseno_path) : null;
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Envía el correo de confirmación si el negocio lo tiene activado y el
     * cliente dejó su correo. Un fallo del servidor de correo nunca debe
     * impedir que la reserva se guarde, por eso solo se registra el error.
     */
    public function notificarConfirmacion(): void
    {
        $negocio = $this->usuario;

        if (! $negocio?->email_confirmacion_activo || ! $this->email_cliente) {
            return;
        }

        try {
            Mail::to($this->email_cliente)->send(new ConfirmacionReserva($this));
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function scopeEnFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    /**
     * Genera el enlace de WhatsApp (wa.me) con el mensaje de confirmación
     * ya redactado, por si en algún momento se vuelve a usar.
     */
    public function getEnlaceWhatsappAttribute(): string
    {
        $telefono = preg_replace('/\D/', '', $this->telefono);

        $mensaje = "Hola {$this->nombre_cliente}, te confirmamos tu reserva en "
            . "{$this->cancha->nombre} para el " . $this->fecha->format('d/m/Y')
            . " de " . substr($this->hora_inicio, 0, 5) . " a " . substr($this->hora_fin, 0, 5)
            . " horas. ¡Te esperamos!";

        return "https://wa.me/{$telefono}?text=" . urlencode($mensaje);
    }
}