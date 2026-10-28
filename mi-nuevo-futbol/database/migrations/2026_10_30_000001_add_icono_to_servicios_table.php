<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            // Ícono elegido por el negocio para este servicio. Nulo = se escoge solo, según el nombre.
            $table->string('icono', 30)->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('servicios', fn (Blueprint $t) => $t->dropColumn('icono'));
    }
};
