<?php

namespace Tests\Feature\Transparencia\Livewire;

use App\Livewire\Transparencia\Datasets\EditarPlantilla;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditarPlantillaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\TransparenciaPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_rda_edita_plantilla(): void
    {
        $rda = User::factory()->withPersonalTeam()->create();
        $rda->assignRole('responsable_datos_abiertos');
        $plantilla = DatasetAbierto::factory()->create([
            'periodo' => null,
            'nombre' => 'Original',
        ]);

        Livewire::actingAs($rda)
            ->test(EditarPlantilla::class, ['dataset' => $plantilla])
            ->set('nombre', 'Plantilla actualizada')
            ->set('descripcion', 'Nueva descripción')
            ->call('save');

        $fresh = $plantilla->fresh();
        $this->assertSame('Plantilla actualizada', $fresh->nombre);
        $this->assertSame('Nueva descripción', $fresh->descripcion);
    }

    public function test_planeador_no_edita_plantilla(): void
    {
        $planeador = User::factory()->withPersonalTeam()->create();
        $planeador->assignRole('planeador');
        $plantilla = DatasetAbierto::factory()->create(['periodo' => null]);

        // El authorize en mount() devuelve 403 antes de que el componente se inicialice.
        Livewire::actingAs($planeador)
            ->test(EditarPlantilla::class, ['dataset' => $plantilla])
            ->assertForbidden();
    }
}
