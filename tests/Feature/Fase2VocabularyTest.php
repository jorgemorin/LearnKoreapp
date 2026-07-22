<?php

namespace Tests\Feature;

use App\Contracts\AIParserServiceInterface;
use App\Jobs\ParseVocabularyJob;
use App\Models\Compound;
use App\Models\Entity;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\AI\FakeAIParserService;
use App\Services\VocabularyIngestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests de Fase 2 — Núcleo de Vocabulario
 *
 * Verifican:
 *   - Cache Hit: misma palabra no duplica en BD
 *   - Cache Miss: Job se despacha correctamente
 *   - ParseVocabularyJob: persiste compound + entities + tags + user_progress
 *   - Validación esquema IA: respuestas malformadas son rechazadas
 *   - API REST: POST /api/vocabulary devuelve 200/202 correctamente
 *   - API REST: GET /api/me/collection devuelve la colección del usuario
 */
class Fase2VocabularyTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 2.A — FakeAIParserService
    // =========================================================================

    public function test_fake_parser_devuelve_estructura_correcta(): void
    {
        $parser = new FakeAIParserService();
        $result = $parser->parse('학교에서');

        $this->assertArrayHasKey('full_compound', $result);
        $this->assertArrayHasKey('components', $result);
        $this->assertEquals('학교에서', $result['full_compound']['text']);
        $this->assertEquals('en la escuela', $result['full_compound']['translation']);
        $this->assertNotEmpty($result['components']);
    }

    public function test_fake_parser_lanza_excepcion_con_texto_vacio(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new FakeAIParserService())->parse('');
    }

    public function test_fake_parser_genera_respuesta_generica_para_texto_desconocido(): void
    {
        $result = (new FakeAIParserService())->parse('테스트');

        $this->assertEquals('테스트', $result['full_compound']['text']);
        $this->assertStringContainsString('테스트', $result['full_compound']['translation']);
        $this->assertCount(1, $result['components']);
        $this->assertEquals('word', $result['components'][0]['type']);
    }

    // =========================================================================
    // 2.B — VocabularyIngestService: Cache Hit
    // =========================================================================

    public function test_cache_hit_no_despacha_job_si_compound_existe(): void
    {
        Queue::fake();

        $user     = User::factory()->create();
        $compound = Compound::factory()->create(['full_text' => '안녕하세요']);

        $service = app(VocabularyIngestService::class);
        $result  = $service->ingest('안녕하세요', $user->id);

        $this->assertEquals('hit', $result['status']);
        Queue::assertNotPushed(ParseVocabularyJob::class);
    }

    public function test_cache_hit_crea_user_progress_si_no_existe(): void
    {
        Queue::fake();

        $user     = User::factory()->create();
        $compound = Compound::factory()->create(['full_text' => '안녕하세요']);

        $this->assertDatabaseMissing('user_progress', [
            'user_id'   => $user->id,
            'item_id'   => $compound->id,
            'item_type' => 'compound',
        ]);

        app(VocabularyIngestService::class)->ingest('안녕하세요', $user->id);

        $this->assertDatabaseHas('user_progress', [
            'user_id'   => $user->id,
            'item_id'   => $compound->id,
            'item_type' => 'compound',
        ]);
    }

    public function test_cache_hit_no_duplica_user_progress_en_segunda_llamada(): void
    {
        Queue::fake();

        $user     = User::factory()->create();
        $compound = Compound::factory()->create(['full_text' => '안녕하세요']);

        $service = app(VocabularyIngestService::class);
        $service->ingest('안녕하세요', $user->id);
        $service->ingest('안녕하세요', $user->id);

        $this->assertDatabaseCount('user_progress', 1);
    }

    // =========================================================================
    // 2.C — VocabularyIngestService: Cache Miss
    // =========================================================================

    public function test_cache_miss_despacha_parse_vocabulary_job(): void
    {
        Queue::fake();

        $user    = User::factory()->create();
        $service = app(VocabularyIngestService::class);
        $result  = $service->ingest('감사합니다', $user->id);

        $this->assertEquals('pending', $result['status']);
        Queue::assertPushed(ParseVocabularyJob::class, function ($job) use ($user) {
            return $job->text === '감사합니다' && $job->userId === $user->id;
        });
    }

    // =========================================================================
    // 2.D — ParseVocabularyJob: persiste correctamente
    // =========================================================================

    public function test_job_crea_compound_entities_y_tags(): void
    {
        // La Fase A requiere que el catálogo esté poblado para filtrar los tags de la IA
        $this->seed(\Database\Seeders\TagSeeder::class);
        $user = User::factory()->create();

        // Bindear el FakeParser para el test
        $this->app->bind(AIParserServiceInterface::class, FakeAIParserService::class);

        $job = new ParseVocabularyJob('학교에서', $user->id);
        $job->handle(app(AIParserServiceInterface::class));

        // Compound creado
        $this->assertDatabaseHas('compounds', ['full_text' => '학교에서']);
        $compound = Compound::where('full_text', '학교에서')->first();

        // Entities creadas
        $this->assertDatabaseHas('entities', ['text' => '학교', 'type' => 'root']);
        $this->assertDatabaseHas('entities', ['text' => '에서', 'type' => 'particle']);

        // Relaciones compound_entity
        $this->assertEquals(2, $compound->entities()->count());

        // Tags (ahora usa el catálogo estándar: sustantivo, estudios, lugares)
        $this->assertDatabaseHas('tags', ['name' => 'sustantivo']);
        $this->assertDatabaseHas('tags', ['name' => 'estudios']);
        $this->assertDatabaseHas('tags', ['name' => 'lugares']);

        // UserProgress inicial
        $this->assertDatabaseHas('user_progress', [
            'user_id'   => $user->id,
            'item_id'   => $compound->id,
            'item_type' => 'compound',
        ]);
    }

    public function test_job_idempotente_no_duplica_en_segunda_ejecucion(): void
    {
        $user = User::factory()->create();
        $this->app->bind(AIParserServiceInterface::class, FakeAIParserService::class);
        $parser = app(AIParserServiceInterface::class);

        $job = new ParseVocabularyJob('학교에서', $user->id);
        $job->handle($parser);
        $job->handle($parser); // segunda vez

        $this->assertDatabaseCount('compounds', 1);
        $this->assertDatabaseCount('user_progress', 1);
    }

    public function test_job_reusa_entity_existente(): void
    {
        $user = User::factory()->create();
        $this->app->bind(AIParserServiceInterface::class, FakeAIParserService::class);

        // Crear entity previa con el mismo text+type
        Entity::factory()->create(['text' => '학교', 'type' => 'root', 'meaning' => 'escuela preexistente']);

        $job = new ParseVocabularyJob('학교에서', $user->id);
        $job->handle(app(AIParserServiceInterface::class));

        // Solo debe existir 1 entity para '학교' (no duplicada)
        $this->assertDatabaseCount('entities', 2); // 학교 + 에서
    }

    // =========================================================================
    // 2.E — API REST: POST /api/vocabulary
    // =========================================================================

    public function test_api_post_vocabulary_miss_retorna_202(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/vocabulary', ['text' => '감사합니다'])
            ->assertStatus(202)
            ->assertJsonPath('status', 'pending');

        Queue::assertPushed(ParseVocabularyJob::class);
    }

    public function test_api_post_vocabulary_hit_retorna_200(): void
    {
        Queue::fake();
        $user     = User::factory()->create();
        Compound::factory()->create(['full_text' => '안녕하세요', 'translation' => 'hola']);

        $this->actingAs($user)
            ->postJson('/api/vocabulary', ['text' => '안녕하세요'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'hit');
    }

    public function test_api_post_vocabulary_sin_autenticar_retorna_401(): void
    {
        $this->postJson('/api/vocabulary', ['text' => '안녕하세요'])
            ->assertStatus(401);
    }

    public function test_api_post_vocabulary_texto_vacio_retorna_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/vocabulary', ['text' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    // =========================================================================
    // 2.F — API REST: GET /api/me/collection
    // =========================================================================

    public function test_api_get_collection_retorna_progreso_del_usuario(): void
    {
        $user     = User::factory()->create();
        $compound = Compound::factory()->create();
        UserProgress::factory()->create([
            'user_id'   => $user->id,
            'item_id'   => $compound->id,
            'item_type' => 'compound',
        ]);

        $this->actingAs($user)
            ->getJson('/api/me/collection')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['progress_id', 'next_review_date', 'ease_factor', 'compound']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);
    }

    public function test_api_get_collection_sin_autenticar_retorna_401(): void
    {
        $this->getJson('/api/me/collection')->assertStatus(401);
    }
}
