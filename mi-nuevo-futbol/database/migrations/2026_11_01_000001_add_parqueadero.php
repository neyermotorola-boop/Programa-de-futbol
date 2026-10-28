<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Pasa de enum('canchas','salon') a texto para admitir 'parqueadero'
            $table->string('tipo_negocio', 20)->default('canchas')->change();

            // Tarifa del parqueadero: 'hora' o 'minuto', y cuánto se cobra por cada una
            $table->string('tarifa_tipo', 10)->default('hora');
            $table->decimal('tarifa_valor', 12, 2)->default(0);
        });

        Schema::create('parqueadero_ingresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('placa', 12);
            $table->timestamp('entrada');
            $table->timestamp('salida')->nullable();
            // Se guardan al registrar la salida, con la tarifa vigente en ese momento
            $table->unsignedInteger('minutos')->nullable();
            $table->string('tarifa_tipo', 10)->nullable();
            $table->decimal('tarifa_valor', 12, 2)->nullable();
            $table->decimal('valor_cobrado', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'salida']);
            $table->index(['user_id', 'placa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parqueadero_ingresos');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tarifa_tipo', 'tarifa_valor']);
        });
    }
};
