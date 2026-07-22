<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Tag — etiqueta semántica del catálogo estándar.
 *
 * Taxonomía en 3 capas:
 *   - grammar:   categorías gramaticales (verbo, sustantivo, partícula, etc.)
 *   - register:  nivel de formalidad (formal, informal, honorífico)
 *   - thematic:  contexto situacional (cafetería, estudios, comida, etc.)
 *
 * Los tags con is_standard = true pertenecen al catálogo oficial.
 * Los tags con is_visible_default = false solo aparecen en "Filtros avanzados".
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $layer              grammar | register | thematic
 * @property bool        $is_standard
 * @property bool        $is_visible_default
 * @property string|null $description
 */
class Tag extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'layer', 'is_standard', 'is_visible_default', 'description'];

    protected $casts = [
        'is_standard'        => 'boolean',
        'is_visible_default' => 'boolean',
    ];

    // ── Scopes ──────────────────────────────────────────────────────────────

    /** Solo etiquetas del catálogo oficial. */
    public function scopeStandard($query)
    {
        return $query->where('is_standard', true);
    }

    /** Solo etiquetas visibles por defecto (Capa 1 y 2). */
    public function scopeVisible($query)
    {
        return $query->where('is_visible_default', true);
    }

    /** Solo etiquetas de una capa concreta. */
    public function scopeLayer($query, string $layer)
    {
        return $query->where('layer', $layer);
    }

    // ── Relaciones polimórficas ──────────────────────────────────────────────

    public function entities()
    {
        return $this->morphedByMany(Entity::class, 'taggable');
    }

    public function compounds()
    {
        return $this->morphedByMany(Compound::class, 'taggable');
    }
}
