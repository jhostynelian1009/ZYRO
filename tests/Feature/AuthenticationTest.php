<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_always_assigns_customer_role(): void
    {
        $response = $this->post('/registro', [
            'name' => 'Cliente Demo',
            'email' => 'cliente@zyro.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('catalog.index'));

        $user = User::query()->where('email', 'cliente@zyro.test')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_cannot_access_admin_area(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_open_the_admin_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Panel administrativo', false);
    }

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'cliente@zyro.test',
            'password' => 'password',
        ]);

        $this->post('/iniciar-sesion', [
            'email' => 'cliente@zyro.test',
            'password' => 'password',
        ])->assertRedirect(route('catalog.index'));

        $this->assertAuthenticatedAs($user);

        $this->post('/cerrar-sesion')->assertRedirect(route('catalog.index'));
        $this->assertGuest();
    }

    public function test_login_page_renders_with_styled_card_and_inputs(): void
    {
        $response = $this->get('/iniciar-sesion');

        $response->assertOk();
        $response->assertSee('auth-card', false);
        $response->assertSee('auth-title', false);
        $response->assertSee('Iniciar sesión', false);
        $response->assertSee('btn-auth-submit', false);
        $response->assertSee('Entrar', false);
    }

    public function test_register_page_renders_with_styled_card_and_inputs(): void
    {
        $response = $this->get('/registro');

        $response->assertOk();
        $response->assertSee('auth-card', false);
        $response->assertSee('auth-title', false);
        $response->assertSee('Crear cuenta', false);
        $response->assertSee('btn-auth-submit', false);
        $response->assertSee('Registrarme', false);
    }

    public function test_login_shows_validation_error_when_credentials_are_invalid(): void
    {
        $response = $this->post('/iniciar-sesion', [
            'email' => 'noexiste@zyro.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
    }
}

