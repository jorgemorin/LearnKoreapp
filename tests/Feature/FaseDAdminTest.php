<?php

namespace Tests\Feature;

use App\Models\AdminActionsLog;
use App\Models\Compound;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\UserReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests de Fase D — Panel Admin + Sistema de Reportes
 *
 * Verifica:
 *   - Flujo completo de reportes (crear, listar, resolver)
 *   - Gestión de usuarios por admins (activar/desactivar, roles)
 *   - Log de auditoría registra acciones
 *   - Cuentas desactivadas no pueden iniciar sesión
 */
class FaseDAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function normalUser(): User
    {
        return User::factory()->create(['role' => 'user', 'is_active' => true]);
    }

    // =========================================================================
    // D.1 — Sistema de reportes: usuarios
    // =========================================================================

    public function test_usuario_puede_crear_reporte(): void
    {
        $user = $this->normalUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports', [
                'category'    => 'error_traduccion',
                'description' => 'La traducción de 가다 es incorrecta.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('report.status', 'pending')
            ->assertJsonPath('report.category', 'error_traduccion');

        $this->assertDatabaseHas('user_reports', [
            'user_id'  => $user->id,
            'status'   => 'pending',
            'category' => 'error_traduccion',
        ]);
    }

    public function test_usuario_puede_listar_sus_propios_reportes(): void
    {
        $user = $this->normalUser();
        UserReport::factory()->count(3)->create(['user_id' => $user->id]);

        // Reportes de otro usuario — no deben aparecer
        UserReport::factory()->count(2)->create(['user_id' => $this->normalUser()->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports')
            ->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_reporte_requiere_autenticacion(): void
    {
        $this->postJson('/api/reports', [
            'category'    => 'bug',
            'description' => 'Algo',
        ])->assertStatus(401);
    }

    public function test_categoria_invalida_retorna_422(): void
    {
        $user = $this->normalUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports', [
                'category'    => 'categoria_falsa',
                'description' => 'Descripción',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    // =========================================================================
    // D.2 — Sistema de reportes: admin
    // =========================================================================

    public function test_admin_puede_listar_todos_los_reportes(): void
    {
        $admin = $this->admin();
        UserReport::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/reports')
            ->assertStatus(200);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    public function test_admin_puede_resolver_un_reporte(): void
    {
        $admin  = $this->admin();
        $report = UserReport::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/reports/{$report->id}", [
                'status'      => 'resolved',
                'admin_notes' => 'Corregido en vocabulario.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('report.status', 'resolved');

        $this->assertDatabaseHas('user_reports', [
            'id'          => $report->id,
            'status'      => 'resolved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_resolver_reporte_registra_en_audit_log(): void
    {
        $admin  = $this->admin();
        $report = UserReport::factory()->create(['status' => 'pending']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/reports/{$report->id}", [
                'status' => 'resolved',
            ]);

        $this->assertDatabaseHas('admin_actions_log', [
            'admin_id'    => $admin->id,
            'action_type' => 'report.resolved',
            'target_type' => 'report',
            'target_id'   => $report->id,
        ]);
    }

    public function test_usuario_normal_no_puede_acceder_a_admin_reports(): void
    {
        $user = $this->normalUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/reports')
            ->assertStatus(403);
    }

    // =========================================================================
    // D.3 — Gestión de usuarios
    // =========================================================================

    public function test_admin_puede_listar_usuarios(): void
    {
        $admin = $this->admin();
        User::factory()->count(5)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_puede_ver_detalle_de_usuario(): void
    {
        $admin = $this->admin();
        $user  = $this->normalUser();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['user', 'srs_states', 'accuracy', 'reports']);
    }

    public function test_admin_puede_cambiar_rol_de_usuario(): void
    {
        $admin = $this->admin();
        $user  = $this->normalUser();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$user->id}/role", ['role' => 'admin'])
            ->assertStatus(200)
            ->assertJsonPath('role', 'admin');

        $this->assertEquals('admin', $user->fresh()->role);

        // Verifica log de auditoría
        $this->assertDatabaseHas('admin_actions_log', [
            'admin_id'    => $admin->id,
            'action_type' => 'user.role_change',
            'target_id'   => $user->id,
        ]);
    }

    public function test_admin_no_puede_cambiar_su_propio_rol(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$admin->id}/role", ['role' => 'user'])
            ->assertStatus(403);
    }

    public function test_admin_puede_desactivar_usuario(): void
    {
        $admin = $this->admin();
        $user  = $this->normalUser();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$user->id}/active")
            ->assertStatus(200)
            ->assertJsonPath('is_active', false);

        $this->assertFalse($user->fresh()->is_active);

        $this->assertDatabaseHas('admin_actions_log', [
            'admin_id'    => $admin->id,
            'action_type' => 'user.deactivate',
            'target_id'   => $user->id,
        ]);
    }

    public function test_admin_puede_reactivar_usuario(): void
    {
        $admin = $this->admin();
        $user  = User::factory()->create(['role' => 'user', 'is_active' => false]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$user->id}/active")
            ->assertStatus(200)
            ->assertJsonPath('is_active', true);

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$admin->id}/active")
            ->assertStatus(403);
    }

    // =========================================================================
    // D.4 — is_active: usuarios inactivos no pueden iniciar sesión
    // =========================================================================

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->create([
            'role'      => 'user',
            'is_active' => false,
            'password'  => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        // No debería estar autenticado
        $this->assertGuest();
    }

    // =========================================================================
    // D.5 — Gestión de tags
    // =========================================================================

    public function test_admin_puede_listar_tags_con_conteo(): void
    {
        $admin = $this->admin();
        Tag::factory()->count(5)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/tags')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'usage_count']]]);
    }

    public function test_admin_puede_fusionar_dos_tags(): void
    {
        $admin  = $this->admin();
        $source = Tag::factory()->create(['name' => 'tag_source', 'is_standard' => true]);
        $target = Tag::factory()->create(['name' => 'tag_target', 'is_standard' => true]);

        // Asignar compound al source
        $compound = Compound::factory()->create();
        $compound->tags()->attach($source->id);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/tags/merge', [
                'source_id' => $source->id,
                'target_id' => $target->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        // Source debe haber sido eliminado
        $this->assertDatabaseMissing('tags', ['id' => $source->id]);

        // El compound debe tener el target
        $this->assertDatabaseHas('taggables', [
            'tag_id'        => $target->id,
            'taggable_id'   => $compound->id,
            'taggable_type' => 'compound',
        ]);

        // Log de auditoría
        $this->assertDatabaseHas('admin_actions_log', [
            'admin_id'    => $admin->id,
            'action_type' => 'tag.merge',
        ]);
    }

    // =========================================================================
    // D.6 — Log de auditoría
    // =========================================================================

    public function test_admin_puede_ver_el_log_de_auditoria(): void
    {
        $admin = $this->admin();

        // Generar una entrada en el log
        AdminActionsLog::record(
            adminId:    $admin->id,
            actionType: 'test.action',
            targetType: 'user',
            targetId:   1,
            payload:    ['test' => true]
        );

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/log')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'admin', 'action_type', 'created_at']]]);
    }

    public function test_admin_actions_log_es_inmutable(): void
    {
        $this->assertNull(AdminActionsLog::UPDATED_AT);
    }
}
