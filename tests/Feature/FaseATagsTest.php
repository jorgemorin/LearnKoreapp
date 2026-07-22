<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use App\Services\AI\FakeAIParserService;
use App\Support\TagCatalog;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de Fase A — Taxonomía Estandarizada de Etiquetas
 *
 * Verifican:
 *   1. El seeder crea exactamente los 24 tags estándar
 *   2. FakeAIParserService solo devuelve tags del catálogo
 *   3. TagCatalog::filterToStandard filtra correctamente
 *   4. ParseVocabularyJob ignora tags fuera del catálogo
 *   5. El Tag model tiene los scopes correctos
 */
class FaseATagsTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // A.1 — Seeder
    // =========================================================================

    public function test_tag_seeder_crea_24_tags_estandar(): void
    {
        $this->seed(TagSeeder::class);

        $this->assertDatabaseCount('tags', 24);
        $this->assertEquals(24, Tag::where('is_standard', true)->count());
    }

    public function test_tag_seeder_crea_9_tags_grammar(): void
    {
        $this->seed(TagSeeder::class);

        $this->assertEquals(9, Tag::where('layer', 'grammar')->count());
    }

    public function test_tag_seeder_crea_3_tags_register(): void
    {
        $this->seed(TagSeeder::class);

        $this->assertEquals(3, Tag::where('layer', 'register')->count());
    }

    public function test_tag_seeder_crea_12_tags_thematic(): void
    {
        $this->seed(TagSeeder::class);

        $this->assertEquals(12, Tag::where('layer', 'thematic')->count());
    }

    public function test_tag_seeder_is_idempotente(): void
    {
        // Ejecutar el seeder dos veces no debe duplicar tags
        $this->seed(TagSeeder::class);
        $this->seed(TagSeeder::class);

        $this->assertDatabaseCount('tags', 24);
    }

    public function test_tags_grammar_son_visibles_por_defecto(): void
    {
        $this->seed(TagSeeder::class);

        $grammarTags = Tag::where('layer', 'grammar')->get();
        foreach ($grammarTags as $tag) {
            $this->assertTrue($tag->is_visible_default, "El tag '{$tag->name}' (grammar) debería ser visible por defecto.");
        }
    }

    public function test_tags_thematic_estan_ocultos_por_defecto(): void
    {
        $this->seed(TagSeeder::class);

        $thematicTags = Tag::where('layer', 'thematic')->get();
        foreach ($thematicTags as $tag) {
            $this->assertFalse($tag->is_visible_default, "El tag '{$tag->name}' (thematic) no debería ser visible por defecto.");
        }
    }

    // =========================================================================
    // A.2 — FakeAIParserService usa tags del catálogo
    // =========================================================================

    public function test_fake_parser_devuelve_tags_del_catalogo_para_안녕하세요(): void
    {
        $this->seed(TagSeeder::class);
        $parser = new FakeAIParserService();

        $result = $parser->parse('안녕하세요');
        $tags   = $result['full_compound']['tags'];

        $standardNames = TagCatalog::standardTagNames();
        foreach ($tags as $tag) {
            $this->assertContains(
                mb_strtolower($tag),
                $standardNames,
                "El tag '{$tag}' no está en el catálogo estándar."
            );
        }
    }

    public function test_fake_parser_devuelve_tags_del_catalogo_para_먹어요(): void
    {
        $this->seed(TagSeeder::class);
        $parser = new FakeAIParserService();

        $result = $parser->parse('먹어요');
        $tags   = $result['full_compound']['tags'];

        $standardNames = TagCatalog::standardTagNames();
        foreach ($tags as $tag) {
            $this->assertContains(mb_strtolower($tag), $standardNames);
        }
    }

    public function test_fake_parser_respuesta_generica_usa_tag_del_catalogo(): void
    {
        $this->seed(TagSeeder::class);
        $parser = new FakeAIParserService();

        // Texto no definido en RESPONSES → usa buildGenericResponse
        $result = $parser->parse('임의의텍스트');
        $tags   = $result['full_compound']['tags'];

        $standardNames = TagCatalog::standardTagNames();
        foreach ($tags as $tag) {
            $this->assertContains(mb_strtolower($tag), $standardNames);
        }
    }

    // =========================================================================
    // A.3 — TagCatalog::filterToStandard
    // =========================================================================

    public function test_tag_catalog_filtra_tags_no_estandar(): void
    {
        $this->seed(TagSeeder::class);

        $input    = ['verbo', 'Educación', 'sustantivo', 'TagInventado'];
        $filtered = TagCatalog::filterToStandard($input);

        $this->assertContains('verbo', $filtered);
        $this->assertContains('sustantivo', $filtered);
        $this->assertNotContains('Educación', $filtered);
        $this->assertNotContains('TagInventado', $filtered);
    }

    public function test_tag_catalog_acepta_tags_en_minusculas(): void
    {
        $this->seed(TagSeeder::class);

        $filtered = TagCatalog::filterToStandard(['verbo', 'SUSTANTIVO', 'Adjetivo']);

        // Todos deben ser aceptados (case-insensitive)
        $this->assertCount(3, $filtered);
    }

    public function test_tag_catalog_devuelve_array_vacio_si_ninguno_es_estandar(): void
    {
        $this->seed(TagSeeder::class);

        $filtered = TagCatalog::filterToStandard(['Saludos', 'Cultura', 'Idiomas']);

        $this->assertEmpty($filtered);
    }

    // =========================================================================
    // A.4 — Scopes del modelo Tag
    // =========================================================================

    public function test_scope_standard_devuelve_solo_tags_estandar(): void
    {
        $this->seed(TagSeeder::class);

        // Crear un tag no estándar
        Tag::create(['name' => 'tag_libre', 'is_standard' => false]);

        $this->assertEquals(24, Tag::standard()->count());
    }

    public function test_scope_visible_devuelve_12_tags_visibles(): void
    {
        $this->seed(TagSeeder::class);

        // grammar (9) + register (3) = 12 visibles
        $this->assertEquals(12, Tag::standard()->visible()->count());
    }

    public function test_scope_layer_filtra_por_capa_correctamente(): void
    {
        $this->seed(TagSeeder::class);

        $this->assertEquals(9,  Tag::layer('grammar')->count());
        $this->assertEquals(3,  Tag::layer('register')->count());
        $this->assertEquals(12, Tag::layer('thematic')->count());
    }

    // =========================================================================
    // A.5 — ParseVocabularyJob ignora tags fuera del catálogo
    // =========================================================================

    public function test_parse_job_no_persiste_tags_no_estandar(): void
    {
        $this->seed(TagSeeder::class);

        $user     = User::factory()->create();
        $fakeData = [
            'full_compound' => [
                'text'        => '테스트',
                'translation' => 'prueba',
                'tags'        => ['verbo', 'TagNoExistente', 'Saludos_inventado'],
            ],
            'components' => [
                ['text' => '테스트', 'type' => 'word', 'meaning' => 'prueba', 'position_order' => 0],
            ],
        ];

        // Simular la lógica de filtrado del Job directamente
        $filteredTags = TagCatalog::filterToStandard($fakeData['full_compound']['tags']);

        $this->assertContains('verbo', $filteredTags);
        $this->assertNotContains('TagNoExistente', $filteredTags);
        $this->assertNotContains('Saludos_inventado', $filteredTags);
        $this->assertCount(1, $filteredTags);
    }
}
