<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Nulo para negocios de canchas; obligatorio (en la práctica) para negocios de salón
            $table->foreignId('servicio_id')->nullable()->after('cancha_id')->constrained('servicios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('servicio_id');
        });
    }
};