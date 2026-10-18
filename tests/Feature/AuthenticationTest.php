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
}
