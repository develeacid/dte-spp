<?php

namespace Tests\Feature;

use App\Models\OdsMeta;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatrizAlineacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            \Database\Seeders\PedSeeder::class,
            \Database\Seeders\PndSeeder::class,
            \Database\Seeders\OdsSeeder::class,
            \Database\Seeders\ProgramasDerivadosSeeder::class,
        ]);
    }

    // ============================================
    // Tests de Unique Constraints
    // ============================================

    public function test_no_permite_duplicar_alineacion_ped_pnd(): void
    {
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();

        // Crear primera alineación
        $pedObj->pndObjetivos()->attach($pndObj->id);

        // Intentar duplicar debe fallar
        $this->expectException(QueryException::class);
        $pedObj->pndObjetivos()->attach($pndObj->id);
    }

    public function test_no_permite_duplicar_alineacion_pnd_ods(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        $pndObj->odsMetas()->attach($odsMeta->id);

        $this->expectException(QueryException::class);
        $pndObj->odsMetas()->attach($odsMeta->id);
    }

    public function test_no_permite_duplicar_alineacion_linea_programa(): void
    {
        $linea = PedLineaAccion::first();
        $progObj = ProgramaDerivadoObjetivo::first();

        $linea->programasDerivadosObjetivos()->attach($progObj->id);

        $this->expectException(QueryException::class);
        $linea->programasDerivadosObjetivos()->attach($progObj->id);
    }

    // ============================================
    // Tests de Navegación de Cadena
    // ============================================

    public function test_navegacion_ped_a_pnd(): void
    {
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();

        $pedObj->pndObjetivos()->attach($pndObj->id);

        $this->assertCount(1, $pedObj->fresh()->pndObjetivos);
        $this->assertEquals($pndObj->id, $pedObj->pndObjetivos->first()->id);
    }

    public function test_navegacion_pnd_a_ods(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        $pndObj->odsMetas()->attach($odsMeta->id);

        $this->assertCount(1, $pndObj->fresh()->odsMetas);
        $this->assertEquals($odsMeta->id, $pndObj->odsMetas->first()->id);
    }

    public function test_navegacion_inversa_ods_a_pnd(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        $pndObj->odsMetas()->attach($odsMeta->id);

        $this->assertCount(1, $odsMeta->fresh()->pndObjetivos);
        $this->assertEquals($pndObj->id, $odsMeta->pndObjetivos->first()->id);
    }

    public function test_cadena_completa_linea_accion_a_ods(): void
    {
        // Crear alineaciones
        $linea = PedLineaAccion::first();
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::where('clave', '1.1')->first();

        $pedObj->pndObjetivos()->attach($pndObj->id);
        $pndObj->odsMetas()->attach($odsMeta->id);

        // Navegar cadena completa con eager loading
        $linea = PedLineaAccion::with([
            'estrategia.objetivoEstrategico.pndObjetivos.odsMetas'
        ])->find($linea->id);

        $cadena = $linea->estrategia->objetivoEstrategico->pndObjetivos->first()->odsMetas;

        $this->assertGreaterThanOrEqual(1, $cadena->count());
        $this->assertEquals('1.1', $cadena->first()->clave);
    }

    public function test_un_objetivo_ped_puede_alinearse_a_multiples_pnd(): void
    {
        $pedObj = PedObjetivoEstrategico::first();
        $pndObjetivos = PndObjetivo::take(2)->get();

        foreach ($pndObjetivos as $pndObj) {
            $pedObj->pndObjetivos()->syncWithoutDetaching([$pndObj->id]);
        }

        $this->assertCount(2, $pedObj->fresh()->pndObjetivos);
    }

    public function test_un_objetivo_pnd_puede_alinearse_a_multiples_ods(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMetas = OdsMeta::whereIn('clave', ['1.1', '1.2'])->get();

        foreach ($odsMetas as $meta) {
            $pndObj->odsMetas()->syncWithoutDetaching([$meta->id]);
        }

        $this->assertCount(2, $pndObj->fresh()->odsMetas);
    }

    // ============================================
    // Tests de Cascade Delete
    // ============================================

    public function test_eliminar_ped_objetivo_elimina_alineaciones(): void
    {
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();

        $pedObj->pndObjetivos()->attach($pndObj->id);

        $this->assertDatabaseHas('alineacion_ped_pnd', [
            'ped_objetivo_estrategico_id' => $pedObj->id,
        ]);

        $pedObj->delete();

        $this->assertDatabaseMissing('alineacion_ped_pnd', [
            'ped_objetivo_estrategico_id' => $pedObj->id,
        ]);
    }

    public function test_eliminar_pnd_objetivo_elimina_alineaciones(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        $pndObj->odsMetas()->attach($odsMeta->id);

        $this->assertDatabaseHas('alineacion_pnd_ods', [
            'pnd_objetivo_id' => $pndObj->id,
        ]);

        $pndObj->delete();

        $this->assertDatabaseMissing('alineacion_pnd_ods', [
            'pnd_objetivo_id' => $pndObj->id,
        ]);
    }
}
