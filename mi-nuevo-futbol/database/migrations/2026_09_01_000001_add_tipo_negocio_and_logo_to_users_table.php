<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 'canchas' = negocio de canchas deportivas (horario libre por horas)
            // 'salon'   = negocio de belleza/manicure (servicios con duración fija)
            $table->enum('tipo_negocio', ['canchas', 'salon'])->default('canchas')->after('slug');

            // Ruta del logo subido por el super admin (opcional)
            $table->string('logo_path')->nullable()->after('tipo_negocio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tipo_negocio', 'logo_path']);
        });
    }
};