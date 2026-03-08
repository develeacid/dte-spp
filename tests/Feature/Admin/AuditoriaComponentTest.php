<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Auditoria;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditoriaComponentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->withPersonalTeam()->create();
        $this->admin->assignRole('admin');

        $this->operador = User::factory()->withPersonalTeam()->create();
        $this->operador->assignRole('operador');
    }

    public function test_admin_can_access_auditoria(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.auditoria'));

        $response->assertOk();
        $response->assertSeeLivewire(Auditoria::class);
    }

    public function test_non_admin_cannot_access_auditoria(): void
    {
        $this->actingAs($this->operador);

        $response = $this->get(route('admin.auditoria'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $response = $this->get(route('admin.auditoria'));

        $response->assertRedirect(route('login'));
    }

    public function test_activities_are_displayed(): void
    {
        // The admin user creation itself generates an activity log entry
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->assertSee('Auditoría del sistema')
            ->assertStatus(200);
    }

    public function test_filter_by_subject_type(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->set('subjectType', 'App\Models\User')
            ->assertStatus(200);
    }

    public function test_filter_by_date_range(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->set('fechaDesde', now()->subDays(7)->toDateString())
            ->set('fechaHasta', now()->toDateString())
            ->assertStatus(200);
    }

    public function test_filter_by_event(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->set('evento', 'created')
            ->assertStatus(200);
    }

    public function test_limpiar_filtros_resets_all(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->set('subjectType', 'App\Models\User')
            ->set('evento', 'updated')
            ->call('limpiarFiltros')
            ->assertSet('subjectType', '')
            ->assertSet('evento', '');
    }
}
