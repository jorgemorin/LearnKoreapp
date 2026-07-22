<?php

namespace Tests\Feature;

use App\Models\Compound;
use App\Models\Entity;
use App\Models\StudyLog;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\StatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de Fase 5 — Motor de Analítica
 *
 * Verifican:
 *   - getPersonalStats() devuelve estructura correcta con datos reales
 *   - Caché: segunda llamada usa caché (no re-ejecuta queries)
 *   - Invalidación de caché al registrar respuesta
 *   - API REST: GET /api/stats/personal, GET /api/stats/cross
 *   - API REST (admin): GET /api/admin/stats/global
 *   - Vistas SQL devuelven datos válidos (SQLite, query equivalente)
 */
class Fase5StatsTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 5.A — getPersonalStats: estructura y datos
    // =========================================================================

    public function test_get_personal_stats_devuelve_estructura_correcta(): void
    {
        $user = User::factory()->create();

        $stats = app(StatsService::class)->getPersonalStats($user->id);

        $this->assertArrayHasKey('global_accuracy', $stats);
        $this->assertArrayHasKey('total_studied', $stats);
        $this->assertArrayHasKey('due_today', $stats);
        $this->assertArrayHasKey('total_in_collection', $stats);
        $this->assertArrayHasKey('accuracy_by_tag', $stats);
        $this->assertArrayHasKey('accuracy_by_type', $stats);
        $this->assertArrayHasKey('recent_sessions', $stats);
    }

    public function test_get_personal_stats_sin_datos_devuelve_ceros(): void
    {
        $user  = User::factory()->create();
        $stats = app(StatsService::class)->getPersonalStats($user->id);

        $this->assertNull($stats['global_accuracy']);
        $this->assertEquals(0, $stats['total_studied']);
        $this->assertEquals(0, $stats['due_today']);
        $this->assertEquals(0, $stats['total_in_collection']);
        $this->assertEmpty($stats['accuracy_by_tag']);
    }

    public function test_get_personal_stats_con_study_logs_calcula_precision(): void
    {
        $user = User::factory()->create();
        $compound = Compound::factory()->create();

        // 3 aciertos, 1 fallo → 75%
        StudyLog::factory()->count(3)->create([
            'user_id'    => $user->id,
            'item_id'    => $compound->id,
            'item_type'  => 'compound',
            'is_correct' => true,
        ]);
        StudyLog::factory()->create([
            'user_id'    => $user->id,
            'item_id'    => $compound->id,
            'item_type'  => 'compound',
            'is_correct' => false,
        ]);

        // Limpiar caché para forzar recálculo
        Cache::forget("stats.personal.{$user->id}");

        $stats = app(StatsService::class)->getPersonalStats($user->id);

        $this->assertEquals(4, $stats['total_studied']);
        $this->assertEquals(75.0, (float) $stats['global_accuracy']);
    }

    public function test_get_personal_stats_total_in_collection(): void
    {
        $user = User::factory()->create();
        UserProgress::factory()->count(5)->create(['user_id' => $user->id]);

        Cache::forget("stats.personal.{$user->id}");

        $stats = app(StatsService::class)->getPersonalStats($user->id);

        $this->assertEquals(5, $stats['total_in_collection']);
    }

    public function test_get_personal_stats_due_today(): void
    {
        $user = User::factory()->create();

        // 2 vencidas
        UserProgress::factory()->count(2)->create([
            'user_id'          => $user->id,
            'next_review_date' => now()->toDateString(),
        ]);
        // 1 futura
        UserProgress::factory()->create([
            'user_id'          => $user->id,
            'next_review_date' => now()->addDays(3)->toDateString(),
        ]);

        Cache::forget("stats.personal.{$user->id}");

        $stats = app(StatsService::class)->getPersonalStats($user->id);

        $this->assertEquals(2, $stats['due_today']);
    }

    // =========================================================================
    // 5.B — Caché
    // =========================================================================

    public function test_segunda_llamada_usa_cache(): void
    {
        $user = User::factory()->create();

        Cache::forget("stats.personal.{$user->id}");

        // Primera llamada: ejecuta queries y guarda en caché
        $stats1 = app(StatsService::class)->getPersonalStats($user->id);

        // Añadir datos después (no deberían aparecer por estar en caché)
        StudyLog::factory()->create(['user_id' => $user->id, 'is_correct' => true]);

        // Segunda llamada: debe devolver los mismos datos (caché hit)
        $stats2 = app(StatsService::class)->getPersonalStats($user->id);

        $this->assertEquals($stats1['total_studied'], $stats2['total_studied']);
    }

    public function test_invalidate_user_cache_limpia_la_cache(): void
    {
        $user = User::factory()->create();

        Cache::forget("stats.personal.{$user->id}");

        // Cachear
        app(StatsService::class)->getPersonalStats($user->id);
        $this->assertTrue(Cache::has("stats.personal.{$user->id}"));

        // Invalidar
        StatsService::invalidateUserCache($user->id);
        $this->assertFalse(Cache::has("stats.personal.{$user->id}"));
    }

    // =========================================================================
    // 5.C — API REST: /api/stats/personal
    // =========================================================================

    public function test_api_stats_personal_devuelve_200_con_estructura(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/stats/personal')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'global_accuracy',
                    'total_studied',
                    'due_today',
                    'total_in_collection',
                    'accuracy_by_tag',
                    'accuracy_by_type',
                    'recent_sessions',
                ],
                'meta' => ['user_id', 'generated_at', 'cache_ttl'],
            ]);
    }

    public function test_api_stats_personal_sin_autenticar_retorna_401(): void
    {
        $this->getJson('/api/stats/personal')->assertStatus(401);
    }

    public function test_api_stats_cross_devuelve_200(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/stats/cross')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'filters']);
    }

    public function test_api_stats_cross_sin_autenticar_retorna_401(): void
    {
        $this->getJson('/api/stats/cross')->assertStatus(401);
    }

    // =========================================================================
    // 5.D — API REST admin: /api/admin/stats/global
    // =========================================================================

    public function test_api_admin_stats_global_devuelve_200_para_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson('/api/admin/stats/global')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_users',
                    'total_compounds',
                    'pending_review',
                    'total_studied',
                    'global_accuracy',
                    'top_tags',
                ],
            ]);
    }

    public function test_api_admin_stats_global_retorna_403_para_usuario_normal(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->getJson('/api/admin/stats/global')
            ->assertStatus(403);
    }

    // =========================================================================
    // 5.E — Global stats calcula correctamente
    // =========================================================================

    public function test_global_stats_cuenta_usuarios_y_compounds(): void
    {
        User::factory()->count(3)->create(['role' => 'user']);
        Compound::factory()->count(5)->create(['status' => 'pending_review']);
        Compound::factory()->count(2)->create(['status' => 'verified']);

        Cache::forget('stats.global');

        $stats = app(StatsService::class)->getGlobalStats();

        $this->assertGreaterThanOrEqual(3, $stats['total_users']);
        $this->assertGreaterThanOrEqual(7, $stats['total_compounds']);
        $this->assertGreaterThanOrEqual(5, $stats['pending_review']);
    }
}
