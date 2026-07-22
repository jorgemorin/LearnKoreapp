<?php

namespace Tests\Feature;

use App\Models\Compound;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests de Fase 6 — Flujos End-to-End Completos
 *
 * Verifican el flujo completo de los dos actores del sistema:
 *   - Usuario normal: registro → login → colección → repaso → estadísticas → perfil
 *   - Admin: login → cola de revisión → aprobar → eliminar
 */
class Fase6EndToEndTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 6.A — Flujo completo del usuario
    // =========================================================================

    public function test_dashboard_muestra_pendientes_y_estadisticas_reales(): void
    {
        $user      = User::factory()->create(['role' => 'user']);
        $compound1 = Compound::factory()->create();
        $compound2 = Compound::factory()->create();

        // 2 tarjetas vencidas (compounds distintos para respetar UNIQUE)
        UserProgress::factory()->create([
            'user_id'          => $user->id,
            'item_id'          => $compound1->id,
            'item_type'        => 'compound',
            'next_review_date' => Carbon::yesterday()->toDateString(),
        ]);
        UserProgress::factory()->create([
            'user_id'          => $user->id,
            'item_id'          => $compound2->id,
            'item_type'        => 'compound',
            'next_review_date' => Carbon::yesterday()->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200);

        // El dashboard debe mostrar "2" pendientes y el nombre del usuario
        $response->assertSee('2');
        $response->assertSee($user->name);
    }

    public function test_pagina_coleccion_accesible_para_usuario(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('collection'))
            ->assertStatus(200)
            ->assertSee('Mi Colección');
    }

    public function test_pagina_study_accesible_para_usuario(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('study'))
            ->assertStatus(200);
    }

    public function test_pagina_stats_accesible_para_usuario(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('stats'))
            ->assertStatus(200)
            ->assertSee('Estadísticas');
    }

    public function test_pagina_perfil_accesible_para_usuario_autenticado(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertStatus(200)
            ->assertSee('Mi Perfil')
            ->assertSee('Test User');
    }

    public function test_perfil_actualiza_nombre_y_email(): void
    {
        $user = User::factory()->create([
            'name'  => 'Nombre Viejo',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name'  => 'Nombre Nuevo',
                'email' => 'new@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'name'  => 'Nombre Nuevo',
            'email' => 'new@example.com',
        ]);
    }

    public function test_perfil_update_requiere_nombre(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name'  => '',
                'email' => $user->email,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_perfil_no_permite_email_duplicado(): void
    {
        $user1 = User::factory()->create(['email' => 'existente@example.com']);
        $user2 = User::factory()->create();

        $this->actingAs($user2)
            ->put(route('profile.update'), [
                'name'  => $user2->name,
                'email' => 'existente@example.com',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_cambio_contrasena_falla_con_actual_incorrecta(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'current_password'      => 'wrongpassword',
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertSessionHasErrors('current_password');
    }

    public function test_usuario_no_accede_a_rutas_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    // =========================================================================
    // 6.B — Flujo completo del admin
    // =========================================================================

    public function test_admin_ve_cola_de_pendientes_con_datos(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $compound = Compound::factory()->create(['status' => 'pending_review']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Cola de revisión');
    }

    public function test_admin_aprueba_compound_via_api(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $compound = Compound::factory()->create(['status' => 'pending_review']);

        $this->actingAs($admin)
            ->postJson("/api/admin/compound/{$compound->id}/approve")
            ->assertStatus(200);

        $this->assertDatabaseHas('compounds', [
            'id'     => $compound->id,
            'status' => 'verified',
        ]);
    }

    public function test_admin_elimina_compound_y_limpia_progreso_usuarios(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $user     = User::factory()->create(['role' => 'user']);
        $compound = Compound::factory()->create();

        UserProgress::factory()->create([
            'user_id'   => $user->id,
            'item_id'   => $compound->id,
            'item_type' => 'compound',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/compound/{$compound->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('compounds',     ['id' => $compound->id]);
        $this->assertDatabaseMissing('user_progress', ['item_id' => $compound->id, 'item_type' => 'compound']);
    }

    // =========================================================================
    // 6.C — Rutas protegidas (unauthenticated redirects)
    // =========================================================================

    public function test_dashboard_redirige_a_login_si_no_autenticado(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_collection_redirige_a_login_si_no_autenticado(): void
    {
        $this->get(route('collection'))->assertRedirect(route('login'));
    }

    public function test_study_redirige_a_login_si_no_autenticado(): void
    {
        $this->get(route('study'))->assertRedirect(route('login'));
    }

    public function test_stats_redirige_a_login_si_no_autenticado(): void
    {
        $this->get(route('stats'))->assertRedirect(route('login'));
    }

    public function test_profile_redirige_a_login_si_no_autenticado(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }
}
