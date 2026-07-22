<?php

namespace Tests\Feature;

use App\Models\Compound;
use App\Models\Entity;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\AdminCurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de Fase 4 — Backoffice y Control de Calidad
 *
 * Verifican:
 *   - RBAC: usuario normal recibe 403 en endpoints admin
 *   - approve: cambia status a 'verified'
 *   - delete: cascade controlado limpia user_progress de todos los usuarios
 *   - update: modifica traducción y tags correctamente
 *   - API REST: GET /api/admin/queue, POST approve, PUT update, DELETE destroy
 */
class Fase4AdminTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 4.A — RBAC: solo admin puede acceder
    // =========================================================================

    public function test_usuario_normal_recibe_403_en_api_admin_queue(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->getJson('/api/admin/queue')
            ->assertStatus(403);
    }

    public function test_usuario_normal_recibe_403_en_api_admin_approve(): void
    {
        $user     = User::factory()->create(['role' => 'user']);
        $compound = Compound::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/admin/compound/{$compound->id}/approve")
            ->assertStatus(403);
    }

    public function test_usuario_normal_recibe_403_en_api_admin_delete(): void
    {
        $user     = User::factory()->create(['role' => 'user']);
        $compound = Compound::factory()->create();

        $this->actingAs($user)
            ->deleteJson("/api/admin/compound/{$compound->id}")
            ->assertStatus(403);
    }

    public function test_usuario_sin_autenticar_recibe_401_en_api_admin(): void
    {
        $this->getJson('/api/admin/queue')->assertStatus(401);
    }

    // =========================================================================
    // 4.B — AdminCurationService: approve
    // =========================================================================

    public function test_approve_cambia_status_a_verified(): void
    {
        $compound = Compound::factory()->create(['status' => 'pending_review']);

        app(AdminCurationService::class)->approve('compound', $compound->id);

        $this->assertDatabaseHas('compounds', [
            'id'     => $compound->id,
            'status' => 'verified',
        ]);
    }

    public function test_api_admin_approve_cambia_status(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $compound = Compound::factory()->create(['status' => 'pending_review']);

        $this->actingAs($admin)
            ->postJson("/api/admin/compound/{$compound->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('compounds', ['id' => $compound->id, 'status' => 'verified']);
    }

    // =========================================================================
    // 4.C — AdminCurationService: delete con cascade controlado
    // =========================================================================

    public function test_delete_elimina_compound_y_su_user_progress(): void
    {
        $user     = User::factory()->create();
        $compound = Compound::factory()->create();
        $progress = UserProgress::factory()->create([
            'user_id'   => $user->id,
            'item_id'   => $compound->id,
            'item_type' => 'compound',
        ]);

        app(AdminCurationService::class)->delete('compound', $compound->id);

        $this->assertDatabaseMissing('compounds',     ['id' => $compound->id]);
        $this->assertDatabaseMissing('user_progress', ['id' => $progress->id]);
    }

    public function test_delete_limpia_user_progress_de_multiples_usuarios(): void
    {
        $users    = User::factory()->count(3)->create();
        $compound = Compound::factory()->create();

        foreach ($users as $user) {
            UserProgress::factory()->create([
                'user_id'   => $user->id,
                'item_id'   => $compound->id,
                'item_type' => 'compound',
            ]);
        }

        $this->assertDatabaseCount('user_progress', 3);

        app(AdminCurationService::class)->delete('compound', $compound->id);

        $this->assertDatabaseMissing('compounds', ['id' => $compound->id]);
        $this->assertDatabaseCount('user_progress', 0);
    }

    public function test_api_admin_delete_elimina_compound(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $compound = Compound::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/compound/{$compound->id}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseMissing('compounds', ['id' => $compound->id]);
    }

    // =========================================================================
    // 4.D — AdminCurationService: update (traducción y tags)
    // =========================================================================

    public function test_update_cambia_traduccion_del_compound(): void
    {
        $compound = Compound::factory()->create(['translation' => 'traducción original']);

        app(AdminCurationService::class)->update('compound', $compound->id, [
            'translation' => 'nueva traducción',
        ]);

        $this->assertDatabaseHas('compounds', [
            'id'          => $compound->id,
            'translation' => 'nueva traducción',
        ]);
    }

    public function test_update_sincroniza_tags_del_compound(): void
    {
        $compound = Compound::factory()->create();
        $oldTag   = Tag::factory()->create(['name' => 'Tag_viejo_99']);
        $compound->tags()->attach($oldTag->id);

        app(AdminCurationService::class)->update('compound', $compound->id, [
            'translation' => 'algo',
            'tags'        => ['Educación', 'Nuevo'],
        ]);

        $compound->refresh();
        $tagNames = $compound->tags->pluck('name')->toArray();

        $this->assertContains('Educación', $tagNames);
        $this->assertContains('Nuevo', $tagNames);
        $this->assertNotContains('Tag_viejo_99', $tagNames);
    }

    public function test_api_admin_update_devuelve_200(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $compound = Compound::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/admin/compound/{$compound->id}", [
                'translation' => 'traducción actualizada',
                'tags'        => ['Saludos'],
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('compounds', [
            'id'          => $compound->id,
            'translation' => 'traducción actualizada',
        ]);
    }

    // =========================================================================
    // 4.E — API: GET /api/admin/queue
    // =========================================================================

    public function test_api_admin_queue_devuelve_solo_pendientes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Compound::factory()->count(3)->create(['status' => 'pending_review']);
        Compound::factory()->count(2)->create(['status' => 'verified']);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/queue')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'type', 'full_text', 'translation', 'status']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $this->assertEquals(3, $response->json('meta.total'));
    }
}
