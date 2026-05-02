<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_valid_token_shows_password_form(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $response = $this->get(route('activar.show', ['token' => $user->invitation_token]));

        $response->assertStatus(200);
        $response->assertSee('Establece tu contraseña');
    }

    public function test_expired_token_shows_error(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create([
            'invitation_sent_at' => now()->subHours(73),
        ]);

        $response = $this->get(route('activar.show', ['token' => $user->invitation_token]));

        $response->assertSee('Enlace inválido o expirado');
    }

    public function test_invalid_token_shows_error(): void
    {
        $response = $this->get(route('activar.show', ['token' => 'invalid-token']));

        $response->assertSee('Enlace inválido o expirado');
    }

    public function test_can_set_password(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $response = $this->post(route('activar.password', ['token' => $user->invitation_token]), [
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('activar.2fa', ['token' => $user->invitation_token]));

        $user->refresh();
        $this->assertNotNull($user->password);
    }

    public function test_password_requires_confirmation(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $response = $this->post(route('activar.password', ['token' => $user->invitation_token]), [
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPass!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_2fa_page_redirects_if_no_password(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $response = $this->get(route('activar.2fa', ['token' => $user->invitation_token]));

        $response->assertRedirect(route('activar.show', ['token' => $user->invitation_token]));
    }

    public function test_already_activated_token_is_invalid(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'activated_at' => now(),
            'invitation_token' => 'some-token',
        ]);

        $response = $this->get(route('activar.show', ['token' => 'some-token']));

        $response->assertSee('Enlace inválido o expirado');
    }
}
