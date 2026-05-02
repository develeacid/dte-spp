<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_has_invitation_fields(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->assertTrue($user->isActivated());
        $this->assertFalse($user->isPendingActivation());
        $this->assertTrue($user->active);
    }

    public function test_invited_user_is_pending(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $this->assertFalse($user->isActivated());
        $this->assertTrue($user->isPendingActivation());
        $this->assertNotNull($user->invitation_token);
    }

    public function test_invitation_expires_after_72_hours(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create([
            'invitation_sent_at' => now()->subHours(73),
        ]);

        $this->assertTrue($user->isInvitationExpired());
    }

    public function test_invitation_valid_within_72_hours(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create([
            'invitation_sent_at' => now()->subHours(71),
        ]);

        $this->assertFalse($user->isInvitationExpired());
    }

    public function test_scope_pending_activation(): void
    {
        User::factory()->withPersonalTeam()->create(); // activo
        User::factory()->withPersonalTeam()->invited()->create(); // pendiente

        $this->assertCount(1, User::pendingActivation()->get());
    }

    public function test_scope_active(): void
    {
        User::factory()->withPersonalTeam()->create(['active' => true]);
        User::factory()->withPersonalTeam()->create(['active' => false]);

        $this->assertCount(1, User::active()->get());
    }
}
