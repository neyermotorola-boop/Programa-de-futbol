<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // "Apodo" único para la URL pública de reservas, ej: /reservas/el-golazo
            $table->string('slug')->unique()->nullable()->after('name');

            // Cada negocio define su propio horario de atención
            $table->string('hora_apertura')->default('06:00')->after('slug');
            $table->string('hora_cierre')->default('23:00')->after('hora_apertura');
            $table->unsignedTinyInteger('max_horas_por_reserva')->default(4)->after('hora_cierre');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['slug', 'hora_apertura', 'hora_cierre', 'max_horas_por_reserva']);
        });
    }
};