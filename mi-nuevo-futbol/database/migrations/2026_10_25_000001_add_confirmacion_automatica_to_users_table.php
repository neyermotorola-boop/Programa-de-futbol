<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // true  = las reservas nuevas quedan confirmadas al instante
            // false = quedan pendientes hasta que el negocio las confirme
            $table->boolean('confirmacion_automatica')->default(false)->after('tipo_puesto');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('confirmacion_automatica');
        });
    }
};
