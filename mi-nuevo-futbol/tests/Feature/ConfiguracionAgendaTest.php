<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Configuración del salón (horario, margen, profesionales) y horas pasadas en canchas.
 */
class ConfiguracionAgendaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-10 08:00:00', 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function negocio(string $tipo): User
    {
        return User::create([
            'name' => 'Negocio', 'email' => $tipo . '@prueba.com', 'password' => 'secreto123', 'slug' => $tipo . '-prueba',
            'tipo_negocio' => $tipo, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'numero_puestos' => 3,
            'tipo_puesto' => 'silla', 'activo' => true, 'max_horas_por_reserva' => 3,
        ]);
    }

    // ------------------------------------------------------------------ salón: horario y capacidad

    public function test_el_horario_del_salon_guarda_intervalo_margen_y_anticipacion(): void
    {
        $salon = $this->negocio('salon');

        $this->actingAs($salon)->post(route('configuracion.horario'), [
            'hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'dias_atencion' => [1, 2, 3, 4, 5],
            'numero_puestos' => 3, 'tipo_puesto' => 'silla',
            'intervalo_agenda' => 15, 'margen_minutos' => 10, 'anticipacion_minutos' => 60,
        ])->assertSessionHasNoErrors();

        $salon->refresh();
        $this->assertSame(15, (int) $salon->intervalo_agenda);
        $this->assertSame(10, (int) $salon->margen_minutos);
        $this->assertSame(60, (int) $salon->anticipacion_minutos);
    }

    public function test_valores_de_agenda_invalidos_se_rechazan(): void
    {
        $salon = $this->negocio('salon');
        $base = ['hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'dias_atencion' => [1], 'numero_puestos' => 3, 'tipo_puesto' => 'silla',
            'intervalo_agenda' => 30, 'margen_minutos' => 0, 'anticipacion_minutos' => 0];

        $this->actingAs($salon)->post(route('configuracion.horario'), ['intervalo_agenda' => 7] + $base)->assertSessionHasErrors('intervalo_agenda');
        $this->actingAs($salon)->post(route('configuracion.horario'), ['margen_minutos' => 500] + $base)->assertSessionHasErrors('margen_minutos');
        $this->actingAs($salon)->post(route('configuracion.horario'), ['anticipacion_minutos' => 45] + $base)->assertSessionHasErrors('anticipacion_minutos');
    }

    // ------------------------------------------------------------------ profesionales

    private function profesional(array $extra = [])
    {
        return $this->post(route('configuracion.recursos.agregar'), $extra + ['nombre' => 'Ana', 'dias_disponibles' => [1, 2, 3], 'activa' => 1]);
    }

    public function test_se_guarda_el_horario_propio_y_el_descanso_de_una_profesional(): void
    {
        $salon = $this->negocio('salon');

        $this->actingAs($salon);
        $this->profesional(['hora_inicio_trabajo' => '10:00', 'hora_fin_trabajo' => '16:00', 'descanso_inicio' => '12:00', 'descanso_fin' => '13:00'])
            ->assertSessionHasNoErrors();

        $ana = Cancha::firstOrFail();
        $this->assertSame(['10:00', '16:00'], $ana->ventanaTrabajo($salon));
        $this->assertSame(['12:00', '13:00'], $ana->descanso());
    }

    public function test_sin_horario_propio_usa_el_del_negocio(): void
    {
        $salon = $this->negocio('salon');

        $this->actingAs($salon);
        $this->profesional()->assertSessionHasNoErrors();

        $ana = Cancha::firstOrFail();
        $this->assertSame(['09:00', '18:00'], $ana->ventanaTrabajo($salon));
        $this->assertNull($ana->descanso());
        $this->assertNull($ana->textoHorarioPropio());
    }

    public function test_el_horario_de_una_profesional_debe_caber_en_el_del_negocio(): void
    {
        $this->actingAs($this->negocio('salon'));

        $this->profesional(['hora_inicio_trabajo' => '07:00', 'hora_fin_trabajo' => '15:00'])->assertSessionHasErrors('hora_inicio_trabajo');
        $this->profesional(['hora_inicio_trabajo' => '10:00', 'hora_fin_trabajo' => '20:00'])->assertSessionHasErrors('hora_inicio_trabajo');
        $this->assertSame(0, Cancha::count());
    }

    public function test_el_fin_debe_ser_despues_del_inicio_y_van_los_dos_juntos(): void
    {
        $this->actingAs($this->negocio('salon'));

        $this->profesional(['hora_inicio_trabajo' => '15:00', 'hora_fin_trabajo' => '10:00'])->assertSessionHasErrors('hora_fin_trabajo');
        $this->profesional(['hora_inicio_trabajo' => '10:00'])->assertSessionHasErrors('hora_fin_trabajo');
        $this->profesional(['descanso_inicio' => '12:00'])->assertSessionHasErrors('descanso_fin');
        $this->assertSame(0, Cancha::count());
    }

    public function test_el_descanso_debe_estar_dentro_de_su_horario(): void
    {
        $this->actingAs($this->negocio('salon'));

        $this->profesional(['hora_inicio_trabajo' => '10:00', 'hora_fin_trabajo' => '14:00', 'descanso_inicio' => '13:30', 'descanso_fin' => '15:00'])
            ->assertSessionHasErrors('descanso_inicio');
        $this->profesional(['descanso_inicio' => '08:00', 'descanso_fin' => '09:30'])->assertSessionHasErrors('descanso_inicio');
        $this->assertSame(0, Cancha::count());
    }

    public function test_una_profesional_necesita_al_menos_un_dia(): void
    {
        $this->actingAs($this->negocio('salon'));

        $this->profesional(['dias_disponibles' => []])->assertSessionHasErrors('dias_disponibles');
        $this->assertSame(0, Cancha::count());
    }

    public function test_se_puede_quitar_el_descanso_dejandolo_vacio(): void
    {
        $salon = $this->negocio('salon');
        $ana = Cancha::create(['user_id' => $salon->id, 'nombre' => 'Ana', 'descanso_inicio' => '12:00', 'descanso_fin' => '13:00']);

        $this->actingAs($salon)->post(route('configuracion.recursos.actualizar', $ana), [
            'nombre' => 'Ana', 'dias_disponibles' => [1, 2], 'activa' => 1, 'descanso_inicio' => '', 'descanso_fin' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($ana->fresh()->descanso());
    }

    // ------------------------------------------------------------------ servicios: margen propio

    public function test_un_servicio_guarda_su_margen_y_vacio_significa_el_del_negocio(): void
    {
        $salon = $this->negocio('salon');
        $this->actingAs($salon);

        $this->post(route('configuracion.servicios.agregar'), ['nombre' => 'Keratina', 'duracion_minutos' => 90, 'margen_minutos' => 20, 'activo' => 1])->assertSessionHasNoErrors();
        $this->post(route('configuracion.servicios.agregar'), ['nombre' => 'Corte', 'duracion_minutos' => 30, 'margen_minutos' => '', 'activo' => 1])->assertSessionHasNoErrors();
        $this->post(route('configuracion.servicios.agregar'), ['nombre' => 'Raro', 'duracion_minutos' => 30, 'margen_minutos' => 999, 'activo' => 1])->assertSessionHasErrors('margen_minutos');

        $salon->update(['margen_minutos' => 5]);
        $this->assertSame(20, $salon->servicios()->where('nombre', 'Keratina')->first()->margenPara($salon));
        $this->assertSame(5, $salon->servicios()->where('nombre', 'Corte')->first()->margenPara($salon));
    }

    // ------------------------------------------------------------------ canchas: horas pasadas

    public function test_en_canchas_no_se_reserva_una_hora_que_ya_paso(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 14:10:00', 'America/Bogota'));
        $negocio = $this->negocio('canchas');
        $cancha = Cancha::create(['user_id' => $negocio->id, 'nombre' => 'Cancha 1']);

        $datos = ['nombre_cliente' => 'Pedro', 'telefono' => '3001234567', 'cancha_id' => $cancha->id, 'fecha' => '2026-11-12', 'duracion_horas' => 1];

        $this->post("/reservas/{$negocio->slug}", $datos + ['hora_inicio' => '12:00'])->assertSessionHasErrors('hora_inicio');
        $this->assertSame(0, Reserva::count());

        $this->post("/reservas/{$negocio->slug}", $datos + ['hora_inicio' => '15:00'])->assertSessionHasNoErrors();
        $this->assertSame(1, Reserva::count());
    }

    public function test_en_canchas_la_disponibilidad_informa_desde_que_hora_se_puede_reservar(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 14:10:00', 'America/Bogota'));
        $negocio = $this->negocio('canchas');
        $cancha = Cancha::create(['user_id' => $negocio->id, 'nombre' => 'Cancha 1']);

        $hoy = $this->getJson("/reservas/{$negocio->slug}/disponibilidad?fecha=2026-11-12&cancha_id={$cancha->id}")->assertOk();
        $this->assertSame('14:10', $hoy->json('minimo'));

        $manana = $this->getJson("/reservas/{$negocio->slug}/disponibilidad?fecha=2026-11-13&cancha_id={$cancha->id}")->assertOk();
        $this->assertNull($manana->json('minimo'));
    }

    public function test_en_canchas_no_se_reserva_una_fecha_pasada(): void
    {
        $negocio = $this->negocio('canchas');
        $cancha = Cancha::create(['user_id' => $negocio->id, 'nombre' => 'Cancha 1']);

        $this->post("/reservas/{$negocio->slug}", [
            'nombre_cliente' => 'Pedro', 'telefono' => '3001234567', 'cancha_id' => $cancha->id,
            'fecha' => '2026-11-09', 'hora_inicio' => '15:00', 'duracion_horas' => 1,
        ])->assertSessionHasErrors('fecha');
    }

    // ------------------------------------------------------------------ página pública

    public function test_la_pagina_publica_del_salon_carga_con_varios_servicios_y_horario_de_la_profesional(): void
    {
        $salon = $this->negocio('salon');
        Cancha::create(['user_id' => $salon->id, 'nombre' => 'Ana', 'hora_inicio_trabajo' => '10:00', 'hora_fin_trabajo' => '15:00']);
        \App\Models\Servicio::create(['user_id' => $salon->id, 'nombre' => 'Manicure', 'duracion_minutos' => 45, 'precio' => 30000, 'activo' => true]);

        $this->get("/reservas/{$salon->slug}")
            ->assertOk()
            ->assertSee('Puedes elegir más de uno')
            ->assertSee('10:00 – 15:00')
            ->assertSee('id="servicioInputs"', false);
    }
}
