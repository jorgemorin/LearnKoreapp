<?php

namespace Tests\Feature;

use App\Models\Compound;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\UserSrsSettings;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests de Fase C — Colección Interactiva
 *
 * Verifica:
 *   - API: edición de traducción
 *   - API: suspender / reactivar
 *   - API: ajuste de intervalo
 *   - API: eliminar de colección (solo user_progress, no compound)
 *   - API: acciones en lote
 *   - Livewire MyCollection: búsqueda, filtros, acciones
 */
class FaseCColeccionTest extends TestCase
{
    use RefreshDatabase;

    private function makeProgress(User $user, array $attrs = []): UserProgress
    {
        return UserProgress::factory()->create(array_merge([
            'user_id'    => $user->id,
            'card_state' => UserProgress::STATE_YOUNG,
            'next_review_date' => Carbon::today()->toDateString(),
        ], $attrs));
    }

    private function settings(User $user): UserSrsSettings
    {
        return UserSrsSettings::firstOrCreate(['user_id' => $user->id]);
    }

    // =========================================================================
    // API: edición de traducción
    // =========================================================================

    public function test_api_translate_actualiza_traduccion(): void
    {
        $user     = User::factory()->create();
        $progress = $this->makeProgress($user);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/collection/{$progress->id}/translate", [
                'translation' => 'nueva traducción',
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('translation', 'nueva traducción');

        $this->assertEquals('nueva traducción', $progress->item->fresh()->translation);
    }

    public function test_api_translate_requiere_autenticacion(): void
    {
        $this->putJson('/api/collection/1/translate', ['translation' => 'x'])
            ->assertStatus(401);
    }

    public function test_api_translate_no_puede_editar_progress_de_otro_usuario(): void
    {
        $user1    = User::factory()->create();
        $user2    = User::factory()->create();
        $progress = $this->makeProgress($user2);

        $this->actingAs($user1, 'sanctum')
            ->putJson("/api/collection/{$progress->id}/translate", ['translation' => 'hack'])
            ->assertStatus(404);
    }

    // =========================================================================
    // API: suspender / reactivar
    // =========================================================================

