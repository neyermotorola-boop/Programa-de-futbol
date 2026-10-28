<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cada cuántos minutos se ofrecen horas de inicio (10, 15, 20, 30 o 60)
            $table->unsignedSmallInteger('intervalo_agenda')->default(30);
            // Minutos libres después de cada cita (limpiar, preparar): se suma a lo que ocupa la cita
            $table->unsignedSmallInteger('margen_minutos')->default(0);
            // Con cuánta anticipación mínima se puede reservar (0 = hasta el último minuto)
            $table->unsignedSmallInteger('anticipacion_minutos')->default(0);
        });

        Schema::table('servicios', function (Blueprint $table) {
            // Margen propio de este servicio; vacío = el del negocio
            $table->unsignedSmallInteger('margen_minutos')->nullable();
        });

        Schema::table('canchas', function (Blueprint $table) {
            // Horario propio de una profesional ('HH:MM'); vacío = el horario del negocio
            $table->string('hora_inicio_trabajo', 5)->nullable();
            $table->string('hora_fin_trabajo', 5)->nullable();
            // Descanso o almuerzo, en el que no se le agenda nada
            $table->string('descanso_inicio', 5)->nullable();
            $table->string('descanso_fin', 5)->nullable();
        });

        Schema::table('reservas', function (Blueprint $table) {
            // Margen que se aplicó a esta cita (queda fijo aunque luego cambie la configuración)
            $table->unsignedSmallInteger('margen_minutos')->default(0);
        });

        // Servicios de cada cita: una cita puede combinar varios (manicure + pedicure...)
        Schema::create('reserva_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained()->cascadeOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained()->nullOnDelete();
            // Se guarda lo que valía al reservar: si luego cambian el nombre o el precio, la cita no cambia
            $table->string('nombre', 50);
            $table->unsignedSmallInteger('duracion_minutos');
            $table->decimal('precio', 10, 2)->nullable();
            $table->unsignedTinyInteger('orden')->default(0);
            $table->timestamps();
        });

        // Las citas que ya existían tienen un solo servicio: se copia a la nueva tabla
        $ahora = now();
        DB::table('reservas')->whereNotNull('servicio_id')->orderBy('id')->each(function ($r) use ($ahora) {
            $servicio = DB::table('servicios')->find($r->servicio_id);
            if (! $servicio) {
                return;
            }
            DB::table('reserva_servicios')->insert([
                'reserva_id' => $r->id,
                'servicio_id' => $servicio->id,
                'nombre' => $servicio->nombre,
                'duracion_minutos' => $servicio->duracion_minutos,
                'precio' => $r->precio,
                'orden' => 0,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserva_servicios');

        Schema::table('reservas', fn (Blueprint $t) => $t->dropColumn('margen_minutos'));
        Schema::table('canchas', fn (Blueprint $t) => $t->dropColumn(['hora_inicio_trabajo', 'hora_fin_trabajo', 'descanso_inicio', 'descanso_fin']));
        Schema::table('servicios', fn (Blueprint $t) => $t->dropColumn('margen_minutos'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['intervalo_agenda', 'margen_minutos', 'anticipacion_minutos']));
    }
};
