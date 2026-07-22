<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test básico: la ruta raíz redirige al login si el usuario no está autenticado.
 */
class ExampleTest extends TestCase
{
    public function test_la_ruta_raiz_redirige_al_login_sin_autenticar(): void
    {
        $response = $this->get('/');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
