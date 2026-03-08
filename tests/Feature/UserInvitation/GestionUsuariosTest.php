<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GestionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        // Register temporary route for activar.show (created in Task 6)
        \Illuminate\Support\Facades\Route::get('/activar/{token}', fn() => '')->name('activar.show');
        app('router')->getRoutes()->refreshNameLookups();
    }

    private function createAdmin(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_admin_can_access_gestion_usuarios(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/usuarios');

        $response->assertStatus(200);
        $response->assertSee('Gestión de Usuarios');
    }

    public function test_operador_cannot_access_gestion_usuarios(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('operador');

        $response = $this->actingAs($user)->get('/admin/usuarios');

        $response->assertStatus(403);
    }

    public function test_admin_can_send_invitation(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();

        // Crear un team no personal para la invitación
        $team = \App\Models\Team::forceCreate([
            'name' => 'Secretaría de Economía',
            'user_id' => $admin->id,
            'personal_team' => false,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\GestionUsuarios::class)
            ->set('showInviteForm', true)
            ->set('inviteEmail', 'test@gob.mx')
            ->set('inviteName', 'Test User')
            ->set('inviteRole', 'operador')
            ->set('inviteTeamId', $team->id)
            ->call('sendInvitation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'test@gob.mx',
            'name' => 'Test User',
        ]);
    }

    public function test_invitation_requires_valid_email(): void
    {
        $admin = $this->createAdmin();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\GestionUsuarios::class)
            ->set('showInviteForm', true)
            ->set('inviteEmail', 'not-an-email')
            ->set('inviteName', 'Test')
            ->set('inviteRole', 'operador')
            ->set('inviteTeamId', 1)
            ->call('sendInvitation')
            ->assertHasErrors(['inviteEmail']);
    }

    public function test_table_shows_user_status(): void
    {
        $admin = $this->createAdmin();
        User::factory()->withPersonalTeam()->create(['name' => 'Activo User']);
        User::factory()->withPersonalTeam()->invited()->create(['name' => 'Pendiente User']);

        $response = $this->actingAs($admin)->get('/admin/usuarios');

        $response->assertSee('Activo User');
        $response->assertSee('Pendiente User');
    }

    public function test_admin_can_toggle_user_active(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->withPersonalTeam()->create(['active' => true]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\GestionUsuarios::class)
            ->call('toggleActive', $user->id);

        $user->refresh();
        $this->assertFalse($user->active);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = $this->createAdmin();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\GestionUsuarios::class)
            ->call('toggleActive', $admin->id)
            ->assertStatus(403);
    }
}
