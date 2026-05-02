<?php

namespace Tests\Feature\Transparencia\Livewire;

use App\Enums\EstadoDatasetAbierto;
use App\Livewire\Transparencia\Datasets\CrearEntrega;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransparenciaPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CrearEntregaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TransparenciaPermissionsSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_planeador_clona_plantilla_a_periodo(): void
    {
        $planeador = User::factory()->withPersonalTeam()->create();
        $planeador->assignRole('planeador');
        $plantilla = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-01',
            'periodo' => null,
            'nombre' => 'MIR Programa',
        ]);

        Livewire::actingAs($planeador)
            ->test(CrearEntrega::class, ['dataset' => $plantilla])
            ->set('periodo', '2026-Q1')
            ->call('save');

        $entrega = DatasetAbierto::where('dataset_clave', 'DS-01')
            ->where('periodo', '2026-Q1')
            ->first();
        $this->assertNotNull($entrega);
        $this->assertSame($planeador->id, $entrega->creado_por);
        $this->assertSame(EstadoDatasetAbierto::BORRADOR, $entrega->status);
    }

    public function test_periodo_invalido_falla_con_mensaje(): void
    {
        $planeador = User::factory()->withPersonalTeam()->create();
        $planeador->assignRole('planeador');
        $plantilla = DatasetAbierto::factory()->create(['periodo' => null]);

        Livewire::actingAs($planeador)
            ->test(CrearEntrega::class, ['dataset' => $plantilla])
            ->set('periodo', '2026Q1')
            ->call('save')
            ->assertHasErrors(['periodo']);
    }

    public function test_no_permite_crear_desde_entrega(): void
    {
        $planeador = User::factory()->withPersonalTeam()->create();
        $planeador->assignRole('planeador');
        $entrega = DatasetAbierto::factory()->create(['periodo' => '2026-Q1']);

        Livewire::actingAs($planeador)
            ->test(CrearEntrega::class, ['dataset' => $entrega])
            ->assertStatus(404);
    }
}
