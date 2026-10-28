<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El negocio decide en qué servicios el cliente puede subir la foto de su diseño
        Schema::table('servicios', function (Blueprint $table) {
            $table->boolean('permite_diseno')->default(false)->after('confirmacion_automatica');
        });

        // La foto y la forma de uña que el cliente eligió, para que el salón las vea
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('diseno_path')->nullable()->after('email_cliente');
            $table->string('diseno_forma', 20)->nullable()->after('diseno_path');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', fn (Blueprint $t) => $t->dropColumn(['diseno_path', 'diseno_forma']));
        Schema::table('servicios', fn (Blueprint $t) => $t->dropColumn('permite_diseno'));
    }
};
