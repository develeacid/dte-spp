<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\Mml\MirNivel;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use App\Models\PndEje;
use App\Models\PndObjetivo;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\AlineacionEstrategicaPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class AlineacionEstrategicaPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_publish_replica_alineacion_minima(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'clave' => 'PROG-001',
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Resumen del Fin',
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'proposito',
            'resumen_narrativo' => 'Resumen del Proposito',
        ]);

        $publisher = app(AlineacionEstrategicaPublisher::class);
        $result = $publisher->publish(DatasetAbierto::factory()->create());

        $this->assertSame(1, $result['count']);

        $row = DB::connection('pgsql_public')->table('pub_alineacion_estrategica')->first();
        $this->assertSame('PROG-001', $row->programa_clave);
        $this->assertSame('Resumen del Fin', $row->mir_fin_resumen);
        $this->assertSame('Resumen del Proposito', $row->mir_proposito_resumen);
    }

    public function test_publish_llena_ods_y_pnd_desde_la_vista(): void
    {
        $plan = PedPlan::create(['nombre' => 'PED', 'nivel_gobierno' => 'estatal', 'periodo_inicio' => 2022, 'periodo_fin' => 2028, 'activo' => true]);
        $eje = PedEje::create(['ped_plan_id' => $plan->id, 'numero' => '1', 'nombre' => 'Desarrollo Económico', 'descripcion' => 'Eje']);
        $tema = PedTema::create(['ped_eje_id' => $eje->id, 'numero' => '1.1', 'nombre' => 'Industria', 'descripcion' => 'Tema']);
        $objEstr = PedObjetivoEstrategico::create(['ped_tema_id' => $tema->id, 'clave' => 'OE1', 'descripcion' => 'Objetivo estratégico X']);

        $pndEje = PndEje::create(['numero' => 1, 'nombre' => 'Eje PND', 'descripcion' => 'x']);
        $pnd = PndObjetivo::create(['pnd_eje_id' => $pndEje->id, 'clave' => '1.1', 'descripcion' => 'Objetivo PND Z']);

        $odsObj = OdsObjetivo::create(['numero' => 8, 'nombre' => 'Trabajo decente', 'descripcion' => 'x']);
        $ods = OdsMeta::create(['ods_objetivo_id' => $odsObj->id, 'clave' => '8.3', 'descripcion' => 'Meta ODS W']);

        $programa = ProgramaPresupuestario::factory()->create(['clave' => 'PROG-ALIN']);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Resumen del Fin',
            'ped_objetivo_estrategico_id' => $objEstr->id,
        ]);

        DB::table('alineacion_ped_pnd')->insert(['ped_objetivo_estrategico_id' => $objEstr->id, 'pnd_objetivo_id' => $pnd->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('alineacion_pnd_ods')->insert(['pnd_objetivo_id' => $pnd->id, 'ods_meta_id' => $ods->id, 'created_at' => now(), 'updated_at' => now()]);

        app(AlineacionEstrategicaPublisher::class)->publish(DatasetAbierto::factory()->create());

        $row = DB::connection('pgsql_public')->table('pub_alineacion_estrategica')
            ->where('programa_clave', 'PROG-ALIN')->first();

        $this->assertContains('8.3', json_decode($row->ods_metas, true));
        $this->assertStringContainsString('Objetivo PND Z', $row->pnd_objetivo);
        $this->assertStringContainsString('Objetivo estratégico X', $row->ped_objetivo_estrategico);
    }

    public function test_code_es_ds_05(): void
    {
        $this->assertSame('DS-05', app(AlineacionEstrategicaPublisher::class)->code());
    }
}
