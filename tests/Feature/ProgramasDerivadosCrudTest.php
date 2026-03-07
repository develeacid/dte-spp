<?php

namespace Tests\Feature;

use App\Enums\TipoProgramaDerivado;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramasDerivadosCrudTest extends TestCase
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

        $response = $this->actingAs($user)->get(route('cascade.programas-derivados.index'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->givePermissionTo('gestionar_catalogos');

        PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get(route('cascade.programas-derivados.index'));

        $response->assertOk();
        $response->assertSee('Programas Derivados');
    }

    public function test_sin_ped_activo_muestra_advertencia(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $response = $this->actingAs($user)->get(route('cascade.programas-derivados.index'));

        $response->assertOk();
        $response->assertSee('No hay Plan Estatal de Desarrollo activo');
    }

    // ============================================
    // Tests de CRUD de Programas
    // ============================================

    public function test_crear_programa_derivado(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->call('createPrograma')
            ->set('programaNombre', 'Programa Sectorial de Educación')
            ->set('programaTipo', TipoProgramaDerivado::SECTORIAL->value)
            ->set('programaDescripcion', 'Descripción del programa')
            ->call('savePrograma')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('programas_derivados', [
            'nombre' => 'Programa Sectorial de Educación',
            'tipo' => TipoProgramaDerivado::SECTORIAL->value,
        ]);
    }

    public function test_editar_programa_derivado(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Original',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->call('editPrograma', $programa->id)
            ->set('programaNombre', 'Programa Editado')
            ->call('savePrograma');

        $this->assertEquals('Programa Editado', $programa->fresh()->nombre);
    }

    public function test_eliminar_programa_derivado(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa a Eliminar',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->call('confirmDeletePrograma', $programa->id)
            ->call('deletePrograma');

        $this->assertDatabaseMissing('programas_derivados', ['id' => $programa->id]);
    }

    // ============================================
    // Tests de CRUD de Objetivos
    // ============================================

    public function test_crear_objetivo_en_programa(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Test',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->call('createObjetivo', $programa->id)
            ->set('objetivoClave', '1')
            ->set('objetivoDescripcion', 'Objetivo de prueba')
            ->call('saveObjetivo');

        $this->assertDatabaseHas('programas_derivados_objetivos', [
            'programa_derivado_id' => $programa->id,
            'clave' => '1',
            'descripcion' => 'Objetivo de prueba',
        ]);
    }

    public function test_eliminar_objetivo(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Test',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        $objetivo = $programa->objetivos()->create([
            'clave' => '1',
            'descripcion' => 'Objetivo a eliminar',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->call('deleteObjetivo', $objetivo->id);

        $this->assertDatabaseMissing('programas_derivados_objetivos', ['id' => $objetivo->id]);
    }

    // ============================================
    // Tests de Filtros
    // ============================================

    public function test_filtro_por_tipo_sectorial(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'PS Educacion Test',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'PE Salud Test',
            'tipo' => TipoProgramaDerivado::ESPECIAL,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->set('filtroTipo', TipoProgramaDerivado::SECTORIAL->value)
            ->assertSee('PS Educacion Test')
            ->assertDontSee('PE Salud Test');
    }

    // ============================================
    // Tests de Validación
    // ============================================

    public function test_validacion_tipo_requerido(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->call('createPrograma')
            ->set('programaNombre', 'Programa sin tipo')
            ->set('programaTipo', '')
            ->call('savePrograma')
            ->assertHasErrors(['programaTipo']);
    }

    public function test_validacion_tipo_invalido(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->call('createPrograma')
            ->set('programaNombre', 'Programa con tipo inválido')
            ->set('programaTipo', 'tipo_inexistente')
            ->call('savePrograma')
            ->assertHasErrors(['programaTipo']);
    }

    public function test_validacion_descripcion_objetivo_max_500(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Test',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->call('createObjetivo', $programa->id)
            ->set('objetivoClave', '1')
            ->set('objetivoDescripcion', str_repeat('a', 501))
            ->call('saveObjetivo')
            ->assertHasErrors(['objetivoDescripcion']);
    }

    // ============================================
    // Tests de Cascade Delete
    // ============================================

    public function test_eliminar_programa_elimina_objetivos(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa con Objetivos',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        $objetivo = $programa->objetivos()->create([
            'clave' => '1',
            'descripcion' => 'Objetivo test',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Cascade\ProgramasDerivadosManager::class)
            ->call('confirmDeletePrograma', $programa->id)
            ->assertSet('programaSeleccionado.objetivos_count', 1)
            ->call('deletePrograma');

        $this->assertDatabaseMissing('programas_derivados', ['id' => $programa->id]);
        $this->assertDatabaseMissing('programas_derivados_objetivos', ['id' => $objetivo->id]);
    }
}
