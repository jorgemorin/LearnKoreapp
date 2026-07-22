<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests de Fase 1 — Autenticación y Control de Acceso RBAC
 *
 * Verifican:
 *   - Registro de usuario con Livewire
 *   - Login correcto e incorrecto
 *   - Control de acceso por rol (admin vs user)
 */
class Fase1AuthTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Registro de usuario
    // =========================================================================

    public function test_registro_crea_usuario_correctamente(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test Usuario')
            ->set('email', 'test@learnkoreapp.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirectToRoute('dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'test@learnkoreapp.com',
            'role'  => User::ROLE_USER,
        ]);
    }

    public function test_registro_falla_si_email_ya_existe(): void
    {
        User::factory()->create(['email' => 'existente@learnkoreapp.com']);

        Livewire::test(Register::class)
            ->set('name', 'Otro')
            ->set('email', 'existente@learnkoreapp.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_registro_falla_si_passwords_no_coinciden(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test')
            ->set('email', 'test@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'diferente456')
            ->call('register')
            ->assertHasErrors(['password' => 'confirmed']);
    }

    public function test_registro_requiere_nombre(): void
    {
        Livewire::test(Register::class)
            ->set('name', '')
            ->set('email', 'test@test.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['name' => 'required']);
    }

    // =========================================================================
    // Login de usuario
    // =========================================================================

    public function test_login_correcto_redirige_al_dashboard(): void
    {
        $user = User::factory()->create([
            'email'    => 'user@learnkoreapp.com',
            'password' => bcrypt('password123'),
            'role'     => User::ROLE_USER,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'user@learnkoreapp.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_falla_con_credenciales_incorrectas(): void
    {
        User::factory()->create([
            'email'    => 'user@learnkoreapp.com',
            'password' => bcrypt('correcta'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'user@learnkoreapp.com')
            ->set('password', 'incorrecta')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    // =========================================================================
    // Control de acceso RBAC — rutas web
    // =========================================================================

    public function test_usuario_no_autenticado_redirige_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_usuario_normal_recibe_403_en_ruta_admin_web(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
             ->get('/admin')
             ->assertStatus(403);
    }

    public function test_administrador_accede_al_panel_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
             ->get('/admin')
             ->assertStatus(200);
    }

    public function test_usuario_no_autenticado_no_accede_a_admin_web(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    // =========================================================================
    // Control de acceso RBAC — rutas API
    // =========================================================================

    public function test_usuario_normal_recibe_403_en_api_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
             ->getJson('/api/admin/queue')
             ->assertStatus(403);
    }

    public function test_admin_accede_a_api_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
             ->getJson('/api/admin/queue')
             ->assertStatus(200);
    }

    public function test_usuario_no_autenticado_recibe_401_en_api(): void
    {
        $this->getJson('/api/admin/queue')
             ->assertStatus(401);
    }
}
