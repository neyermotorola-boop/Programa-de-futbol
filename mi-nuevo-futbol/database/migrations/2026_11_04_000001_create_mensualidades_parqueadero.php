<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vehículos que pagan una mensualidad: entran y salen sin cobro mientras la tengan vigente
        Schema::create('parqueadero_mensualidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tipo_id')->nullable()->constrained('parqueadero_tipos')->nullOnDelete();
            $table->string('vehiculo', 30);
            $table->string('placa', 12);
            $table->string('cliente', 100);
            $table->string('telefono', 30)->nullable();
            $table->decimal('valor', 12, 2)->default(0);
            $table->date('inicio');
            // Último día cubierto por lo pagado; al día siguiente ya hay que renovar
            $table->date('vence');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'placa']);
            $table->index(['user_id', 'vence']);
        });

        // Cada pago de mensualidad (el primero y cada renovación)
        Schema::create('parqueadero_mensualidad_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensualidad_id')->constrained('parqueadero_mensualidades')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('valor', 12, 2);
            $table->date('desde');
            $table->date('hasta');
            $table->foreignId('cobrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('parqueadero_ingresos', function (Blueprint $table) {
            $table->foreignId('mensualidad_id')->nullable()->constrained('parqueadero_mensualidades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parqueadero_ingresos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mensualidad_id');
        });

        Schema::dropIfExists('parqueadero_mensualidad_pagos');
        Schema::dropIfExists('parqueadero_mensualidades');
    }
};
