<?php

namespace Tests\Feature\Notifications;

use App\Livewire\NotificationBell;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_bell_renders_for_authenticated_user(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user);

        Livewire::test(NotificationBell::class)
            ->assertOk();
    }

    public function test_bell_shows_unread_count(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        \DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Test notification']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationBell::class)
            ->assertSee('Test notification');
    }

    public function test_mark_all_as_read(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        \DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Test notification']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationBell::class)
            ->assertSee('Test notification')
            ->call('markAllAsRead');

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_mark_single_as_read(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $notifId = Str::uuid()->toString();
        \DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Single test']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationBell::class)
            ->call('markAsRead', $notifId);

        $this->assertNotNull(
            \DB::table('notifications')->where('id', $notifId)->value('read_at')
        );
    }
}
