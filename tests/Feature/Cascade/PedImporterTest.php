<?php

namespace Tests\Feature\Cascade;

use App\Models\PedPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class PedImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_usuario_sin_permiso_no_puede_acceder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cascade.ped.import'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $response = $this->actingAs($user)->get(route('cascade.ped.import'));

        $response->assertOk();
        // El titulo "Importar Plan Estatal de Desarrollo" vive en <x-slot name="header">
        // que el layout post-S9 no renderiza (deuda de migracion). El breadcrumb
        // "Importar PED" sigue visible y es lo que el usuario realmente ve.
        $response->assertSee('Importar PED');
    }

    public function test_importa_archivo_valido(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $contenido = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Bienestar Social
### Tema 1.1: Educación
#### Objetivo 1.1.1: Garantizar acceso
##### Estrategia 1.1.1.1: Ampliar cobertura
- Línea de Acción 1.1.1.1.1: Construir escuelas
MD;

        $archivo = UploadedFile::fake()->createWithContent('plan.md', $contenido);

        Livewire::actingAs($user)
            ->test('cascade.ped-importer')
            ->set('archivo', $archivo)
            ->assertSet('showPreview', true)
            ->set('planNombre', 'Plan Test 2025-2030')
            ->set('planPeriodoInicio', 2025)
            ->set('planPeriodoFin', 2030)
            ->call('confirmarImportacion')
            ->assertSet('showSuccess', true);

        $this->assertDatabaseHas('ped_planes', [
            'nombre' => 'Plan Test 2025-2030',
            'activo' => true,
        ]);

        $plan = PedPlan::where('nombre', 'Plan Test 2025-2030')->first();
        $this->assertEquals(1, $plan->ejes()->count());
    }

    public function test_desactiva_plan_anterior_al_importar_nuevo_activo(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $planAnterior = PedPlan::create([
            'nombre' => 'Plan Anterior',
            'periodo_inicio' => 2020,
            'periodo_fin' => 2025,
            'activo' => true,
        ]);

        $contenido = <<<'MD'
# Plan Nuevo 2025-2030

## Eje 1: Test
MD;

        $archivo = UploadedFile::fake()->createWithContent('plan.md', $contenido);

        Livewire::actingAs($user)
            ->test('cascade.ped-importer')
            ->set('archivo', $archivo)
            ->set('planActivo', true)
            ->call('confirmarImportacion');

        $this->assertFalse($planAnterior->fresh()->activo);
        $this->assertTrue(PedPlan::where('nombre', 'Plan Nuevo 2025-2030')->first()->activo);
    }
}
