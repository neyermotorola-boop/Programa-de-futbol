<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada parqueadero define sus tipos de vehículo (carro, moto, cicla...) y la tarifa de cada uno
        Schema::create('parqueadero_tipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nombre', 30);
            $table->string('tarifa_tipo', 10)->default('hora');
            $table->decimal('tarifa_valor', 12, 2)->default(0);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::table('parqueadero_ingresos', function (Blueprint $table) {
            $table->foreignId('tipo_id')->nullable()->after('user_id')->constrained('parqueadero_tipos')->nullOnDelete();
            // Nombre del tipo al momento del ingreso: se conserva aunque el tipo se renombre o se borre
            $table->string('vehiculo', 30)->default('Carro')->after('placa');
        });

        // Parqueaderos que ya existían: su tarifa única pasa a ser la de "Carro", y se agregan Moto y Cicla
        $ahora = now();
        foreach (DB::table('users')->where('tipo_negocio', 'parqueadero')->get() as $negocio) {
            $carro = DB::table('parqueadero_tipos')->insertGetId([
                'user_id' => $negocio->id, 'nombre' => 'Carro', 'tarifa_tipo' => $negocio->tarifa_tipo ?? 'hora',
                'tarifa_valor' => $negocio->tarifa_valor ?? 0, 'orden' => 1, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
            foreach (['Moto' => 2, 'Cicla' => 3] as $nombre => $orden) {
                DB::table('parqueadero_tipos')->insert([
                    'user_id' => $negocio->id, 'nombre' => $nombre, 'tarifa_tipo' => 'hora', 'tarifa_valor' => 0,
                    'orden' => $orden, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }
            DB::table('parqueadero_ingresos')->where('user_id', $negocio->id)->update(['tipo_id' => $carro, 'vehiculo' => 'Carro']);
        }

        // La tarifa ya no es una sola por negocio
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tarifa_tipo', 'tarifa_valor']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tarifa_tipo', 10)->default('hora');
            $table->decimal('tarifa_valor', 12, 2)->default(0);
        });

        Schema::table('parqueadero_ingresos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_id');
            $table->dropColumn('vehiculo');
        });

        Schema::dropIfExists('parqueadero_tipos');
    }
};
