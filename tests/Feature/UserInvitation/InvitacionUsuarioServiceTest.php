<?php

namespace Tests\Feature\UserInvitation;

use App\Mail\InvitacionUsuario;
use App\Models\Team;
use App\Models\User;
use App\Services\InvitacionUsuarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitacionUsuarioServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        // Register temporary route for activar.show (created in Task 6)
        \Illuminate\Support\Facades\Route::get('/activar/{token}', fn () => '')->name('activar.show');
        $this->app['router']->getRoutes()->refreshNameLookups();
    }

    public function test_invitar_creates_user_with_token(): void
    {
        Mail::fake();

        $admin = User::factory()->withPersonalTeam()->create();
        $team = $admin->currentTeam;

        $service = new InvitacionUsuarioService();
        $user = $service->invitar('nuevo@gob.mx', 'Nuevo Usuario', 'operador', $team->id);

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@gob.mx',
            'name' => 'Nuevo Usuario',
        ]);
        $this->assertNull($user->password);
        $this->assertNull($user->activated_at);
        $this->assertNotNull($user->invitation_token);
        $this->assertTrue($user->hasRole('operador'));
        $this->assertEquals($team->id, $user->current_team_id);
    }

    public function test_invitar_sends_email(): void
    {
        Mail::fake();

        $admin = User::factory()->withPersonalTeam()->create();

        $service = new InvitacionUsuarioService();
        $service->invitar('nuevo@gob.mx', 'Nuevo Usuario', 'operador', $admin->currentTeam->id);

        Mail::assertSent(InvitacionUsuario::class, function ($mail) {
            return $mail->hasTo('nuevo@gob.mx');
        });
    }

    public function test_reenviar_generates_new_token(): void
    {
        Mail::fake();

        $user = User::factory()->withPersonalTeam()->invited()->create();
        $oldToken = $user->invitation_token;

        $service = new InvitacionUsuarioService();
        $service->reenviarInvitacion($user);

        $user->refresh();
        $this->assertNotEquals($oldToken, $user->invitation_token);
    }

    public function test_reenviar_sends_email(): void
    {
        Mail::fake();

        $user = User::factory()->withPersonalTeam()->invited()->create();
        $user->assignRole('operador');

        $service = new InvitacionUsuarioService();
        $service->reenviarInvitacion($user);

        Mail::assertSent(InvitacionUsuario::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
