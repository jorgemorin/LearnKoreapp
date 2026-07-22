<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo StudyLog — registro histórico INMUTABLE de intentos de repaso.
 *
 * IMPORTANTE: Este modelo NUNCA debe actualizarse ni borrarse.
 * Solo se crea (INSERT). La analítica educativa depende de la integridad
 * de este registro histórico.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $item_id
 * @property string      $item_type    'entity' | 'compound'
 * @property bool        $is_correct   retrocompat. con el sistema anterior
 * @property string|null $rating       again | hard | good | easy (Anki)
 * @property int         $time_taken_ms
 */
class StudyLog extends Model
{
    use HasFactory;

    // Sin updated_at: este registro es inmutable
    const UPDATED_AT = null;

    // ── Constantes de calificación Anki ─────────────────────────────────────
    public const RATING_AGAIN = 'again';
    public const RATING_HARD  = 'hard';
    public const RATING_GOOD  = 'good';
    public const RATING_EASY  = 'easy';

    /** Ratings que equivalen a "correcto" para la analítica existente. */
    public const CORRECT_RATINGS = [self::RATING_HARD, self::RATING_GOOD, self::RATING_EASY];

    protected $fillable = [
        'user_id',
        'item_id',
        'item_type',
        'is_correct',
        'rating',
        'time_taken_ms',
    ];

    protected function casts(): array
    {
        return [
            'is_correct'    => 'boolean',
            'time_taken_ms' => 'integer',
            'created_at'    => 'datetime',
        ];
    }

    /**
     * Devuelve true si el rating equivale a una respuesta correcta.
     * Mantiene compatibilidad con el campo is_correct de la analítica.
     */
    public static function isCorrectFromRating(string $rating): bool
    {
        return in_array($rating, self::CORRECT_RATINGS, true);
    }

    // ---------------------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación polimórfica con el elemento estudiado.
     * Permite saber si fue una Entity o un Compound.
     */
    public function item()
    {
        return $this->morphTo();
    }
}
