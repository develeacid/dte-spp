<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_creation_is_logged(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Test Audit User',
            'email' => 'audit@test.com',
        ]);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('User created', $activity->description);
    }

    public function test_user_update_logs_only_dirty_attributes(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $user->update(['name' => 'Nombre Cambiado']);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('name', $activity->properties['attributes']);
        $this->assertEquals('Nombre Cambiado', $activity->properties['attributes']['name']);
    }

    public function test_user_activation_change_is_logged(): void
    {
        $user = User::factory()->withPersonalTeam()->create(['active' => true]);

        $user->update(['active' => false]);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('active', $activity->properties['attributes']);
        $this->assertFalse($activity->properties['attributes']['active']);
    }

    public function test_password_is_not_logged(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $user->update(['password' => 'new-password-123']);

        $activities = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->get();

        foreach ($activities as $activity) {
            $this->assertArrayNotHasKey('password', $activity->properties['attributes'] ?? []);
        }
    }
}