    public function test_api_suspend_suspende_tarjeta(): void
    {
        $user     = User::factory()->create();
        $progress = $this->makeProgress($user, ['card_state' => UserProgress::STATE_YOUNG]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/collection/{$progress->id}/suspend")
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('suspended', true);

        $this->assertEquals(UserProgress::STATE_SUSPENDED, $progress->fresh()->card_state);
    }

    public function test_api_suspend_reactiva_tarjeta_suspendida(): void
    {
        $user     = User::factory()->create();
        $progress = $this->makeProgress($user, ['card_state' => UserProgress::STATE_SUSPENDED]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/collection/{$progress->id}/suspend")
            ->assertStatus(200)
            ->assertJsonPath('suspended', false);

        $this->assertEquals(UserProgress::STATE_NEW, $progress->fresh()->card_state);
    }

    // =========================================================================
    // API: ajuste de intervalo
    // =========================================================================

    public function test_api_interval_ajusta_dias_y_estado(): void
    {
        $user     = User::factory()->create();
        $this->settings($user);
        $progress = $this->makeProgress($user);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/collection/{$progress->id}/interval", [
                'interval_days' => 30,
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        $fresh = $progress->fresh();
        $this->assertEquals(30, $fresh->interval_days);
        $this->assertEquals(UserProgress::STATE_MATURE, $fresh->card_state);
    }

    public function test_api_interval_reset_envía_a_learning(): void
    {
        $user     = User::factory()->create();
        $this->settings($user);
        $progress = $this->makeProgress($user, ['card_state' => UserProgress::STATE_MATURE]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/collection/{$progress->id}/interval", [
                'interval_days' => 0,
                'reset'         => true,
            ])
            ->assertStatus(200);

        $fresh = $progress->fresh();
        $this->assertEquals(UserProgress::STATE_LEARNING, $fresh->card_state);
        $this->assertEquals(0, $fresh->interval_days);
        $this->assertEquals(2.5, $fresh->ease_factor);
    }

    // =========================================================================
    // API: eliminar de colección
    // =========================================================================

    public function test_api_delete_elimina_user_progress_no_el_compound(): void
    {
        $user      = User::factory()->create();
        $progress  = $this->makeProgress($user);
        $compoundId = $progress->item_id;

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/collection/{$progress->id}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        // user_progress eliminado
        $this->assertDatabaseMissing('user_progress', ['id' => $progress->id]);

        // compound intacto
        $this->assertDatabaseHas('compounds', ['id' => $compoundId]);
    }

    public function test_api_delete_no_puede_eliminar_progress_de_otro_usuario(): void
    {
        $user1    = User::factory()->create();
        $user2    = User::factory()->create();
        $progress = $this->makeProgress($user2);

        $this->actingAs($user1, 'sanctum')
            ->deleteJson("/api/collection/{$progress->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('user_progress', ['id' => $progress->id]);
    }

    // =========================================================================
    // API: acciones en lote
    // =========================================================================

    public function test_api_batch_suspend_actualiza_multiples_tarjetas(): void
    {
        $user = User::factory()->create();
        $p1   = $this->makeProgress($user, ['card_state' => UserProgress::STATE_YOUNG]);
        $p2   = $this->makeProgress($user, ['card_state' => UserProgress::STATE_MATURE]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/collection/batch', [
                'ids'    => [$p1->id, $p2->id],
                'action' => 'suspend',
            ])
            ->assertStatus(200)
            ->assertJsonPath('affected', 2);

        $this->assertEquals(UserProgress::STATE_SUSPENDED, $p1->fresh()->card_state);
        $this->assertEquals(UserProgress::STATE_SUSPENDED, $p2->fresh()->card_state);
    }

    public function test_api_batch_no_puede_afectar_tarjetas_de_otro_usuario(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $p     = $this->makeProgress($user2);

        $this->actingAs($user1, 'sanctum')
            ->postJson('/api/collection/batch', [
                'ids'    => [$p->id],
                'action' => 'suspend',
            ])
            ->assertStatus(200)
            ->assertJsonPath('affected', 0); // 0 afectados (no le pertenece)

        $this->assertNotEquals(UserProgress::STATE_SUSPENDED, $p->fresh()->card_state);
    }

    public function test_api_batch_delete_elimina_user_progress(): void
    {
        $user = User::factory()->create();
        $p1   = $this->makeProgress($user);
        $p2   = $this->makeProgress($user);
        $cid1 = $p1->item_id;
        $cid2 = $p2->item_id;

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/collection/batch', [
                'ids'    => [$p1->id, $p2->id],
                'action' => 'delete',
            ])
            ->assertStatus(200)
            ->assertJsonPath('affected', 2);

        $this->assertDatabaseMissing('user_progress', ['id' => $p1->id]);
        $this->assertDatabaseMissing('user_progress', ['id' => $p2->id]);
        // Compounds intactos
        $this->assertDatabaseHas('compounds', ['id' => $cid1]);
        $this->assertDatabaseHas('compounds', ['id' => $cid2]);
    }

    public function test_api_batch_reset_envía_tarjetas_a_learning(): void
    {
        $user = User::factory()->create();
        $this->settings($user);
        $p1 = $this->makeProgress($user, ['card_state' => UserProgress::STATE_MATURE, 'lapses' => 3]);
        $p2 = $this->makeProgress($user, ['card_state' => UserProgress::STATE_YOUNG]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/collection/batch', [
                'ids'    => [$p1->id, $p2->id],
                'action' => 'reset',
            ])
            ->assertStatus(200);

        $this->assertEquals(UserProgress::STATE_LEARNING, $p1->fresh()->card_state);
        $this->assertEquals(0, $p1->fresh()->lapses);
        $this->assertEquals(UserProgress::STATE_LEARNING, $p2->fresh()->card_state);
    }

    public function test_api_batch_valida_action_invalida(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/collection/batch', [
                'ids'    => [1],
                'action' => 'hack',
            ])
            ->assertStatus(422);
    }
}
