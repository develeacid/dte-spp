<?php

namespace Tests\Feature;

use App\Models\PedEje;
use App\Models\PedPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PedCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ============================================
    // Tests de Autorización
    // ============================================

    public function test_usuario_sin_permiso_no_puede_acceder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ped.index'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $response = $this->actingAs($user)->get(route('ped.index'));

        $response->assertOk();
        $response->assertSee('Plan Estatal de Desarrollo');
    }

    // ============================================
    // Tests de Plan
    // ============================================

    public function test_crear_plan_desde_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        Livewire::actingAs($user)
            ->test('ped-plan-form')
            ->call('create')
            ->set('nombre', 'Plan de Prueba 2025-2030')
            ->set('periodo_inicio', 2025)
            ->set('periodo_fin', 2030)
            ->set('activo', true)
            ->call('save')
            ->assertDispatched('planCreated');

        $this->assertDatabaseHas('ped_planes', [
            'nombre' => 'Plan de Prueba 2025-2030',
            'activo' => true,
        ]);
    }

    public function test_editar_plan_desde_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Original',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => false,
        ]);

        Livewire::actingAs($user)
            ->test('ped-plan-form')
            ->call('edit', $plan)
            ->set('nombre', 'Plan Editado')
            ->call('save')
            ->assertDispatched('planUpdated');

        $this->assertEquals('Plan Editado', $plan->fresh()->nombre);
    }

    public function test_eliminar_plan_desde_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan a Eliminar',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        Livewire::actingAs($user)
            ->test('ped-plan-form')
            ->call('edit', $plan)
            ->call('delete')
            ->assertDispatched('planDeleted');

        $this->assertDatabaseMissing('ped_planes', ['id' => $plan->id]);
    }

    // ============================================
    // Tests de Eje
    // ============================================

    public function test_crear_eje_desde_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        Livewire::actingAs($user)
            ->test('ped-nodo-form')
            ->call('create', 'eje', $plan->id)
            ->set('numero', '1')
            ->set('nombre', 'Eje de Prueba')
            ->set('descripcion', 'Descripción del eje')
            ->call('save')
            ->assertDispatched('nodeCreated');

        $this->assertDatabaseHas('ped_ejes', [
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => 'Eje de Prueba',
        ]);
    }

    public function test_eliminar_eje_elimina_temas_cascade(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => 'Eje Test',
        ]);

        $tema = $eje->temas()->create([
            'numero' => '1',
            'nombre' => 'Tema Test',
        ]);

        Livewire::actingAs($user)
            ->test('ped-nodo-form')
            ->call('edit', 'eje', $eje->id)
            ->call('delete');

        $this->assertDatabaseMissing('ped_ejes', ['id' => $eje->id]);
        $this->assertDatabaseMissing('ped_temas', ['id' => $tema->id]);
    }

    // ============================================
    // Tests de Validación
    // ============================================

    public function test_validacion_periodo_fin_mayor_que_inicio(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        Livewire::actingAs($user)
            ->test('ped-plan-form')
            ->call('create')
            ->set('nombre', 'Plan Test')
            ->set('periodo_inicio', 2030)
            ->set('periodo_fin', 2025)
            ->call('save')
            ->assertHasErrors(['periodo_fin']);
    }

    public function test_validacion_descripcion_max_500_caracteres(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        Livewire::actingAs($user)
            ->test('ped-nodo-form')
            ->call('create', 'eje', $plan->id)
            ->set('numero', '1')
            ->set('nombre', 'Eje Test')
            ->set('descripcion', str_repeat('a', 501))
            ->call('save')
            ->assertHasErrors(['descripcion']);
    }

    // ============================================
    // Test de Constraint Plan Único Activo
    // ============================================

    public function test_no_puede_haber_dos_planes_activos(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        PedPlan::create([
            'nombre' => 'Plan Activo 1',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PedPlan::create([
            'nombre' => 'Plan Activo 2',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);
    }
}
