<?php

namespace Tests\Feature;

use App\Models\OdsMeta;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivadoObjetivo;
use App\Models\User;
use Database\Seeders\Cascade\PedSeeder;
use Database\Seeders\Cascade\ProgramasDerivadosSeeder;
use Database\Seeders\Mml\OdsSeeder;
use Database\Seeders\Mml\PndSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MatrizAlineacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed([OdsSeeder::class, PndSeeder::class, PedSeeder::class, ProgramasDerivadosSeeder::class]);
    }

    // ============================================
    // Tests de Autorización
    // ============================================

    public function test_usuario_sin_permiso_no_puede_acceder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cascade.alineacion.index'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $response = $this->actingAs($user)->get(route('cascade.alineacion.index'));

        $response->assertOk();
        $response->assertSee('Matriz de Alineación');
    }

    // ============================================
    // Tests de Alineación PED ↔ PND
    // ============================================

    public function test_crear_alineacion_ped_pnd(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();

        Livewire::actingAs($user)
            ->test('cascade.alineacion-ped-pnd')
            ->call('toggleForm')
            ->set('searchPed', substr($pedObj->descripcion, 0, 10))
            ->call('selectPed', $pedObj->id)
            ->set('searchPnd', $pndObj->clave)
            ->call('selectPnd', $pndObj->id)
            ->call('crearAlineacion');

        $this->assertDatabaseHas('alineacion_ped_pnd', [
            'ped_objetivo_estrategico_id' => $pedObj->id,
            'pnd_objetivo_id' => $pndObj->id,
        ]);
    }

    public function test_eliminar_alineacion_ped_pnd(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();
        $pedObj->pndObjetivos()->attach($pndObj->id);

        Livewire::actingAs($user)
            ->test('cascade.alineacion-ped-pnd')
            ->call('eliminarAlineacion', $pedObj->id, $pndObj->id);

        $this->assertDatabaseMissing('alineacion_ped_pnd', [
            'ped_objetivo_estrategico_id' => $pedObj->id,
            'pnd_objetivo_id' => $pndObj->id,
        ]);
    }

    // ============================================
    // Tests de Alineación PND ↔ ODS
    // ============================================

    public function test_crear_alineacion_pnd_ods(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        Livewire::actingAs($user)
            ->test('cascade.alineacion-pnd-ods')
            ->call('toggleForm')
            ->set('searchPnd', $pndObj->clave)
            ->call('selectPnd', $pndObj->id)
            ->set('searchOds', $odsMeta->clave)
            ->call('selectOds', $odsMeta->id)
            ->call('crearAlineacion');

        $this->assertDatabaseHas('alineacion_pnd_ods', [
            'pnd_objetivo_id' => $pndObj->id,
            'ods_meta_id' => $odsMeta->id,
        ]);
    }

    // ============================================
    // Tests de Alineación Línea ↔ Programa
    // ============================================

    public function test_crear_alineacion_linea_programa(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $linea = PedLineaAccion::first();
        $progObj = ProgramaDerivadoObjetivo::first();

        Livewire::actingAs($user)
            ->test('cascade.alineacion-linea-programa')
            ->call('toggleForm')
            ->set('searchLinea', substr($linea->descripcion, 0, 10))
            ->call('selectLinea', $linea->id)
            ->set('searchPrograma', substr($progObj->descripcion, 0, 10))
            ->call('selectPrograma', $progObj->id)
            ->call('crearAlineacion');

        $this->assertDatabaseHas('alineacion_linea_programa', [
            'ped_linea_accion_id' => $linea->id,
            'programa_derivado_objetivo_id' => $progObj->id,
        ]);
    }

    // ============================================
    // Tests de Cadena Completa
    // ============================================

    public function test_herencia_de_cadena_completa(): void
    {
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::where('clave', '1.1')->first();

        $pedObj->pndObjetivos()->attach($pndObj->id);
        $pndObj->odsMetas()->attach($odsMeta->id);

        $linea = PedLineaAccion::whereHas('estrategia.objetivoEstrategico', fn ($q) => $q->where('id', $pedObj->id))->first();

        if (! $linea) {
            $estrategia = $pedObj->estrategias()->create(['clave' => '1', 'descripcion' => 'Test']);
            $linea = $estrategia->lineasAccion()->create(['clave' => '1', 'descripcion' => 'Test']);
        }

        $lineaConCadena = PedLineaAccion::with([
            'estrategia.objetivoEstrategico.pndObjetivos.odsMetas',
        ])->find($linea->id);

        $this->assertTrue($lineaConCadena->estrategia->objetivoEstrategico->pndObjetivos->contains($pndObj));
        $this->assertTrue(
            $lineaConCadena->estrategia->objetivoEstrategico->pndObjetivos->first()->odsMetas->contains($odsMeta)
        );
    }

    public function test_ver_cadena_completa_modal(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $linea = PedLineaAccion::first();

        Livewire::actingAs($user)
            ->test('cascade.cadena-alineacion')
            ->call('loadCadena', $linea->id)
            ->assertSet('showModal', true)
            ->assertSet('lineaAccionId', $linea->id);
    }

    // ============================================
    // Tests de Eager Loading (N+1)
    // ============================================

    public function test_eager_loading_evita_n1(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $pedObjetivos = PedObjetivoEstrategico::take(3)->get();
        $pndObjetivos = PndObjetivo::take(3)->get();

        foreach ($pedObjetivos as $i => $ped) {
            $ped->pndObjetivos()->attach($pndObjetivos[$i]->id);
        }

        $queries = [];
        \DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        Livewire::actingAs($user)
            ->test('cascade.alineacion-ped-pnd');

        $this->assertLessThan(10, count($queries), 'Se detectaron demasiadas queries. Posible problema N+1.');
    }
}
