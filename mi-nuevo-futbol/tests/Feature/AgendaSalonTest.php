<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Agenda del salón: duración de los servicios, combos, margen, horarios de las profesionales,
 * horas pasadas, anticipación y capacidad (sillas).
 */
class AgendaSalonTest extends TestCase
{
    use RefreshDatabase;

    private const FECHA = '2026-11-12';   // jueves

    private User $salon;
    private Cancha $ana;
    private Cancha $beto;
    private Servicio $manicure;   // 45 min, $30.000
    private Servicio $pedicure;   // 60 min, $40.000
    private Servicio $tinte;      // 120 min, sin precio

    protected function setUp(): void
    {
        parent::setUp();

        // "Ahora" es el martes 10 de noviembre a las 8:00 en Bogotá: la fecha de las pruebas queda en el futuro
        Carbon::setTestNow(Carbon::parse('2026-11-10 08:00:00', 'America/Bogota'));

        $this->salon = $this->crearSalon();
        $this->ana = Cancha::create(['user_id' => $this->salon->id, 'nombre' => 'Ana']);
        $this->beto = Cancha::create(['user_id' => $this->salon->id, 'nombre' => 'Beto']);
        $this->manicure = $this->servicio('Manicure', 45, 30000);
        $this->pedicure = $this->servicio('Pedicure', 60, 40000);
        $this->tinte = $this->servicio('Tinte', 120, null);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearSalon(array $extra = []): User
    {
        return User::create($extra + [
            'name' => 'Salón de prueba',
            'email' => 'salon@prueba.com',
            'password' => 'secreto123',
            'slug' => 'salon-prueba',
            'tipo_negocio' => 'salon',
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'numero_puestos' => 5,
            'tipo_puesto' => 'silla',
            'activo' => true,
        ]);
    }

    private function servicio(string $nombre, int $minutos, ?int $precio, array $extra = []): Servicio
    {
        return Servicio::create($extra + ['user_id' => $this->salon->id, 'nombre' => $nombre, 'duracion_minutos' => $minutos, 'precio' => $precio, 'activo' => true]);
    }

    /** Horas que el cliente ve para esos servicios: ['09:00' => true, '09:30' => false, ...] */
    private function horas(array $servicios, string $profesional = 'cualquiera', string $fecha = self::FECHA): array
    {
        $r = $this->getJson("/reservas/{$this->salon->slug}/disponibilidad-servicio?" . http_build_query([
            'fecha' => $fecha,
            'servicio_ids' => array_map(fn ($s) => $s->id, $servicios),
            'profesional_id' => $profesional,
        ]))->assertOk();

        return collect($r->json('horas'))->mapWithKeys(fn ($h) => [$h['hora'] => $h['disponible']])->all();
    }

    private function reservar(array $servicios, string $hora, $profesional = 'cualquiera', string $fecha = self::FECHA)
    {
        return $this->post("/reservas/{$this->salon->slug}", [
            'nombre_cliente' => 'Cliente Prueba',
            'telefono' => '3001234567',
            'servicio_ids' => array_map(fn ($s) => $s->id, $servicios),
            'profesional_id' => (string) ($profesional instanceof Cancha ? $profesional->id : $profesional),
            'fecha' => $fecha,
            'hora_inicio' => $hora,
        ]);
    }

    /** Una cita ya existente (sin pasar por la página), para armar escenarios. */
    private function cita(Cancha $prof, string $inicio, string $fin, int $margen = 0, string $estado = 'confirmada'): Reserva
    {
        return Reserva::create([
            'user_id' => $this->salon->id, 'cancha_id' => $prof->id, 'servicio_id' => $this->manicure->id,
            'nombre_cliente' => 'Otra', 'telefono' => '3000000000', 'fecha' => self::FECHA,
            'hora_inicio' => $inicio . ':00', 'hora_fin' => $fin . ':00', 'duracion_horas' => 1,
            'margen_minutos' => $margen, 'estado' => $estado,
        ]);
    }

    // ------------------------------------------------------------------ duración

    public function test_la_duracion_del_servicio_decide_que_horas_caben(): void
    {
        $this->cita($this->ana, '10:00', '10:45');

        $h = $this->horas([$this->manicure], (string) $this->ana->id);

        $this->assertTrue($h['09:00']);          // 9:00-9:45 cabe antes de la cita
        $this->assertFalse($h['09:30']);         // 9:30-10:15 se cruza
        $this->assertFalse($h['10:00']);         // ocupada
        $this->assertTrue($h['10:45']);          // pegada al final de la cita: no deja hueco
        $this->assertTrue($h['11:00']);
    }

    public function test_un_servicio_largo_no_se_ofrece_si_no_cabe_antes_del_cierre(): void
    {
        $h = $this->horas([$this->tinte]);   // 120 min

        $this->assertTrue($h['16:00']);       // 16:00-18:00 justo
        $this->assertArrayNotHasKey('16:30', $h);   // terminaría 18:30, pasada la hora de cierre
    }

    public function test_la_cita_guarda_la_hora_de_fin_segun_la_duracion(): void
    {
        $this->reservar([$this->manicure], '10:00')->assertSessionHasNoErrors();

        $r = Reserva::firstOrFail();
        $this->assertSame('10:00:00', $r->hora_inicio);
        $this->assertSame('10:45:00', $r->hora_fin);
    }

    // ------------------------------------------------------------------ varios servicios

    public function test_varios_servicios_suman_duracion_y_precio(): void
    {
        $r = $this->getJson("/reservas/{$this->salon->slug}/disponibilidad-servicio?" . http_build_query([
            'fecha' => self::FECHA, 'servicio_ids' => [$this->manicure->id, $this->pedicure->id],
        ]))->assertOk();
        $this->assertSame(105, $r->json('duracion'));

        $this->reservar([$this->manicure, $this->pedicure], '10:00')->assertSessionHasNoErrors();

        $cita = Reserva::with('servicios')->firstOrFail();
        $this->assertSame('11:45:00', $cita->hora_fin);                       // 10:00 + 45 + 60
        $this->assertEquals(70000, $cita->precio);                            // 30.000 + 40.000
        $this->assertSame('Manicure + Pedicure', $cita->nombreServicios());
        $this->assertCount(2, $cita->servicios);
        $this->assertSame($this->manicure->id, $cita->servicio_id);           // el primero queda como principal
    }

    public function test_un_combo_que_no_cabe_en_la_cita_siguiente_no_se_ofrece(): void
    {
        $this->cita($this->ana, '11:00', '12:00');

        $h = $this->horas([$this->manicure, $this->pedicure], (string) $this->ana->id);   // 105 min

        $this->assertTrue($h['09:00']);    // 9:00-10:45
        $this->assertFalse($h['09:30']);   // 9:30-11:15 pisa la cita de las 11
    }

    public function test_un_servicio_inexistente_o_de_otro_negocio_se_rechaza(): void
    {
        $otro = User::create(['name' => 'Otro', 'email' => 'otro@x.com', 'password' => 'secreto123', 'slug' => 'otro', 'tipo_negocio' => 'salon', 'hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'activo' => true]);
        $ajeno = Servicio::create(['user_id' => $otro->id, 'nombre' => 'Ajeno', 'duracion_minutos' => 30, 'activo' => true]);

        $this->reservar([$this->manicure, $ajeno], '10:00')->assertSessionHasErrors('servicio_ids');
        $this->assertSame(0, Reserva::count());
    }

    // ------------------------------------------------------------------ horas pasadas y anticipación

    public function test_las_horas_que_ya_pasaron_hoy_no_se_ofrecen(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 14:10:00', 'America/Bogota'));

        $h = $this->horas([$this->manicure], 'cualquiera', '2026-11-12');

        $this->assertFalse($h['09:00']);
        $this->assertFalse($h['14:00']);     // ya empezó
        $this->assertTrue($h['14:30']);      // la próxima
    }

    public function test_no_se_puede_reservar_una_hora_que_ya_paso(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 14:10:00', 'America/Bogota'));

        $this->reservar([$this->manicure], '13:00', 'cualquiera', '2026-11-12')->assertSessionHasErrors('hora_inicio');
        $this->assertSame(0, Reserva::count());

        $this->reservar([$this->manicure], '14:30', 'cualquiera', '2026-11-12')->assertSessionHasNoErrors();
        $this->assertSame(1, Reserva::count());
    }

    public function test_no_se_puede_reservar_una_fecha_pasada(): void
    {
        $this->reservar([$this->manicure], '10:00', 'cualquiera', '2026-11-09')->assertSessionHasErrors('fecha');
    }

    public function test_hoy_es_el_dia_de_bogota_y_no_el_de_utc(): void
    {
        // 9:30 p. m. del 12 en Bogotá ya es el 13 en UTC: "hoy" sigue siendo el 12
        Carbon::setTestNow(Carbon::parse('2026-11-12 21:30:00', 'America/Bogota'));

        $this->assertSame('2026-11-12', User::ahora()->toDateString());
        $this->assertSame('21:30', $this->salon->horaMinimaReservable('2026-11-12'));
        // El negocio cierra a las 18:00: a las 21:30 no queda ninguna hora reservable hoy
        $this->assertSame([], array_filter($this->horas([$this->manicure], 'cualquiera', '2026-11-12')));
        $this->reservar([$this->manicure], '17:00', 'cualquiera', '2026-11-12')->assertSessionHasErrors('hora_inicio');
        // Y el día siguiente (13) sigue siendo reservable con normalidad
        $this->assertNotEmpty(array_filter($this->horas([$this->manicure], 'cualquiera', '2026-11-13')));
    }

    public function test_la_anticipacion_minima_oculta_las_horas_cercanas(): void
    {
        $this->salon->update(['anticipacion_minutos' => 60]);
        Carbon::setTestNow(Carbon::parse('2026-11-12 14:10:00', 'America/Bogota'));

        $h = $this->horas([$this->manicure], 'cualquiera', '2026-11-12');

        $this->assertFalse($h['15:00']);     // antes de 15:10
        $this->assertTrue($h['15:30']);
        $this->reservar([$this->manicure], '15:00', 'cualquiera', '2026-11-12')->assertSessionHasErrors('hora_inicio');
    }

    // ------------------------------------------------------------------ horario y descanso de la profesional

    public function test_la_profesional_solo_se_ofrece_dentro_de_su_horario_y_fuera_de_su_descanso(): void
    {
        $this->ana->update(['hora_inicio_trabajo' => '10:00', 'hora_fin_trabajo' => '16:00', 'descanso_inicio' => '12:00', 'descanso_fin' => '13:00']);

        $h = $this->horas([$this->pedicure], (string) $this->ana->id);   // 60 min

        $this->assertFalse($h['09:00']);     // antes de entrar
        $this->assertTrue($h['10:00']);
        $this->assertTrue($h['11:00']);      // 11:00-12:00 justo antes del descanso
        $this->assertFalse($h['11:30']);     // pisa el descanso
        $this->assertFalse($h['12:00']);
        $this->assertTrue($h['13:00']);      // vuelve del descanso
        $this->assertTrue($h['15:00']);      // 15:00-16:00 justo al salir
        $this->assertFalse($h['15:30']);     // se pasa de su hora de salida
    }

    public function test_con_cualquiera_se_asigna_la_profesional_que_si_trabaja_a_esa_hora(): void
    {
        $this->ana->update(['hora_inicio_trabajo' => '14:00', 'hora_fin_trabajo' => '18:00']);

        $this->reservar([$this->manicure], '10:00')->assertSessionHasNoErrors();

        $this->assertSame($this->beto->id, Reserva::firstOrFail()->cancha_id);   // Ana aún no llega
    }

    public function test_no_se_reserva_con_una_profesional_en_su_descanso(): void
    {
        $this->ana->update(['descanso_inicio' => '12:00', 'descanso_fin' => '13:00']);

        $this->reservar([$this->manicure], '12:15', $this->ana)->assertSessionHasErrors('hora_inicio');
        $this->assertSame(0, Reserva::count());
    }

    public function test_la_profesional_que_no_atiende_ese_dia_no_aparece(): void
    {
        $this->ana->update(['dias_disponibles' => [1, 2, 3]]);   // lunes a miércoles; la fecha es jueves

        $this->assertSame([], $this->horas([$this->manicure], (string) $this->ana->id));
        $this->reservar([$this->manicure], '10:00', $this->ana)->assertSessionHasErrors('profesional_id');
    }

    // ------------------------------------------------------------------ margen entre citas

    public function test_el_margen_se_suma_a_lo_que_ocupa_una_cita(): void
    {
        $this->cita($this->ana, '10:00', '10:45', margen: 15);   // ocupa hasta las 11:00

        $h = $this->horas([$this->manicure], (string) $this->ana->id);

        $this->assertFalse($h['10:45'] ?? false);   // la profesional aún está limpiando: ni se ofrece
        $this->assertTrue($h['11:00']);
    }

    public function test_el_margen_de_la_cita_nueva_tambien_cuenta_contra_la_siguiente(): void
    {
        $this->salon->update(['margen_minutos' => 15]);
        $this->cita($this->ana, '10:45', '11:30');

        $h = $this->horas([$this->manicure], (string) $this->ana->id);

        // Una cita de 45 min + 15 de margen que empiece a las 10:00 invadiría la de las 10:45
        $this->assertFalse($h['10:00']);
        $this->assertTrue($h['09:30']);      // 9:30-10:15 y margen hasta 10:30
    }

    public function test_el_margen_propio_de_un_servicio_manda_sobre_el_del_negocio(): void
    {
        $this->salon->update(['margen_minutos' => 10]);
        $largo = $this->servicio('Keratina', 60, 100000, ['margen_minutos' => 30]);

        $this->reservar([$largo], '10:00', $this->ana)->assertSessionHasNoErrors();

        $r = Reserva::firstOrFail();
        $this->assertSame(30, (int) $r->margen_minutos);
        $this->assertSame(10, $this->manicure->margenPara($this->salon));
    }

    // ------------------------------------------------------------------ cuadricula y huecos

    public function test_el_intervalo_de_la_agenda_es_configurable(): void
    {
        $this->salon->update(['intervalo_agenda' => 15]);

        $h = $this->horas([$this->manicure]);

        $this->assertArrayHasKey('09:15', $h);
        $this->assertArrayHasKey('09:45', $h);
    }

    // ------------------------------------------------------------------ capacidad

    public function test_nunca_hay_mas_citas_a_la_vez_que_sillas(): void
    {
        $this->salon->update(['numero_puestos' => 1]);
        $this->reservar([$this->manicure], '10:00', $this->ana)->assertSessionHasNoErrors();

        $this->reservar([$this->manicure], '10:15', $this->beto)->assertSessionHasErrors('hora_inicio');

        $h = $this->horas([$this->manicure], (string) $this->beto->id);
        $this->assertFalse($h['10:00']);
        $this->assertTrue($h['10:45']);
        $this->assertSame(1, Reserva::count());
    }

    public function test_dos_citas_se_pueden_solapar_si_hay_sillas_y_distinta_profesional(): void
    {
        $this->salon->update(['numero_puestos' => 2]);
        $this->reservar([$this->manicure], '10:00', $this->ana)->assertSessionHasNoErrors();
        $this->reservar([$this->manicure], '10:00', $this->beto)->assertSessionHasNoErrors();

        // Una tercera ya no cabe: 2 sillas, 2 profesionales ocupadas
        $this->reservar([$this->manicure], '10:00')->assertSessionHasErrors('hora_inicio');
        $this->assertSame(2, Reserva::count());
    }

    public function test_una_cita_cancelada_libera_el_horario(): void
    {
        $this->cita($this->ana, '10:00', '10:45', estado: 'cancelada');

        $this->assertTrue($this->horas([$this->manicure], (string) $this->ana->id)['10:00']);
    }

    public function test_con_cualquiera_se_reparte_el_trabajo_entre_las_profesionales(): void
    {
        $this->reservar([$this->manicure], '09:00')->assertSessionHasNoErrors();
        $this->reservar([$this->manicure], '11:00')->assertSessionHasNoErrors();

        $this->assertNotSame(Reserva::orderBy('id')->first()->cancha_id, Reserva::orderBy('id')->skip(1)->first()->cancha_id);
    }

    // ------------------------------------------------------------------ confirmación

    public function test_el_combo_solo_se_confirma_solo_si_todos_sus_servicios_lo_hacen(): void
    {
        $this->salon->update(['confirmacion_automatica' => true]);
        $manual = $this->servicio('Especial', 30, 10000, ['confirmacion_automatica' => false]);

        $this->reservar([$this->manicure], '09:00', $this->ana)->assertSessionHasNoErrors();
        $this->reservar([$this->manicure, $manual], '10:00', $this->ana)->assertSessionHasNoErrors();

        $this->assertSame('confirmada', Reserva::orderBy('id')->first()->estado);
        $this->assertSame('pendiente', Reserva::orderBy('id')->skip(1)->first()->estado);
    }
}
