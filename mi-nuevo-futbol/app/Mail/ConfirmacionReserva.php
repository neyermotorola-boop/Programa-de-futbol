<?php

namespace App\Mail;

use App\Models\Reserva;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Correo de confirmación que recibe el cliente final. Usa la plantilla y los
 * colores del negocio.
 */
class ConfirmacionReserva extends Mailable
{
    public function __construct(public Reserva $reserva)
    {
        $this->reserva->loadMissing(['usuario', 'cancha', 'servicio', 'servicios']);
    }

    public function envelope(): Envelope
    {
        $negocio = $this->reserva->usuario;

        return new Envelope(
            subject: User::rellenarPlantilla($negocio->plantillaEmail()['asunto'], self::datos($this->reserva)),
            // El correo sale a nombre del negocio, no de la plataforma
            from: new Address(config('mail.from.address'), $negocio->name),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.confirmacion', with: self::vista($this->reserva));
    }

    /** Valores para los marcadores {negocio}, {cliente}, {servicio}, {fecha} y {hora}. */
    public static function datos(Reserva $r): array
    {
        $esSalon = $r->usuario->tipo_negocio === 'salon';

        return [
            'negocio' => $r->usuario->name,
            'cliente' => $r->nombre_cliente,
            'servicio' => $esSalon ? $r->nombreServicios('') : $r->cancha->nombre,
            'fecha' => ucfirst($r->fecha->locale('es')->translatedFormat('l j \d\e F \d\e Y')),
            'hora' => date('g:i A', strtotime($r->hora_inicio)) . ' – ' . date('g:i A', strtotime($r->hora_fin)),
        ];
    }

    /** Todo lo que necesita la plantilla del correo (también se usa para la vista previa). */
    public static function vista(Reserva $r): array
    {
        $negocio = $r->usuario;
        $datos = self::datos($r);
        $plantilla = $negocio->plantillaEmail();
        $esSalon = $negocio->tipo_negocio === 'salon';

        return [
            'negocio' => $negocio,
            'paleta' => $negocio->paletaCorreo(),
            'logo' => $negocio->urlLogo(),
            'mensaje' => User::rellenarPlantilla($plantilla['mensaje'], $datos),
            'pie' => User::rellenarPlantilla($plantilla['pie'], $datos),
            'filas' => array_values(array_filter([
                $r->radicado ? ['Radicado', $r->radicado] : null,
                ['Cliente', $datos['cliente']],
                [$esSalon ? 'Servicio' : 'Cancha', $datos['servicio']],
                $esSalon ? ['Profesional', $r->cancha->nombre] : null,
                ['Fecha', $datos['fecha']],
                ['Horario', $datos['hora']],
            ])),
        ];
    }
}
