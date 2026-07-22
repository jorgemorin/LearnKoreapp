<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Compound — palabra compuesta del Diccionario Global.
 * Representa una palabra aglutinada coreana completa, compuesta de una
 * o más entidades morfológicas (raíces y partículas) ordenadas.
 *
 * @property int    $id
 * @property string $full_text
 * @property string $translation
 * @property string $status  'pending_review' | 'verified'
 */
class Compound extends Model
{
    use HasFactory;

    const STATUS_PENDING  = 'pending_review';
    const STATUS_VERIFIED = 'verified';

    protected $fillable = [
        'full_text',
        'translation',
        'status',
    ];

    // ---------------------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------------------

    /**
     * Las entidades que componen este compuesto, ordenadas por posición.
     * Se usa la tabla pivot compound_entity con el campo extra position_order.
     */
    public function entities()
    {
        return $this->belongsToMany(Entity::class, 'compound_entity')
                    ->withPivot('position_order')
                    ->orderByPivot('position_order');
    }

    /** Las etiquetas semánticas asignadas a este compuesto (polimórfico). */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    // ---------------------------------------------------------------------------
    // Query Scopes
    // ---------------------------------------------------------------------------

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /** Progreso de usuarios para este compound (morfológico). */
    public function userProgress()
    {
        return $this->morphMany(UserProgress::class, 'item');
    }
}
