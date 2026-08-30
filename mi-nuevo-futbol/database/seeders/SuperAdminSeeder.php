<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Crea la cuenta maestra que puede crear, editar y eliminar negocios.
     * Correo: superadmin@canchas.com — Contraseña: super2026
     * ¡Cámbiala más adelante desde tu propio código si vas a usar esto en producción!
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@canchas.com'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('super2026'),
                'es_superadmin' => true,
            ]
        );
    }
}