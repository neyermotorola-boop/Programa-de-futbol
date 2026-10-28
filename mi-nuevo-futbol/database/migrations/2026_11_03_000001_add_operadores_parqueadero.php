<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un operador es un usuario ligado al dueño del parqueadero (negocio_id = id del dueño)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('negocio_id')->nullable()->constrained('users')->cascadeOnDelete();
        });

        // Quién registró cada entrada y cada salida (queda aunque el operador se desactive)
        Schema::table('parqueadero_ingresos', function (Blueprint $table) {
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cobrado_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parqueadero_ingresos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registrado_por');
            $table->dropConstrainedForeignId('cobrado_por');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('negocio_id');
        });
    }
};
