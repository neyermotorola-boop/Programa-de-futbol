<?php

namespace App\Console\Commands;

use App\Mail\AvisoMensualidades;
use App\Models\Mensualidad;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Envía a cada dueño de parqueadero un correo con sus mensualidades que se cumplen en 3 días,
 * hoy, o que vencieron ayer. Se programa una vez al día (ver routes/console.php).
 */
class AvisarMensualidades extends Command
{
    protected $signature = 'parqueadero:avisar-mensualidades';

    protected $description = 'Avisa por correo a los parqueaderos sobre mensualidades por vencer';

    /** Días restantes en los que se manda aviso: faltan 3, vence hoy, venció ayer. */
    private const DIAS_AVISO = [3, 0, -1];

    public function handle(): int
    {
        $enviados = 0;

        User::where('tipo_negocio', 'parqueadero')->whereNull('negocio_id')->where('activo', true)->each(function (User $dueno) use (&$enviados) {
            $pendientes = $dueno->mensualidades()->where('activa', true)->get()
                ->filter(fn (Mensualidad $m) => in_array($m->diasRestantes(), self::DIAS_AVISO, true))
                ->values();

            if ($pendientes->isEmpty()) {
                return;
            }

            Mail::to($dueno->email)->send(new AvisoMensualidades($dueno, $pendientes));
            $this->line("{$dueno->name}: {$pendientes->count()} mensualidades avisadas a {$dueno->email}");
            $enviados++;
        });

        $this->info($enviados ? "Avisos enviados: {$enviados}" : 'No hay mensualidades por avisar hoy.');

        return self::SUCCESS;
    }
}
