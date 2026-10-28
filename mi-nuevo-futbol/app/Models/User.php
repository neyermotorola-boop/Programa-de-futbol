<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'slug',
        'hora_apertura',
        'hora_cierre',
        'max_horas_por_reserva',
        'es_superadmin',
        'activo',
        'tipo_negocio',
        'negocio_id',
        'intervalo_agenda',
        'margen_minutos',
        'anticipacion_minutos',
        'logo_path',
        'razon_social',
        'eslogan',
        'telefono_contacto',
        'direccion',
        'color_primario',
        'color_acento',
        'dias_atencion',
        'numero_puestos',
        'tipo_puesto',
        'confirmacion_automatica',
        'email_confirmacion_activo',
        'email_asunto',
        'email_mensaje',
        'email_pie',
    ];

    public const DIAS_SEMANA = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    public const TIPOS_PUESTO = ['silla' => 'Sillas', 'consultorio' => 'Consultorios', 'cabina' => 'Cabinas', 'estacion' => 'Estaciones'];

    /** Cada cuántos minutos se ofrecen horas de inicio en la agenda de un salón. */
    public const INTERVALOS_AGENDA = [10 => '10 minutos', 15 => '15 minutos', 20 => '20 minutos', 30 => '30 minutos', 60 => '1 hora'];

    /** Anticipación mínima con la que un cliente puede reservar (minutos => texto). */
    public const ANTICIPACIONES = [0 => 'Hasta el último minuto', 30 => '30 minutos', 60 => '1 hora', 120 => '2 horas', 180 => '3 horas', 360 => '6 horas', 720 => '12 horas', 1440 => '1 día'];

    /** El momento actual en la zona horaria en que atienden los negocios. */
    public static function ahora(): Carbon
    {
        return now(config('canchas.zona', 'America/Bogota'));
    }

    /**
     * Primera hora ('HH:MM') que se puede reservar en una fecha, según la hora actual y la anticipación
     * del negocio. Null si no hay restricción (fecha futura); '24:00' si todo el día ya pasó.
     */
    public function horaMinimaReservable(string $fecha): ?string
    {
        $hoy = self::ahora()->toDateString();

        if ($fecha < $hoy) {
            return '24:00';
        }
        if ($fecha > $hoy) {
            return null;
        }

        $limite = self::ahora()->addMinutes((int) $this->anticipacion_minutos);
        // Un instante con segundos ya no alcanza para el minuto en curso
        if ($limite->second > 0) {
            $limite->addMinute()->second(0);
        }

        return $limite->toDateString() > $fecha ? '24:00' : $limite->format('H:i');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'es_superadmin' => 'boolean',
            'activo' => 'boolean',
            'dias_atencion' => 'array',
            'confirmacion_automatica' => 'boolean',
            'email_confirmacion_activo' => 'boolean',
            'email_confirmacion_activo' => 'boolean',
            'email_confirmacion_activo' => 'boolean',
        ];
    }

    /**
     * Días en que el negocio atiende (1 = lunes ... 7 = domingo). Si nunca
     * se configuró, se asume que abre todos los días.
     */
    public function diasAtencion(): array
    {
        return $this->dias_atencion ?: array_keys(self::DIAS_SEMANA);
    }

    public const EMAIL_ASUNTO = 'Tu cita en {negocio} está confirmada';
    public const EMAIL_MENSAJE = 'Hola {cliente}, tu reserva quedó confirmada. Estos son los detalles:';
    public const EMAIL_PIE = 'Si necesitas cambiar o cancelar tu cita, comunícate con nosotros.';

    /**
     * Textos de la plantilla del correo de confirmación. Lo que el negocio no
     * personalizó usa el texto estándar.
     */
    public function plantillaEmail(): array
    {
        return [
            'asunto' => $this->email_asunto ?: self::EMAIL_ASUNTO,
            'mensaje' => $this->email_mensaje ?: self::EMAIL_MENSAJE,
            'pie' => $this->email_pie ?: self::EMAIL_PIE,
        ];
    }

    /** Reemplaza {negocio}, {cliente}, {servicio}, {fecha} y {hora} en un texto de la plantilla. */
    public static function rellenarPlantilla(string $texto, array $datos): string
    {
        return strtr($texto, collect($datos)->mapWithKeys(fn ($v, $k) => ['{' . $k . '}' => $v])->all());
    }

    /** Colores de marca y el texto (negro/blanco) que se lee sobre cada uno, para el correo. */
    public function paletaCorreo(): array
    {
        $primario = $this->colorValido($this->color_primario, $this->coloresPorDefecto()[0]);
        $acento = $this->colorValido($this->color_acento, $this->coloresPorDefecto()[1]);

        return [
            'primario' => $primario,
            'acento' => $acento,
            'texto_primario' => self::textoSobre($primario),
            'texto_acento' => self::textoSobre($acento),
        ];
    }

    /**
     * ¿Las reservas de este servicio se confirman solas? Si el servicio tiene
     * su propia regla manda esa; si no, la configuración general del negocio.
     */
    public function confirmaSola(?Servicio $servicio = null): bool
    {
        return (bool) ($servicio?->confirmacion_automatica ?? $this->confirmacion_automatica);
    }

    public function atiendeEl(string $fecha): bool
    {
        return in_array(Carbon::parse($fecha)->dayOfWeekIso, $this->diasAtencion());
    }

    public function urlLogo(): ?string
    {
        return $this->logo_path ? asset($this->logo_path) : null;
    }

    /**
     * Variables CSS con los colores de marca del negocio (página pública y panel).
     * Un color elegido por el cliente puede ser ilegible como texto (un verde
     * oscuro sobre fondo oscuro, un amarillo sobre fondo blanco), así que aquí se
     * calculan, para cada tema, versiones del color con contraste suficiente
     * (WCAG AA, 4.5:1) y el color de texto correcto para ponerlo como relleno.
     */
    public function variablesMarca(): string
    {
        $primario = $this->colorValido($this->color_primario, $this->coloresPorDefecto()[0]);
        $acento = $this->colorValido($this->color_acento, $this->coloresPorDefecto()[1]);

        // Fondos más claros de cada tema: el peor caso para el contraste del texto
        $fondoOscuro = self::mezclar($primario, '#1f2324', 0.24);
        $fondoClaro = self::mezclar($primario, '#ffffff', 0.14);

        return implode(' ', [
            "--turf: {$primario};",
            "--floodlight: {$acento};",
            '--texto-acento: ' . self::textoSobre(self::mezclar($acento, '#ffffff', 0.9)) . ';',
            '--texto-primario: ' . self::textoSobre(self::mezclar($primario, '#ffffff', 0.75)) . ';',
            '--acento-txt-d: ' . self::legible($acento, $fondoOscuro) . ';',
            '--acento-txt-l: ' . self::legible($acento, $fondoClaro) . ';',
            '--primario-txt-d: ' . self::legible($primario, $fondoOscuro) . ';',
            '--primario-txt-l: ' . self::legible($primario, $fondoClaro) . ';',
        ]);
    }

    private function colorValido(?string $color, string $porDefecto): string
    {
        return $color && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtoupper($color) : $porDefecto;
    }

    private static function rgb(string $hex): array
    {
        return array_map('hexdec', str_split(ltrim($hex, '#'), 2));
    }

    /** Mezcla dos colores: $parteA (0-1) del primero y el resto del segundo. */
    private static function mezclar(string $a, string $b, float $parteA): string
    {
        $ra = self::rgb($a);
        $rb = self::rgb($b);
        $mezcla = array_map(fn ($x, $y) => (int) round($x * $parteA + $y * (1 - $parteA)), $ra, $rb);

        return sprintf('#%02X%02X%02X', ...$mezcla);
    }

    private static function luminancia(string $hex): float
    {
        [$r, $g, $b] = array_map(function ($c) {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function contraste(string $a, string $b): float
    {
        [$claro, $oscuro] = [max(self::luminancia($a), self::luminancia($b)), min(self::luminancia($a), self::luminancia($b))];

        return ($claro + 0.05) / ($oscuro + 0.05);
    }

    /** Negro o blanco, el que mejor se lea sobre este color de relleno. */
    private static function textoSobre(string $fondo): string
    {
        return self::contraste($fondo, '#1a1204') >= self::contraste($fondo, '#ffffff') ? '#1a1204' : '#ffffff';
    }

    /**
     * Aclara u oscurece $color (según el fondo sea oscuro o claro) hasta que
     * se lea con contraste mínimo 4.5:1; si ya se lee, no lo toca.
     */
    private static function legible(string $color, string $fondo): string
    {
        $haciaBlanco = self::luminancia($fondo) < 0.4;
        $destino = $haciaBlanco ? '#ffffff' : '#000000';

        for ($parte = 1.0; $parte >= 0; $parte -= 0.04) {
            $candidato = self::mezclar($color, $destino, $parte);
            if (self::contraste($candidato, $fondo) >= 4.5) {
                return $candidato;
            }
        }

        return $destino;
    }

    /**
     * Las canchas (o, en un salón, las profesionales/estaciones) de este negocio.
     */
    public function canchas(): HasMany
    {
        return $this->hasMany(Cancha::class);
    }

    /**
     * Los servicios de este negocio (solo aplica a negocios tipo 'salon').
     */
    /** Deportes y superficies que este negocio de canchas define y edita. */
    public function categoriasCancha(): HasMany
    {
        return $this->hasMany(CategoriaCancha::class)->orderBy('orden')->orderBy('id');
    }

    public function parqueaderoIngresos(): HasMany
    {
        return $this->hasMany(ParqueaderoIngreso::class);
    }

    /** Vehículos con mensualidad de este parqueadero. */
    public function mensualidades(): HasMany
    {
        return $this->hasMany(Mensualidad::class)->orderBy('vence');
    }

    /** Tipos de vehículo (y su tarifa) que atiende este parqueadero. */
    public function tiposVehiculo(): HasMany
    {
        return $this->hasMany(TipoVehiculo::class)->orderBy('orden')->orderBy('id');
    }

    /** El dueño del parqueadero al que pertenece este operador (null si es el dueño). */
    public function dueno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'negocio_id');
    }

    /** Empleados que el dueño creó para registrar ingresos y salidas. */
    public function operadores(): HasMany
    {
        return $this->hasMany(User::class, 'negocio_id')->orderBy('name');
    }

    /** Operador: solo registra ingresos y salidas del parqueadero de su dueño. */
    public function esOperador(): bool
    {
        return $this->negocio_id !== null;
    }

    /** Colores de marca [principal, acento] mientras el negocio no elige los suyos. */
    public function coloresPorDefecto(): array
    {
        return $this->esParqueadero() ? ['#1F4E8C', '#FFC247'] : ['#2F6B3C', '#F2A93B'];
    }

    public function esParqueadero(): bool
    {
        return $this->tipo_negocio === 'parqueadero';
    }

    /** Nombre de la ruta del panel principal según el tipo de negocio. */
    public function rutaPanel(): string
    {
        return match (true) {
            (bool) $this->es_superadmin => 'superadmin.index',
            $this->esParqueadero() => 'parqueadero.index',
            default => 'reservas.admin',
        };
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    /**
     * Las reservas que le pertenecen a este negocio/usuario.
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }
}