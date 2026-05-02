<?php

namespace Tests\Feature\Transparencia\Livewire;

use App\Enums\EstadoDatasetAbierto;
use App\Livewire\Transparencia\Datasets\Show;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\TransparenciaPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_autor_envia_a_revision(): void
    {
        $autor = User::factory()->withPersonalTeam()->create();
        $autor->assignRole('planeador');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::BORRADOR,
            'creado_por' => $autor->id,
        ]);

        Livewire::actingAs($autor)
            ->test(Show::class, ['dataset' => $ds])
            ->call('enviarARevision');

        $this->assertSame(EstadoDatasetAbierto::REVISION, $ds->fresh()->status);
    }

    public function test_rda_aprueba_revision(): void
    {
        $rda = User::factory()->withPersonalTeam()->create();
        $rda->assignRole('responsable_datos_abiertos');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::REVISION,
        ]);

        Livewire::actingAs($rda)
            ->test(Show::class, ['dataset' => $ds])
            ->call('aprobar');

        $this->assertSame(EstadoDatasetAbierto::APROBADO, $ds->fresh()->status);
        $this->assertSame($rda->id, $ds->fresh()->aprobado_por);
    }

    public function test_planeador_no_puede_aprobar(): void
    {
        $planeador = User::factory()->withPersonalTeam()->create();
        $planeador->assignRole('planeador');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::REVISION,
        ]);

        Livewire::actingAs($planeador)
            ->test(Show::class, ['dataset' => $ds])
            ->call('aprobar')
            ->assertForbidden();
    }

    public function test_rda_rechaza_con_motivo(): void
    {
        $rda = User::factory()->withPersonalTeam()->create();
        $rda->assignRole('responsable_datos_abiertos');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::REVISION,
            'aprobado_por' => $rda->id,
            'aprobado_en' => now(),
        ]);

        Livewire::actingAs($rda)
            ->test(Show::class, ['dataset' => $ds])
            ->set('motivo', 'Faltan keywords del DCAT')
            ->call('rechazar');

        $fresh = $ds->fresh();
        $this->assertSame(EstadoDatasetAbierto::BORRADOR, $fresh->status);
        $this->assertSame('Faltan keywords del DCAT', $fresh->motivo_cambio_estado);
        $this->assertNull($fresh->aprobado_por);
    }

    public function test_rechazar_requiere_motivo(): void
    {
        $rda = User::factory()->withPersonalTeam()->create();
        $rda->assignRole('responsable_datos_abiertos');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::REVISION,
        ]);

        Livewire::actingAs($rda)
            ->test(Show::class, ['dataset' => $ds])
            ->set('motivo', '')
            ->call('rechazar')
            ->assertHasErrors(['motivo']);
    }

    public function test_rda_publica_aprobado(): void
    {
        $rda = User::factory()->withPersonalTeam()->create();
        $rda->assignRole('responsable_datos_abiertos');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);

        Livewire::actingAs($rda)
            ->test(Show::class, ['dataset' => $ds])
            ->call('publicar');

        $fresh = $ds->fresh();
        $this->assertSame(EstadoDatasetAbierto::PUBLICADO, $fresh->status);
        $this->assertNotNull($fresh->publicado_en);
    }

    public function test_rda_retira_publicado(): void
    {
        $rda = User::factory()->withPersonalTeam()->create();
        $rda->assignRole('responsable_datos_abiertos');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);

        Livewire::actingAs($rda)
            ->test(Show::class, ['dataset' => $ds])
            ->set('motivo', 'Información detectada con error de fuente')
            ->call('retirar');

        $fresh = $ds->fresh();
        $this->assertSame(EstadoDatasetAbierto::RETIRADO, $fresh->status);
        $this->assertSame('Información detectada con error de fuente', $fresh->motivo_cambio_estado);
    }

    public function test_retirar_requiere_motivo(): void
    {
        $rda = User::factory()->withPersonalTeam()->create();
        $rda->assignRole('responsable_datos_abiertos');
        $ds = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);

        Livewire::actingAs($rda)
            ->test(Show::class, ['dataset' => $ds])
            ->set('motivo', '')
            ->call('retirar')
            ->assertHasErrors(['motivo']);
    }
}
