<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría que un negocio de canchas define y edita: un deporte (fútbol, tenis, pádel...)
 * o una superficie (grama sintética, arena, cemento...).
 */
class CategoriaCancha extends Model
{
    protected $table = 'categorias_cancha';

    protected $fillable = ['user_id', 'tipo', 'nombre', 'base', 'color', 'orden'];

    /** Cómo se dibuja un deporte en el visor 3D. "Otro" = cancha rectangular sencilla. */
    public const BASES_DEPORTE = [
        'futbol' => 'Fútbol (arcos y áreas)',
        'tenis' => 'Tenis (red y cajas de saque)',
        'padel' => 'Pádel (paredes de vidrio y red)',
        'voleibol' => 'Voleibol (red y línea de ataque)',
        'generico' => 'Otro deporte (cancha sencilla)',
    ];

    /** Qué textura lleva una superficie en el visor 3D. */
    public const ESTILOS = [
        'cesped' => 'Césped (franjas)',
        'arena' => 'Arena (granulada)',
        'dura' => 'Superficie dura (lisa)',
        'arcilla' => 'Arcilla (polvo de ladrillo)',
    ];

    /** Formatos de juego disponibles según el diseño del deporte. */
    public const FORMATOS = [
        'futbol' => ['futbol5' => 'Fútbol 5', 'futbol7' => 'Fútbol 7', 'futbol11' => 'Fútbol 11'],
        'tenis' => ['individual' => 'Individual', 'dobles' => 'Dobles'],
        'padel' => ['dobles' => 'Dobles'],
        'voleibol' => ['6x6' => '6 contra 6', 'playa' => 'Playa (2 contra 2)'],
        'generico' => [],
    ];

    public const DEPORTES_PREDETERMINADOS = ['Fútbol' => 'futbol', 'Tenis' => 'tenis', 'Pádel' => 'padel', 'Voleibol' => 'voleibol'];

    public const SUPERFICIES_PREDETERMINADAS = [
        'Grama sintética' => ['cesped', '#2F8F3F'],
        'Grama natural' => ['cesped', '#3F8A35'],
        'Arena' => ['arena', '#D8B67A'],
        'Cemento' => ['dura', '#2F6AA3'],
        'Arcilla' => ['arcilla', '#C4673A'],
    ];

    /** Íconos del deporte según su diseño. */
    public const ICONOS = ['futbol' => 'pitch', 'tenis' => 'racket', 'padel' => 'padel', 'voleibol' => 'volleyball', 'generico' => 'pitch'];

    /** Valores de una cancha nueva: primer deporte y primera superficie del negocio. */
    public static function valoresIniciales(User $negocio): array
    {
        self::crearPredeterminadas($negocio);
        $deporte = self::where('user_id', $negocio->id)->deporte()->orderBy('orden')->first();
        $superficie = self::where('user_id', $negocio->id)->superficie()->orderBy('orden')->first();

        return [
            'deporte_id' => $deporte?->id,
            'superficie_id' => $superficie?->id,
            'tamano' => array_key_first(self::FORMATOS[$deporte?->base ?? 'generico'] ?? []),
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Canchas que usan esta categoría como deporte. */
    public function canchasDeporte(): HasMany
    {
        return $this->hasMany(Cancha::class, 'deporte_id');
    }

    /** Canchas que usan esta categoría como superficie. */
    public function canchasSuperficie(): HasMany
    {
        return $this->hasMany(Cancha::class, 'superficie_id');
    }

    public function enUso(): int
    {
        return $this->tipo === 'deporte' ? $this->canchasDeporte()->count() : $this->canchasSuperficie()->count();
    }

    public function icono(): string
    {
        return self::ICONOS[$this->base] ?? 'pitch';
    }

    public function scopeDeporte($query)
    {
        return $query->where('tipo', 'deporte');
    }

    public function scopeSuperficie($query)
    {
        return $query->where('tipo', 'superficie');
    }

    /**
     * Crea los deportes y superficies de partida de un negocio de canchas (si no los tiene).
     * Después el negocio los edita a su gusto: renombra, agrega o quita.
     */
    public static function crearPredeterminadas(User $negocio): void
    {
        if (self::where('user_id', $negocio->id)->exists()) {
            return;
        }

        $orden = 0;
        foreach (self::DEPORTES_PREDETERMINADOS as $nombre => $base) {
            self::create(['user_id' => $negocio->id, 'tipo' => 'deporte', 'nombre' => $nombre, 'base' => $base, 'orden' => $orden++]);
        }
        foreach (self::SUPERFICIES_PREDETERMINADAS as $nombre => [$base, $color]) {
            self::create(['user_id' => $negocio->id, 'tipo' => 'superficie', 'nombre' => $nombre, 'base' => $base, 'color' => $color, 'orden' => $orden++]);
        }
    }
}
