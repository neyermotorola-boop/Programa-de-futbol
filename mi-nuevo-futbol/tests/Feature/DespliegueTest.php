<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Lo que necesita el sistema para funcionar bien en un hosting (Railway): cuenta de superadmin
 * sin clave pública en producción, https detrás del proxy y página de salud.
 */
class DespliegueTest extends TestCase
{
    use RefreshDatabase;

    public function test_en_desarrollo_el_superadmin_conserva_la_clave_de_siempre(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $admin = User::where('email', 'superadmin@canchas.com')->firstOrFail();
        $this->assertTrue($admin->es_superadmin);
        $this->assertTrue(Hash::check('super2026', $admin->password));
    }

    public function test_el_superadmin_usa_el_correo_y_la_clave_de_las_variables(): void
    {
        config(['canchas.superadmin.email' => 'dueno@miempresa.com', 'canchas.superadmin.password' => 'ClaveMuySegura9']);

        $this->seed(SuperAdminSeeder::class);

        $admin = User::where('email', 'dueno@miempresa.com')->firstOrFail();
        $this->assertTrue(Hash::check('ClaveMuySegura9', $admin->password));
        $this->assertSame(0, User::where('email', 'superadmin@canchas.com')->count());
    }

    public function test_en_produccion_sin_clave_definida_no_se_usa_la_clave_publica(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        (new SuperAdminSeeder())->run();   // sin consola: el aviso de la clave generada se omite

        $admin = User::where('email', 'superadmin@canchas.com')->firstOrFail();
        $this->assertFalse(Hash::check('super2026', $admin->password), 'la clave pública no debe funcionar en producción');
    }

    public function test_volver_a_ejecutar_el_seeder_no_pisa_una_clave_cambiada(): void
    {
        $this->seed(SuperAdminSeeder::class);
        User::where('email', 'superadmin@canchas.com')->update(['password' => Hash::make('NuevaClave123')]);

        $this->seed(SuperAdminSeeder::class);

        $admin = User::where('email', 'superadmin@canchas.com')->firstOrFail();
        $this->assertTrue(Hash::check('NuevaClave123', $admin->password));
        $this->assertSame(1, User::where('email', 'superadmin@canchas.com')->count());
    }

    public function test_detras_del_proxy_los_enlaces_salen_en_https(): void
    {
        $this->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'miapp.up.railway.app'])
            ->get('/login?tipo=canchas')
            ->assertOk()
            ->assertSee('https://', false)
            ->assertDontSee('action="http://', false);
    }

    public function test_la_pagina_de_salud_responde_sin_base_de_datos(): void
    {
        $this->get('/up')->assertOk();
    }
}
