<?php

namespace App\Console\Commands;

use App\Mail\ConfirmacionReserva;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Envía a una dirección el correo de confirmación de cita con datos de ejemplo,
 * para ver cómo queda la plantilla de un negocio. No crea ninguna reserva.
 *
 *   php artisan correo:prueba tucorreo@gmail.com --negocio=beautiful-woman
 */
class CorreoPrueba extends Command
{
    protected $signature = 'correo:prueba {destino : Correo que recibirá la prueba} {--negocio= : Apodo (slug) del negocio; por defecto, el primero}';

    protected $description = 'Envía un correo de confirmación de cita de ejemplo para revisar la plantilla';

    public function handle(): int
    {
        $destino = $this->argument('destino');

        if (! filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            $this->error("«{$destino}» no es un correo válido.");

            return self::FAILURE;
        }

        $negocio = User::where('es_superadmin', false)
            ->when($this->option('negocio'), fn ($q, $slug) => $q->where('slug', $slug))
            ->first();

        if (! $negocio) {
            $this->error('No encontré ese negocio.');

            return self::FAILURE;
        }

        $negocio->load(['canchas', 'servicios']);
        Mail::to($destino)->send(new ConfirmacionReserva(Reserva::deMuestra($negocio)));

        $this->info("Correo de prueba de «{$negocio->name}» enviado a {$destino} (servidor: " . config('mail.default') . ').');

        return self::SUCCESS;
    }
}
