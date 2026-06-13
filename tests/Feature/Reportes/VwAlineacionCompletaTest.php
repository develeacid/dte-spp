<?php

namespace Tests\Feature\Reportes;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use App\Models\PndEje;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;
use App\Models\ProgramaPresupuestario;
use App\Models\Reportes\VwAlineacionCompleta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VwAlineacionCompletaTest extends TestCase
{
    use RefreshDatabase;

    public function test_resuelve_la_cadena_ascendente_completa_de_un_programa(): void
    {
        $plan = PedPlan::create(['nombre' => 'PED 2022-2028', 'nivel_gobierno' => 'estatal', 'periodo_inicio' => 2022, 'periodo_fin' => 2028, 'activo' => true]);
        $eje = PedEje::create(['ped_plan_id' => $plan->id, 'numero' => '1', 'nombre' => 'Desarrollo Económico', 'descripcion' => 'Eje económico']);
        $tema = PedTema::create(['ped_eje_id' => $eje->id, 'numero' => '1.1', 'nombre' => 'Industria', 'descripcion' => 'Tema industria']);
        $objEstr = PedObjetivoEstrategico::create(['ped_tema_id' => $tema->id, 'clave' => 'OE1', 'descripcion' => 'Objetivo estratégico X']);
        $estrategia = PedEstrategia::create(['ped_objetivo_estrategico_id' => $objEstr->id, 'clave' => 'E1', 'descripcion' => 'Estrategia']);
        $linea = PedLineaAccion::create(['ped_estrategia_id' => $estrategia->id, 'clave' => 'LA1', 'descripcion' => 'Línea de acción Y']);

        $pndEje = PndEje::create(['numero' => 1, 'nombre' => 'Eje PND', 'descripcion' => 'x']);
        $pnd = PndObjetivo::create(['pnd_eje_id' => $pndEje->id, 'clave' => '1.1', 'descripcion' => 'Objetivo PND Z']);

        $odsObj = OdsObjetivo::create(['numero' => 8, 'nombre' => 'Trabajo decente', 'descripcion' => 'x']);
        $ods = OdsMeta::create(['ods_objetivo_id' => $odsObj->id, 'clave' => '8.3', 'descripcion' => 'Meta ODS W']);

        $pd = ProgramaDerivado::create(['ped_plan_id' => $plan->id, 'nombre' => 'Programa Sectorial Q', 'descripcion' => 'x', 'tipo' => 'sectorial']);
        $pdo = ProgramaDerivadoObjetivo::create(['programa_derivado_id' => $pd->id, 'clave' => 'PDO1', 'descripcion' => 'Objetivo PD']);

        $programa = ProgramaPresupuestario::factory()->create();
        MirNivel::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'ped_objetivo_estrategico_id' => $objEstr->id,
            'ped_linea_accion_id' => $linea->id,
            'programa_derivado_objetivo_id' => $pdo->id,
        ]);

        DB::table('alineacion_ped_pnd')->insert(['ped_objetivo_estrategico_id' => $objEstr->id, 'pnd_objetivo_id' => $pnd->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('alineacion_pnd_ods')->insert(['pnd_objetivo_id' => $pnd->id, 'ods_meta_id' => $ods->id, 'created_at' => now(), 'updated_at' => now()]);

        $row = VwAlineacionCompleta::where('programa_id', $programa->id)->first();

        $this->assertNotNull($row);
        $this->assertSame($programa->clave, $row->programa_clave);
        $this->assertSame('Desarrollo Económico', $row->ped_eje);
        $this->assertSame('Objetivo estratégico X', $row->ped_objetivo_estrategico);
        $this->assertStringContainsString('Línea de acción Y', $row->ped_linea_accion);
        $this->assertStringContainsString('Programa Sectorial Q', $row->programas_derivados);
        $this->assertStringContainsString('Objetivo PND Z', $row->pnd_objetivos);
        $this->assertStringContainsString('Meta ODS W', $row->ods_metas);
    }

    public function test_programa_sin_alineacion_aparece_con_cascada_nula(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $row = VwAlineacionCompleta::where('programa_id', $programa->id)->first();

        $this->assertNotNull($row);
        $this->assertNull($row->ped_eje);
        $this->assertNull($row->pnd_objetivos);
        $this->assertNull($row->ods_metas);
    }
}
