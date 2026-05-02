<?php

namespace Tests\Feature\Transparencia\Livewire;

use App\Livewire\Transparencia\Datasets\Index;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\TransparenciaPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_planeador_ve_la_lista(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        DatasetAbierto::factory()->create(['nombre' => 'Foo Dataset', 'periodo' => '2026-Q1']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Foo Dataset');
    }

    public function test_filtro_por_status(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        DatasetAbierto::factory()->create(['nombre' => 'En Borrador', 'status' => 'borrador']);
        DatasetAbierto::factory()->create(['nombre' => 'En Revision', 'status' => 'revision']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('filtroStatus', 'revision')
            ->assertSee('En Revision')
            ->assertDontSee('En Borrador');
    }

    public function test_filtro_por_tipo_plantilla(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        DatasetAbierto::factory()->create(['nombre' => 'PlantillaDS', 'periodo' => null]);
        DatasetAbierto::factory()->create(['nombre' => 'EntregaDS', 'periodo' => '2026-Q1']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('filtroTipo', 'plantilla')
            ->assertSee('PlantillaDS')
            ->assertDontSee('EntregaDS');
    }

    public function test_filtro_por_sistema(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        DatasetAbierto::factory()->create(['nombre' => 'SppDataset', 'sistema_origen' => 'spp']);
        DatasetAbierto::factory()->create(['nombre' => 'GeobaseDataset', 'sistema_origen' => 'geobase']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('filtroSistema', 'geobase')
            ->assertSee('GeobaseDataset')
            ->assertDontSee('SppDataset');
    }

    public function test_search_por_clave_o_nombre(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        DatasetAbierto::factory()->create(['dataset_clave' => 'DS-99', 'nombre' => 'Match Search Foo']);
        DatasetAbierto::factory()->create(['dataset_clave' => 'DS-77', 'nombre' => 'Other Bar']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'DS-99')
            ->assertSee('Match Search Foo')
            ->assertDontSee('Other Bar');
    }

    public function test_search_escapa_wildcards_like(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        DatasetAbierto::factory()->create(['nombre' => 'Reporte 100% completo']);
        DatasetAbierto::factory()->create(['nombre' => 'Otro nombre sin marca']);

        // Si % se interpretara como wildcard LIKE, '100%' coincidiría con cualquier
        // string que empiece con '100'. El test verifica que sólo coincide el literal.
        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', '100%')
            ->assertSee('Reporte 100% completo')
            ->assertDontSee('Otro nombre sin marca');
    }
}
