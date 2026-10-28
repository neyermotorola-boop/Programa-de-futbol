<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plantilla del correo de confirmación que cada negocio personaliza
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_confirmacion_activo')->default(false)->after('confirmacion_automatica');
            $table->string('email_asunto', 150)->nullable()->after('email_confirmacion_activo');
            $table->string('email_mensaje', 500)->nullable()->after('email_asunto');
            $table->string('email_pie', 300)->nullable()->after('email_mensaje');
        });

        // Un servicio puede tener su propia regla: nulo = usar la configuración general
        Schema::table('servicios', function (Blueprint $table) {
            $table->boolean('confirmacion_automatica')->nullable()->after('activo');
        });

        // Correo opcional del cliente final, para enviarle la confirmación
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('email_cliente', 120)->nullable()->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', fn (Blueprint $t) => $t->dropColumn('email_cliente'));
        Schema::table('servicios', fn (Blueprint $t) => $t->dropColumn('confirmacion_automatica'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['email_confirmacion_activo', 'email_asunto', 'email_mensaje', 'email_pie']));
    }
};
