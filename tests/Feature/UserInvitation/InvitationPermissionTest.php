<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_has_invitar_usuarios_permission(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->can('invitar_usuarios'));
    }

    public function test_planeador_does_not_have_invitar_usuarios_by_default(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');

        $this->assertFalse($user->can('invitar_usuarios'));
    }

    public function test_operador_does_not_have_invitar_usuarios(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('operador');

        $this->assertFalse($user->can('invitar_usuarios'));
    }
}
