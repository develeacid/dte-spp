<?php

namespace Tests\Feature\Transparencia\Livewire;

use App\Enums\EstadoDatasetAbierto;
use App\Livewire\Transparencia\Datasets\Edit;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\TransparenciaPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_autor_edita_su_borrador(): void
    {
        $autor = User::factory()->withPersonalTeam()->create();
        $autor->assignRole('planeador');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::BORRADOR,
            'creado_por' => $autor->id,
        ]);

        Livewire::actingAs($autor)
            ->test(Edit::class, ['dataset' => $ds])
            ->set('nombre', 'Nombre actualizado')
            ->set('descripcion', 'Descripción nueva')
            ->call('save');

        $fresh = $ds->fresh();
        $this->assertSame('Nombre actualizado', $fresh->nombre);
        $this->assertSame('Descripción nueva', $fresh->descripcion);
    }

    public function test_planeador_no_edita_borrador_ajeno(): void
    {
        $autor = User::factory()->withPersonalTeam()->create();
        $autor->assignRole('planeador');
        $otro = User::factory()->withPersonalTeam()->create();
        $otro->assignRole('planeador');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::BORRADOR,
            'creado_por' => $autor->id,
        ]);

        // mount() autoriza con la ability `update`, así que el planeador no autor
        // recibe 403 antes de inicializar el componente.
        Livewire::actingAs($otro)
            ->test(Edit::class, ['dataset' => $ds])
            ->assertForbidden();
    }

    public function test_dcat_metadata_invalido_no_guarda(): void
    {
        $autor = User::factory()->withPersonalTeam()->create();
        $autor->assignRole('planeador');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::BORRADOR,
            'creado_por' => $autor->id,
        ]);

        Livewire::actingAs($autor)
            ->test(Edit::class, ['dataset' => $ds])
            ->set('dcat_metadata_json', '{ broken json')
            ->call('save')
            ->assertHasErrors(['dcat_metadata_json']);
    }

    public function test_dataset_no_borrador_aborta_403(): void
    {
        $autor = User::factory()->withPersonalTeam()->create();
        $autor->assignRole('planeador');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::REVISION,
            'creado_por' => $autor->id,
        ]);

        Livewire::actingAs($autor)
            ->test(Edit::class, ['dataset' => $ds])
            ->assertForbidden();
    }
}
