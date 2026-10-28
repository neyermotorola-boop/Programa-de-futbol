<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Número que el cliente recibe como comprobante, ej: BW-2026-00031
            $table->string('radicado', 24)->nullable()->unique()->after('id');
            // Precio de la cita al momento de reservar: así los ingresos del dashboard
            // no cambian si después el negocio modifica los precios.
            $table->decimal('precio', 12, 2)->nullable()->after('duracion_horas');
        });

        // Citas que ya existían: radicado y precio con los valores actuales
        $negocios = DB::table('users')->get()->keyBy('id');
        $servicios = DB::table('servicios')->get()->keyBy('id');
        $canchas = DB::table('canchas')->get()->keyBy('id');

        foreach (DB::table('reservas')->get() as $r) {
            $negocio = $negocios[$r->user_id] ?? null;
            $precio = $r->servicio_id
                ? ($servicios[$r->servicio_id]->precio ?? null)
                : (($canchas[$r->cancha_id]->precio_hora ?? null) ? $canchas[$r->cancha_id]->precio_hora * $r->duracion_horas : null);

            DB::table('reservas')->where('id', $r->id)->update([
                'radicado' => self::radicado($negocio->name ?? '', $r->id, substr((string) $r->created_at, 0, 4) ?: date('Y')),
                'precio' => $precio,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropUnique(['radicado']);
            $table->dropColumn(['radicado', 'precio']);
        });
    }

    /** Debe producir lo mismo que Reserva::radicadoPara(). */
    private static function radicado(string $nombreNegocio, int $id, string $anio): string
    {
        $palabras = preg_split('/\s+/', trim(preg_replace('/[^A-Za-z ]/', '', Str::ascii($nombreNegocio))), -1, PREG_SPLIT_NO_EMPTY);
        $prefijo = count($palabras) > 1
            ? implode('', array_map(fn ($p) => $p[0], array_slice($palabras, 0, 3)))
            : substr($palabras[0] ?? 'RSV', 0, 3);

        return strtoupper($prefijo) . '-' . $anio . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }
};
