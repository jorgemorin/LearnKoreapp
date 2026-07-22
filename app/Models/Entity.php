<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Entity — elemento del Diccionario Global.
 * Representa raíces verbales, partículas y palabras sueltas del coreano.
 * La unicidad se garantiza con el par (text, type).
 *
 * @property int    $id
 * @property string $text
 * @property string $type    'root' | 'particle' | 'word'
 * @property string $meaning
 * @property string $status  'pending_review' | 'verified'
 */
class Entity extends Model
{
    use HasFactory;

    // Valores permitidos de tipo y estado (reflejan los CHECK del DDL)
    const TYPE_ROOT     = 'root';
    const TYPE_PARTICLE = 'particle';
    const TYPE_WORD     = 'word';

    const STATUS_PENDING  = 'pending_review';
    const STATUS_VERIFIED = 'verified';

    protected $fillable = [
        'text',
        'type',
        'meaning',
        'status',
    ];

    // ---------------------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------------------

    /** Los compuestos en los que participa esta entidad. */
    public function compounds()
    {
        return $this->belongsToMany(Compound::class, 'compound_entity')
                    ->withPivot('position_order')
                    ->orderByPivot('position_order');
    }

    /** Las etiquetas semánticas asignadas a esta entidad (polimórfico). */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    // ---------------------------------------------------------------------------
    // Query Scopes
    // ---------------------------------------------------------------------------

    /** Filtra entidades con un status determinado. */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /** Filtra entidades con un tipo morfológico determinado. */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /** Solo entidades verificadas. */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /** Solo entidades pendientes de revisión. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
