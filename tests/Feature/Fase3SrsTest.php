<?php

namespace Tests\Feature;

use App\Models\Compound;
use App\Models\StudyLog;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\UserSrsSettings;
use App\Services\SrsService;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests de Fase 3 — Motor SRS (actualizado para Anki completo)
 *
 * Mantiene retrocompatibilidad con la API REST legacy (is_correct),
 * y verifica el nuevo comportamiento del motor Anki.
 */
class Fase3SrsTest extends TestCase
{
    use RefreshDatabase;

    private function defaultSettings(int $userId): UserSrsSettings
    {
        return UserSrsSettings::firstOrCreate(['user_id' => $userId]);
    }

    // =========================================================================
    // 3.A — Algoritmo Anki: 4 ratings básicos desde estado New
    // =========================================================================

    public function test_sm2_acierto_incrementa_repeticiones_y_ajusta_ease_factor(): void
    {
        $service  = app(SrsService::class);
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'     => $user->id,
            'ease_factor'   => 2.5,
            'interval_days' => 1,
            'repetitions'   => 1,
            'card_state'    => UserProgress::STATE_YOUNG, // En Review
        ]);
        $settings = $this->defaultSettings($user->id);

        $result = $service->calculate($progress, 'good', $settings);

        $this->assertGreaterThanOrEqual(1, $result['interval_days']);
        $this->assertGreaterThanOrEqual(1, $result['repetitions']);
        $this->assertGreaterThanOrEqual(SrsService::MIN_EASE_FACTOR, $result['ease_factor']);
        $this->assertTrue($result['is_correct']);
    }

    public function test_sm2_segunda_rep_correcta_da_intervalo_mayor(): void
    {
        $service  = app(SrsService::class);
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'       => $user->id,
            'ease_factor'   => 2.5,
            'interval_days' => 6,
            'repetitions'   => 2,
            'card_state'    => UserProgress::STATE_YOUNG,
        ]);
        $settings = $this->defaultSettings($user->id);

        $result = $service->calculate($progress, 'good', $settings);

        // good: interval = max(hard+1, round(6 * 2.5)) = max(9, 15) = 15
        $this->assertEquals(15, $result['interval_days']);
    }

    public function test_sm2_fallo_resetea_a_relearning(): void
    {
        $service  = app(SrsService::class);
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'       => $user->id,
            'ease_factor'   => 2.5,
            'interval_days' => 15,
            'repetitions'   => 3,
            'card_state'    => UserProgress::STATE_MATURE,
            'lapses'        => 0,
        ]);
        $settings = $this->defaultSettings($user->id);

        $result = $service->calculate($progress, 'again', $settings);

        $this->assertEquals(UserProgress::STATE_RELEARNING, $result['card_state']);
        $this->assertEquals(1, $result['lapses']);
        $this->assertFalse($result['is_correct']);
        $this->assertLessThan(2.5, $result['ease_factor']); // baja por fallo
    }

    public function test_sm2_ease_factor_no_baja_de_1_3(): void
    {
        $service  = app(SrsService::class);
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'     => $user->id,
            'ease_factor'   => 1.3,
            'interval_days' => 1,
            'repetitions'   => 0,
            'card_state'    => UserProgress::STATE_YOUNG,
        ]);
        $settings = $this->defaultSettings($user->id);

        $result = $service->calculate($progress, 'again', $settings);

        $this->assertGreaterThanOrEqual(SrsService::MIN_EASE_FACTOR, $result['ease_factor']);
    }

    // =========================================================================
    // 3.B — Ciclo de estados: New → Learning → Young → Mature
    // =========================================================================

    public function test_carta_new_con_good_entra_en_learning(): void
    {
        $service  = app(SrsService::class);
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'    => $user->id,
            'card_state' => UserProgress::STATE_NEW,
        ]);
        $settings = $this->defaultSettings($user->id);

        $result = $service->calculate($progress, 'good', $settings);

        $this->assertEquals(UserProgress::STATE_LEARNING, $result['card_state']);
    }

    public function test_carta_new_con_easy_pasa_directamente_a_young(): void
    {
        $service  = app(SrsService::class);
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'    => $user->id,
            'card_state' => UserProgress::STATE_NEW,
        ]);
        $settings = $this->defaultSettings($user->id);

        $result = $service->calculate($progress, 'easy', $settings);

        $this->assertContains($result['card_state'], [UserProgress::STATE_YOUNG, UserProgress::STATE_MATURE]);
        $this->assertTrue($result['is_correct']);
    }

    public function test_carta_young_con_intervalo_mayor_21_se_vuelve_mature(): void
    {
        $service  = app(SrsService::class);
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'       => $user->id,
            'ease_factor'   => 2.5,
            'interval_days' => 15,
            'repetitions'   => 4,
            'card_state'    => UserProgress::STATE_YOUNG,
        ]);
        $settings = $this->defaultSettings($user->id);

        // good: round(15 * 2.5) = 38 días → Mature
        $result = $service->calculate($progress, 'good', $settings);

        $this->assertGreaterThanOrEqual(UserProgress::MATURE_THRESHOLD_DAYS, $result['interval_days']);
        $this->assertEquals(UserProgress::STATE_MATURE, $result['card_state']);
    }

    public function test_carta_mature_con_again_pasa_a_relearning(): void
    {
        $service  = app(SrsService::class);
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'       => $user->id,
            'ease_factor'   => 2.5,
            'interval_days' => 30,
            'repetitions'   => 5,
            'card_state'    => UserProgress::STATE_MATURE,
        ]);
        $settings = $this->defaultSettings($user->id);

        $result = $service->calculate($progress, 'again', $settings);

        $this->assertEquals(UserProgress::STATE_RELEARNING, $result['card_state']);
        $this->assertEquals(1, $result['lapses']);
    }

    // =========================================================================
    // 3.C — Intervalos: hard < good < easy
    // =========================================================================

    public function test_intervalos_siguen_orden_correcto(): void
    {
        $service  = app(SrsService::class);
        $user     = User::factory()->create();
        $settings = $this->defaultSettings($user->id);
        $base     = [
            'user_id'       => $user->id,
            'ease_factor'   => 2.5,
            'interval_days' => 10,
            'repetitions'   => 3,
            'card_state'    => UserProgress::STATE_YOUNG,
        ];

        $hard = $service->calculate(UserProgress::factory()->create($base), 'hard', $settings);
        $good = $service->calculate(UserProgress::factory()->create($base), 'good', $settings);
        $easy = $service->calculate(UserProgress::factory()->create($base), 'easy', $settings);

        // easy > good >= hard (siempre en este orden)
        $this->assertGreaterThan($good['interval_days'], $easy['interval_days']);
        $this->assertGreaterThanOrEqual($hard['interval_days'], $good['interval_days'] - 1);
    }

    // =========================================================================
    // 3.D — StudyLog: solo INSERTs, inmutable
    // =========================================================================

    public function test_study_log_no_tiene_updated_at(): void
    {
        $log = StudyLog::factory()->create();
        $this->assertNull(StudyLog::UPDATED_AT);
        $this->assertDatabaseHas('study_logs', ['id' => $log->id]);
    }

    public function test_record_answer_inserta_study_log_con_rating(): void
    {
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'          => $user->id,
            'next_review_date' => now()->toDateString(),
            'card_state'       => UserProgress::STATE_YOUNG,
        ]);
        $this->defaultSettings($user->id);

        $this->assertDatabaseCount('study_logs', 0);

        app(SrsService::class)->recordAnswer(
            progressId:  $progress->id,
            userId:      $user->id,
            rating:      'good',
            timeTakenMs: 1500,
        );

        $this->assertDatabaseCount('study_logs', 1);
        $this->assertDatabaseHas('study_logs', [
            'user_id'    => $user->id,
            'is_correct' => true,
            'rating'     => 'good',
        ]);
    }

    // =========================================================================
    // 3.E — getNextBatch: excluye suspendidas, respeta límite
    // =========================================================================

    public function test_get_next_batch_devuelve_solo_tarjetas_vencidas(): void
    {
        $user = User::factory()->create();

        UserProgress::factory()->count(2)->create([
            'user_id'          => $user->id,
            'next_review_date' => Carbon::yesterday()->toDateString(),
            'card_state'       => UserProgress::STATE_YOUNG,
        ]);
        UserProgress::factory()->create([
            'user_id'          => $user->id,
            'next_review_date' => Carbon::tomorrow()->toDateString(),
            'card_state'       => UserProgress::STATE_YOUNG,
        ]);

        $batch = app(SrsService::class)->getNextBatch($user->id);
        $this->assertCount(2, $batch);
    }

    public function test_get_next_batch_excluye_tarjetas_suspendidas(): void
    {
        $user = User::factory()->create();

        UserProgress::factory()->create([
            'user_id'          => $user->id,
            'next_review_date' => Carbon::yesterday()->toDateString(),
            'card_state'       => UserProgress::STATE_SUSPENDED,
        ]);
        UserProgress::factory()->create([
            'user_id'          => $user->id,
            'next_review_date' => Carbon::yesterday()->toDateString(),
            'card_state'       => UserProgress::STATE_YOUNG,
        ]);

        $batch = app(SrsService::class)->getNextBatch($user->id);
        $this->assertCount(1, $batch);
    }

    public function test_get_next_batch_incluye_tarjetas_de_hoy(): void
    {
        $user = User::factory()->create();
        UserProgress::factory()->create([
            'user_id'          => $user->id,
            'next_review_date' => Carbon::today()->toDateString(),
            'card_state'       => UserProgress::STATE_YOUNG,
        ]);

        $batch = app(SrsService::class)->getNextBatch($user->id);
        $this->assertCount(1, $batch);
    }

    public function test_get_next_batch_respeta_limite(): void
    {
        $user = User::factory()->create();
        UserProgress::factory()->count(10)->create([
            'user_id'          => $user->id,
            'next_review_date' => Carbon::yesterday()->toDateString(),
            'card_state'       => UserProgress::STATE_YOUNG,
        ]);

        $batch = app(SrsService::class)->getNextBatch($user->id, limit: 5);
        $this->assertCount(5, $batch);
    }

    // =========================================================================
    // 3.F — API REST: retrocompatibilidad con is_correct
    // =========================================================================

    public function test_api_next_batch_retorna_tarjetas_vencidas(): void
    {
        $user = User::factory()->create();
        UserProgress::factory()->count(3)->create([
            'user_id'          => $user->id,
            'next_review_date' => Carbon::yesterday()->toDateString(),
            'card_state'       => UserProgress::STATE_YOUNG,
        ]);

        $this->actingAs($user)
            ->getJson('/api/review/next-batch')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['progress_id', 'next_review_date', 'ease_factor', 'card_state', 'item']],
                'meta' => ['count', 'date', 'user_id'],
            ]);
    }

    public function test_api_next_batch_sin_autenticar_retorna_401(): void
    {
        $this->getJson('/api/review/next-batch')->assertStatus(401);
    }

    public function test_api_answer_con_rating_actualiza_progress(): void
    {
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'          => $user->id,
            'card_state'       => UserProgress::STATE_YOUNG,
            'interval_days'    => 6,
            'repetitions'      => 2,
            'next_review_date' => Carbon::today()->toDateString(),
        ]);
        UserSrsSettings::firstOrCreate(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/review/{$progress->id}/answer", [
                'rating'        => 'good',
                'time_taken_ms' => 1500,
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'progress' => ['id', 'card_state', 'interval_days', 'ease_factor', 'repetitions', 'next_review_date'],
            ]);
    }

    public function test_api_answer_con_is_correct_legacy_funciona(): void
    {
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'          => $user->id,
            'card_state'       => UserProgress::STATE_YOUNG,
            'next_review_date' => Carbon::today()->toDateString(),
        ]);
        UserSrsSettings::firstOrCreate(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/review/{$progress->id}/answer", [
                'is_correct'    => true,
                'time_taken_ms' => 1500,
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    public function test_api_answer_sin_rating_ni_is_correct_retorna_422(): void
    {
        $user     = User::factory()->create();
        $progress = UserProgress::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/review/{$progress->id}/answer", [
                'time_taken_ms' => 1000,
            ])
            ->assertStatus(422);
    }

    public function test_api_answer_sin_autenticar_retorna_401(): void
    {
        $this->postJson('/api/review/1/answer', [
            'rating'        => 'good',
            'time_taken_ms' => 1000,
        ])->assertStatus(401);
    }

    public function test_record_answer_lanza_excepcion_si_progress_no_pertenece_al_usuario(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $user1    = User::factory()->create();
        $user2    = User::factory()->create();
        $progress = UserProgress::factory()->create(['user_id' => $user2->id]);
        UserSrsSettings::firstOrCreate(['user_id' => $user1->id]);

        app(SrsService::class)->recordAnswer(
            progressId:  $progress->id,
            userId:      $user1->id,
            rating:      'good',
            timeTakenMs: 1000,
        );
    }
}
