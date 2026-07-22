<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * TagSeeder — puebla el catálogo oficial de etiquetas estándar.
 *
 * Taxonomía en 3 capas:
 *   - grammar   (9 tags): categorías gramaticales, siempre visibles como filtros principales
 *   - register  (3 tags): nivel de formalidad, siempre visibles
 *   - thematic  (12 tags): contexto situacional, ocultos por defecto (filtros avanzados)
 *
 * Idempotente: usa updateOrCreate para no duplicar si ya existe el tag.
 */
class TagSeeder extends Seeder
{
    private const TAGS = [
        // ── Capa 1: Gramática (visible por defecto) ─────────────────────────
        ['name' => 'verbo',       'layer' => 'grammar',   'is_visible_default' => true,  'description' => 'Verbos en cualquier forma y conjugación'],
        ['name' => 'sustantivo',  'layer' => 'grammar',   'is_visible_default' => true,  'description' => 'Sustantivos comunes y propios'],
        ['name' => 'adjetivo',    'layer' => 'grammar',   'is_visible_default' => true,  'description' => 'Adjetivos descriptivos'],
        ['name' => 'adverbio',    'layer' => 'grammar',   'is_visible_default' => true,  'description' => 'Modificadores de verbo o adjetivo'],
        ['name' => 'partícula',   'layer' => 'grammar',   'is_visible_default' => true,  'description' => 'Partículas gramaticales coreanas (은/는, 이/가, 을/를, etc.)'],
        ['name' => 'expresión',   'layer' => 'grammar',   'is_visible_default' => true,  'description' => 'Expresiones idiomáticas y frases hechas'],
        ['name' => 'conjunción',  'layer' => 'grammar',   'is_visible_default' => true,  'description' => 'Conectores y conjunciones oracionales'],
        ['name' => 'contador',    'layer' => 'grammar',   'is_visible_default' => true,  'description' => 'Palabras clasificadoras / contadores numéricos'],
        ['name' => 'pronombre',   'layer' => 'grammar',   'is_visible_default' => true,  'description' => 'Pronombres personales y demostrativos'],

        // ── Capa 2: Registro / Formalidad (visible por defecto) ─────────────
        ['name' => 'formal',      'layer' => 'register',  'is_visible_default' => true,  'description' => 'Registro formal o estándar (합쇼체, 해요체)'],
        ['name' => 'informal',    'layer' => 'register',  'is_visible_default' => true,  'description' => 'Registro informal o coloquial (해체, 반말)'],
        ['name' => 'honorífico',  'layer' => 'register',  'is_visible_default' => true,  'description' => 'Formas de cortesía especiales (존댓말 avanzado)'],

        // ── Capa 3: Temática / Situacional (oculta, búsqueda avanzada) ──────
        ['name' => 'cafetería',   'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Vocabulario de cafetería, pedidos y bebidas'],
        ['name' => 'estudios',    'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Vocabulario académico y universitario'],
        ['name' => 'trabajo',     'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Entorno laboral y profesional'],
        ['name' => 'familia',     'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Relaciones familiares y parentesco'],
        ['name' => 'transporte',  'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Metro, taxi, direcciones y transporte público'],
        ['name' => 'salud',       'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Contexto médico y farmacéutico'],
        ['name' => 'comida',      'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Vocabulario culinario y gastronomía coreana'],
        ['name' => 'tecnología',  'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Términos tecnológicos y digitales'],
        ['name' => 'objetos',     'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Objetos cotidianos del hogar y la calle'],
        ['name' => 'emociones',   'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Vocabulario emocional y estados de ánimo'],
        ['name' => 'tiempo',      'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Expresiones temporales y meteorología'],
        ['name' => 'lugares',     'layer' => 'thematic',  'is_visible_default' => false, 'description' => 'Nombres de lugares y localizaciones'],
    ];

    public function run(): void
    {
        foreach (self::TAGS as $tagData) {
            Tag::updateOrCreate(
                ['name' => $tagData['name']],
                [
                    'layer'              => $tagData['layer'],
                    'is_standard'        => true,
                    'is_visible_default' => $tagData['is_visible_default'],
                    'description'        => $tagData['description'],
                ]
            );
        }

        $this->command->info('✅ TagSeeder: ' . count(self::TAGS) . ' etiquetas estándar creadas/actualizadas.');
    }
}
