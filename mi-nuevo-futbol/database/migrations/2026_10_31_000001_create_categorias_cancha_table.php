<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorías que cada negocio define y edita: deportes (fútbol, tenis...) y superficies (grama, arena...)
        Schema::create('categorias_cancha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tipo', 12);                       // 'deporte' | 'superficie'
            $table->string('nombre', 40);
            // deporte: futbol | tenis | padel | voleibol | generico (cómo se dibuja en 3D)
            // superficie: cesped | arena | dura | arcilla (qué textura se usa en 3D)
            $table->string('base', 20)->default('generico');
            $table->string('color', 7)->nullable();           // solo superficies: color del piso
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'tipo', 'nombre']);
        });

        Schema::table('canchas', function (Blueprint $table) {
            $table->foreignId('deporte_id')->nullable()->after('nombre')->constrained('categorias_cancha')->nullOnDelete();
            $table->foreignId('superficie_id')->nullable()->after('deporte_id')->constrained('categorias_cancha')->nullOnDelete();
            // Foto real de la cancha, que se usa dentro del modelo 3D como entorno o como piso
            $table->string('foto_path')->nullable();
            $table->string('foto_modo', 10)->default('entorno');
        });

        // Negocios de canchas que ya existían: se les crean las categorías y se asigna cada cancha
        $deportes = ['Fútbol' => 'futbol', 'Tenis' => 'tenis', 'Pádel' => 'padel', 'Voleibol' => 'voleibol'];
        $superficies = [
            'Grama sintética' => ['cesped', '#2F8F3F'],
            'Grama natural' => ['cesped', '#3F8A35'],
            'Arena' => ['arena', '#D8B67A'],
            'Cemento' => ['dura', '#2F6AA3'],
            'Arcilla' => ['arcilla', '#C4673A'],
        ];
        $claveAntigua = ['sintetica' => 'Grama sintética', 'natural' => 'Grama natural', 'arena' => 'Arena', 'cemento' => 'Cemento'];

        foreach (DB::table('users')->where('es_superadmin', false)->where('tipo_negocio', 'canchas')->get() as $u) {
            $ids = ['deporte' => [], 'superficie' => []];
            $orden = 0;
            foreach ($deportes as $nombre => $base) {
                $ids['deporte'][$nombre] = DB::table('categorias_cancha')->insertGetId([
                    'user_id' => $u->id, 'tipo' => 'deporte', 'nombre' => $nombre, 'base' => $base, 'orden' => $orden++,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach ($superficies as $nombre => [$base, $color]) {
                $ids['superficie'][$nombre] = DB::table('categorias_cancha')->insertGetId([
                    'user_id' => $u->id, 'tipo' => 'superficie', 'nombre' => $nombre, 'base' => $base, 'color' => $color, 'orden' => $orden++,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            foreach (DB::table('canchas')->where('user_id', $u->id)->get() as $c) {
                DB::table('canchas')->where('id', $c->id)->update([
                    'deporte_id' => $ids['deporte']['Fútbol'],
                    'superficie_id' => $ids['superficie'][$claveAntigua[$c->superficie] ?? 'Grama sintética'],
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('canchas', function (Blueprint $table) {
            $table->dropColumn(['foto_path', 'foto_modo']);
            $table->dropConstrainedForeignId('superficie_id');
            $table->dropConstrainedForeignId('deporte_id');
        });
        Schema::dropIfExists('categorias_cancha');
    }
};
