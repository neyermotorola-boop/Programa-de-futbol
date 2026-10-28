<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Marca, contacto y agenda que cada negocio configura desde su panel
        Schema::table('users', function (Blueprint $table) {
            $table->string('razon_social')->nullable()->after('name');
            $table->string('eslogan', 150)->nullable()->after('razon_social');
            $table->string('telefono_contacto', 30)->nullable()->after('eslogan');
            $table->string('direccion')->nullable()->after('telefono_contacto');
            $table->string('color_primario', 7)->default('#2F6B3C')->after('logo_path');
            $table->string('color_acento', 7)->default('#F2A93B')->after('color_primario');
            // Días de atención (1 = lunes ... 7 = domingo)
            $table->json('dias_atencion')->nullable()->after('hora_cierre');
            // Salones: cuántas sillas / consultorios / cabinas atienden a la vez
            $table->unsignedTinyInteger('numero_puestos')->default(1)->after('max_horas_por_reserva');
            $table->string('tipo_puesto', 20)->default('silla')->after('numero_puestos');
        });

        // Ficha de cada cancha (o profesional, en un salón)
        Schema::table('canchas', function (Blueprint $table) {
            $table->string('superficie', 20)->default('sintetica')->after('nombre'); // sintetica, natural, arena, cemento
            $table->string('tamano', 20)->default('futbol5')->after('superficie');   // futbol5, futbol7, futbol11
            $table->boolean('iluminacion')->default(true)->after('tamano');
            $table->boolean('techada')->default(false)->after('iluminacion');
            $table->decimal('precio_hora', 10, 2)->nullable()->after('techada');
            $table->string('especialidad', 80)->nullable()->after('precio_hora');     // profesionales de salón
            $table->json('dias_disponibles')->nullable()->after('especialidad');      // nulo = todos los días del negocio
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->string('descripcion', 200)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('descripcion');
        });

        Schema::table('canchas', function (Blueprint $table) {
            $table->dropColumn(['superficie', 'tamano', 'iluminacion', 'techada', 'precio_hora', 'especialidad', 'dias_disponibles']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['razon_social', 'eslogan', 'telefono_contacto', 'direccion', 'color_primario', 'color_acento', 'dias_atencion', 'numero_puestos', 'tipo_puesto']);
        });
    }
};
