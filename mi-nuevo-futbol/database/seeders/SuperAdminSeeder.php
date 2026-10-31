<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Crea la cuenta maestra que puede crear, editar y eliminar negocios.
     *
     * Correo y clave salen de SUPERADMIN_EMAIL y SUPERADMIN_PASSWORD. Si no se definen:
     *  - en desarrollo: superadmin@canchas.com / super2026;
     *  - en producción: se genera una clave aleatoria y se muestra una sola vez.
     * Si la cuenta ya existe no se modifica (así no se pisa una clave que ya cambiaste).
     */
    public function run(): void
    {
        $correo = config('canchas.superadmin.email');
        $clave = config('canchas.superadmin.password');
        $generada = false;

        if (! $clave) {
            if (app()->isProduction()) {
                $clave = Str::random(20);
                $generada = true;
            } else {
                $clave = 'super2026';
            }
        }

        $usuario = User::firstOrCreate(
            ['email' => $correo],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make($clave),
                'es_superadmin' => true,
            ]
        );

        if ($usuario->wasRecentlyCreated && $generada) {
            $this->command?->warn("Superadmin creado. Correo: {$correo} — Contraseña (guárdala, no se vuelve a mostrar): {$clave}");
        }
    }
}
