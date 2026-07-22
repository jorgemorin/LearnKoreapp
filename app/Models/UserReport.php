<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Modelo UserReport — ticket de soporte/reporte enviado por un usuario.
 *
 * Estados:
 *   pending   → recibido, sin revisar
 *   reviewing → asignado a un admin
 *   resolved  → resuelto con nota del admin
 *   dismissed → descartado
 *
 * Categorías: error_traduccion, error_hangul, contenido_inapropiado, bug, sugerencia, otro
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $category
 * @property string      $description
 * @property int|null    $related_item_id
 * @property string|null $related_item_type
 * @property string      $status
 * @property string|null $admin_notes
 * @property int|null    $reviewed_by
 */
class UserReport extends Model
{
    use HasFactory;

    protected $table = 'user_reports';

    // ── Status constants ─────────────────────────────────────────────────────
    public const STATUS_PENDING   = 'pending';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_RESOLVED  = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    // ── Category constants ───────────────────────────────────────────────────
    public const CATEGORY_TRANSLATION  = 'error_traduccion';
    public const CATEGORY_HANGUL       = 'error_hangul';
    public const CATEGORY_INAPPROPRIATE = 'contenido_inapropiado';
    public const CATEGORY_BUG          = 'bug';
    public const CATEGORY_SUGGESTION   = 'sugerencia';
    public const CATEGORY_OTHER        = 'otro';

    public static array $categories = [
        self::CATEGORY_TRANSLATION   => 'Error en la traducción',
        self::CATEGORY_HANGUL        => 'Error en el hangul',
        self::CATEGORY_INAPPROPRIATE => 'Contenido inapropiado',
        self::CATEGORY_BUG           => 'Bug / error técnico',
        self::CATEGORY_SUGGESTION    => 'Sugerencia',
        self::CATEGORY_OTHER         => 'Otro',
    ];

    protected $fillable = [
        'user_id', 'category', 'description',
        'related_item_id', 'related_item_type',
        'status', 'admin_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function relatedItem()
    {
        return $this->morphTo('related_item');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending(Builder $q): Builder   { return $q->where('status', self::STATUS_PENDING); }
    public function scopeReviewing(Builder $q): Builder { return $q->where('status', self::STATUS_REVIEWING); }
    public function scopeResolved(Builder $q): Builder  { return $q->where('status', self::STATUS_RESOLVED); }
    public function scopeDismissed(Builder $q): Builder { return $q->where('status', self::STATUS_DISMISSED); }
    public function scopeOpen(Builder $q): Builder      { return $q->whereIn('status', [self::STATUS_PENDING, self::STATUS_REVIEWING]); }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function categoryLabel(): string
    {
        return self::$categories[$this->category] ?? ucfirst($this->category);
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING   => 'Pendiente',
            self::STATUS_REVIEWING => 'En revisión',
            self::STATUS_RESOLVED  => 'Resuelto',
            self::STATUS_DISMISSED => 'Descartado',
            default                => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING   => '#fbbf24',
            self::STATUS_REVIEWING => '#818cf8',
            self::STATUS_RESOLVED  => '#34d399',
            self::STATUS_DISMISSED => '#6b7280',
            default                => '#9ca3af',
        };
    }
}
