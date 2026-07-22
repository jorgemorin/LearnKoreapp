<?php

namespace App\Support;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Helper estático: catálogo centralizado de etiquetas estándar.
 *
 * Proporciona métodos convenientes para obtener los IDs, nombres y
 * listas de tags del catálogo oficial sin duplicar queries en el código.
 *
 * Las consultas se cachean 24 horas (el catálogo rara vez cambia).
 */
class TagCatalog
{
    private const CACHE_TTL = 86400; // 24 horas

    /**
     * Devuelve los IDs de todos los tags estándar.
     * Útil para validar que un tag pertenece al catálogo.
     */
    public static function standardTagIds(): array
    {
        return Cache::remember('tag_catalog.standard_ids', self::CACHE_TTL, function () {
            return Tag::standard()->pluck('id')->toArray();
        });
    }

    /**
     * Devuelve los nombres de todos los tags estándar (minúsculas).
     * Útil para generar el prompt de la IA.
     */
    public static function standardTagNames(): array
    {
        return Cache::remember('tag_catalog.standard_names', self::CACHE_TTL, function () {
            return Tag::standard()->pluck('name')->toArray();
        });
    }

    /**
     * Devuelve los tags agrupados por capa para la UI.
     * Formato: ['grammar' => [Tag,...], 'register' => [...], 'thematic' => [...]]
     */
    public static function groupedByLayer(): Collection
    {
        return Cache::remember('tag_catalog.grouped', self::CACHE_TTL, function () {
            return Tag::standard()->get()->groupBy('layer');
        });
    }

    /**
     * Devuelve solo los tags visibles por defecto (Capa 1 + 2).
     * Para los filtros principales de la UI de Colección.
     */
    public static function visibleTags(): Collection
    {
        return Cache::remember('tag_catalog.visible', self::CACHE_TTL, function () {
            return Tag::standard()->visible()->orderBy('layer')->orderBy('name')->get();
        });
    }

    /**
     * Filtra un array de nombres de tags para devolver solo los del catálogo.
     * Los tags no reconocidos se ignoran silenciosamente.
     *
     * @param  array $names Array de strings con nombres de tags
     * @return array        Solo los nombres que existen en el catálogo
     */
    public static function filterToStandard(array $names): array
    {
        $standardNames = array_map('mb_strtolower', self::standardTagNames());

        return array_values(array_filter(
            $names,
            fn ($name) => in_array(mb_strtolower(trim($name)), $standardNames, true)
        ));
    }

    /**
     * Invalida la caché del catálogo (llamar tras añadir nuevos tags estándar).
     */
    public static function clearCache(): void
    {
        Cache::forget('tag_catalog.standard_ids');
        Cache::forget('tag_catalog.standard_names');
        Cache::forget('tag_catalog.grouped');
        Cache::forget('tag_catalog.visible');
    }

    /**
     * Genera la lista de tags en formato legible para incluir en el prompt de la IA.
     * Formato: "verbo, sustantivo, adjetivo, ..."
     */
    public static function promptList(): string
    {
        return implode(', ', self::standardTagNames());
    }
}
