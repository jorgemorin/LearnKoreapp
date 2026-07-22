<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Modelo UserProgress — estado SRS de un elemento para un usuario.
 *
 * Implementa el algoritmo Anki completo con los siguientes estados:
 *   new        → nunca estudiada
 *   learning   → pasando por los pasos iniciales (minutos)
 *   young      → graduada, intervalo < 21 días
 *   mature     → graduada, intervalo ≥ 21 días
 *   relearning → falló en Review, vuelve a los pasos de reaprendizaje
 *   suspended  → el usuario la ha suspendido manualmente
 *
 * @property int    $id
 * @property int    $user_id
 * @property int    $item_id
 * @property string $item_type          'entity' | 'compound'
 * @property Carbon $next_review_date
 * @property float  $ease_factor
 * @property int    $interval_days
 * @property int    $repetitions
 * @property string $card_state         new|learning|young|mature|relearning|suspended
 * @property int    $lapses             fallos en fase Review
 * @property int    $learning_step_index paso actual en secuencia de learning
 */
class UserProgress extends Model
{
    use HasFactory;

    // ── Estados de madurez ──────────────────────────────────────────────────
    public const STATE_NEW        = 'new';
    public const STATE_LEARNING   = 'learning';
    public const STATE_YOUNG      = 'young';
    public const STATE_MATURE     = 'mature';
    public const STATE_RELEARNING = 'relearning';
    public const STATE_SUSPENDED  = 'suspended';

    /** Intervalo mínimo para considerar una carta "Madura" (días). */
    public const MATURE_THRESHOLD_DAYS = 21;

    protected $fillable = [
        'user_id',
        'item_id',
        'item_type',
        'next_review_date',
        'ease_factor',
        'interval_days',
        'repetitions',
        'card_state',
        'lapses',
        'learning_step_index',
    ];

    protected function casts(): array
    {
        return [
            'next_review_date'    => 'date',
            'ease_factor'         => 'float',
            'interval_days'       => 'integer',
            'repetitions'         => 'integer',
            'lapses'              => 'integer',
            'learning_step_index' => 'integer',
        ];
    }

    // ── Helpers de estado ────────────────────────────────────────────────────

    public function isNew(): bool        { return $this->card_state === self::STATE_NEW; }
    public function isLearning(): bool   { return $this->card_state === self::STATE_LEARNING; }
    public function isMature(): bool     { return $this->card_state === self::STATE_MATURE; }
    public function isYoung(): bool      { return $this->card_state === self::STATE_YOUNG; }
    public function isRelearning(): bool { return $this->card_state === self::STATE_RELEARNING; }
    public function isSuspended(): bool  { return $this->card_state === self::STATE_SUSPENDED; }

    /** Etiqueta legible para mostrar en la UI. */
    public function stateLabel(): string
    {
        return match($this->card_state) {
            self::STATE_NEW        => 'Nueva',
            self::STATE_LEARNING   => 'Aprendiendo',
            self::STATE_YOUNG      => 'Joven',
            self::STATE_MATURE     => 'Madura',
            self::STATE_RELEARNING => 'Reaprendiendo',
            self::STATE_SUSPENDED  => 'Suspendida',
            default                => 'Desconocido',
        };
    }

    // ---------------------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación polimórfica con el elemento de vocabulario.
     * Devuelve una instancia de Entity o Compound según item_type.
     */
    public function item()
    {
        return $this->morphTo();
    }

    // ---------------------------------------------------------------------------
    // Query Scopes
    // ---------------------------------------------------------------------------

    /** Tarjetas pendientes de repaso hoy o anteriores (excluye suspendidas). */
    public function scopeDueToday(Builder $query): Builder
    {
        return $query
            ->where('next_review_date', '<=', Carbon::today())
            ->where('card_state', '!=', self::STATE_SUSPENDED);
    }

    /** Filtra por usuario. */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /** Excluye tarjetas suspendidas. */
    public function scopeNotSuspended(Builder $query): Builder
    {
        return $query->where('card_state', '!=', self::STATE_SUSPENDED);
    }

    /** Filtra por estado. */
    public function scopeInState(Builder $query, string $state): Builder
    {
        return $query->where('card_state', $state);
    }
}
