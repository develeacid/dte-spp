<?php

namespace Tests\Feature\Notifications;

use App\Livewire\NotificationsIndex;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_notifications_page_renders(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notificaciones');
    }

    public function test_filter_unread_only(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        \DB::table('notifications')->insert([
            [
                'id' => Str::uuid(),
                'type' => 'TestNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => json_encode(['message' => 'Unread one']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'TestNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => json_encode(['message' => 'Read one']),
                'read_at' => now(),
                'created_at' => now()->subHour(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationsIndex::class)
            ->set('filter', 'unread')
            ->assertSee('Unread one')
            ->assertDontSee('Read one');
    }

    public function test_mark_as_read(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $notifId = Str::uuid()->toString();
        \DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Test']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationsIndex::class)
            ->call('markAsRead', $notifId);

        $this->assertNotNull(
            \DB::table('notifications')->where('id', $notifId)->value('read_at')
        );
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }
}
