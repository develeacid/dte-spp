<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserIsActivatedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_activated_user_can_access_dashboard(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $user = User::factory()->withPersonalTeam()->create(['active' => false]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_pending_user_is_redirected(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();
        $user->assignRole('operador');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_not_affected(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
