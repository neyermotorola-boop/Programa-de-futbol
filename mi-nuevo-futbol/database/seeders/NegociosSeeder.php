<?php

namespace Database\Seeders;

use App\Models\Cancha;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NegociosSeeder extends Seeder
{
    /**
     * Crea negocios de ejemplo. Para agregar un negocio nuevo (uno de tus
     * ~10 clientes), copia y pega uno de estos bloques completos, cambiando
     * los valores: email, contraseña, slug (para su URL pública), horario,
     * y los nombres de sus canchas.
     */
    public function run(): void
    {
        // -----------------------------------------------------------------
        // Negocio 1: tu propia cancha de prueba
        // Panel privado: /login → /admin/reservas
        // Página pública para tus clientes: /reservas/mi-cancha
        // -----------------------------------------------------------------
        $negocio1 = User::firstOrCreate(
            ['email' => 'admin@micancha.com'],
            [
                'name' => 'Mi Cancha',
                'slug' => 'mi-cancha',
                'password' => Hash::make('cancha2026'),
                'hora_apertura' => '13:00',
                'hora_cierre' => '23:00',
                'max_horas_por_reserva' => 4,
            ]
        );

        Cancha::firstOrCreate(['user_id' => $negocio1->id, 'nombre' => 'Cancha 1']);
        Cancha::firstOrCreate(['user_id' => $negocio1->id, 'nombre' => 'Cancha 2']);

        // -----------------------------------------------------------------
        // Negocio 2: otro negocio de ejemplo, totalmente independiente
        // Página pública: /reservas/el-golazo
        // -----------------------------------------------------------------
        $negocio2 = User::firstOrCreate(
            ['email' => 'admin@elgolazo.com'],
            [
                'name' => 'El Golazo',
                'slug' => 'el-golazo',
                'password' => Hash::make('cancha2026'),
                'hora_apertura' => '06:00',
                'hora_cierre' => '22:00',
                'max_horas_por_reserva' => 3,
            ]
        );

        Cancha::firstOrCreate(['user_id' => $negocio2->id, 'nombre' => 'Cancha A']);
        Cancha::firstOrCreate(['user_id' => $negocio2->id, 'nombre' => 'Cancha B']);
        Cancha::firstOrCreate(['user_id' => $negocio2->id, 'nombre' => 'Cancha C']);
    }
}