<?php

namespace Tests\Feature\Evaluation\Externa;

use App\Enums\EstadoEvaluacionExterna;
use App\Enums\TipoEvaluacionExterna;
use App\Models\Evaluation\Asm;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\Evaluation\Hallazgo;
use App\Models\Evaluation\InformeEvaluacion;
use App\Models\Evaluation\Recomendacion;
use App\Models\ProgramaPresupuestario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeloTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadena_completa_y_navegacion_de_relaciones(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $evaluacion = EvaluacionExterna::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoEvaluacionExterna::DISENO,
            'estado' => EstadoEvaluacionExterna::EN_PROCESO,
        ]);

        $informe = InformeEvaluacion::factory()->create([
            'evaluacion_externa_id' => $evaluacion->id,
        ]);

        $hallazgo = Hallazgo::factory()->create([
            'informe_evaluacion_id' => $informe->id,
        ]);

        $recomendacion = Recomendacion::factory()->create([
            'hallazgo_id' => $hallazgo->id,
        ]);

        $asm = Asm::factory()->create([
            'recomendacion_id' => $recomendacion->id,
        ]);

        // Descendente.
        $this->assertTrue($evaluacion->programa->is($programa));
        $this->assertTrue($evaluacion->informe->is($informe));
        $this->assertTrue($informe->hallazgos->first()->is($hallazgo));
        $this->assertTrue($hallazgo->recomendaciones->first()->is($recomendacion));
        $this->assertTrue($recomendacion->asms->first()->is($asm));

        // Ascendente.
        $this->assertTrue($asm->recomendacion->is($recomendacion));
        $this->assertTrue($recomendacion->hallazgo->is($hallazgo));
        $this->assertTrue($hallazgo->informe->is($informe));
        $this->assertTrue($informe->evaluacionExterna->is($evaluacion));
    }

    public function test_la_cadena_de_factories_es_autosuficiente(): void
    {
        $recomendacion = Recomendacion::factory()->create();

        $this->assertNotNull($recomendacion->hallazgo);
        $this->assertNotNull($recomendacion->hallazgo->informe);
        $this->assertNotNull($recomendacion->hallazgo->informe->evaluacionExterna);
        $this->assertNotNull($recomendacion->hallazgo->informe->evaluacionExterna->programa);
    }

    public function test_borrar_informe_cascadea_hallazgos_y_recomendaciones_y_anula_asm(): void
    {
        $informe = InformeEvaluacion::factory()->create();
        $hallazgo = Hallazgo::factory()->create(['informe_evaluacion_id' => $informe->id]);
        $recomendacion = Recomendacion::factory()->create(['hallazgo_id' => $hallazgo->id]);
        $asm = Asm::factory()->create(['recomendacion_id' => $recomendacion->id]);

        $informe->delete();

        $this->assertDatabaseMissing('hallazgos', ['id' => $hallazgo->id]);
        $this->assertDatabaseMissing('recomendaciones', ['id' => $recomendacion->id]);

        $asm->refresh();
        $this->assertNull($asm->recomendacion_id);
    }

    public function test_borrar_evaluacion_externa_cascadea_el_informe(): void
    {
        $evaluacion = EvaluacionExterna::factory()->create();
        $informe = InformeEvaluacion::factory()->create([
            'evaluacion_externa_id' => $evaluacion->id,
        ]);

        $evaluacion->delete();

        $this->assertDatabaseMissing('informes_evaluacion', ['id' => $informe->id]);
    }

    public function test_los_enums_castean_correctamente(): void
    {
        $evaluacion = EvaluacionExterna::factory()->create([
            'tipo' => TipoEvaluacionExterna::CONSISTENCIA_RESULTADOS,
            'estado' => EstadoEvaluacionExterna::CONCLUIDA,
        ]);

        $evaluacion->refresh();

        $this->assertInstanceOf(TipoEvaluacionExterna::class, $evaluacion->tipo);
        $this->assertInstanceOf(EstadoEvaluacionExterna::class, $evaluacion->estado);
        $this->assertSame(TipoEvaluacionExterna::CONSISTENCIA_RESULTADOS, $evaluacion->tipo);
        $this->assertSame(EstadoEvaluacionExterna::CONCLUIDA, $evaluacion->estado);
    }

    public function test_dos_evaluaciones_del_mismo_programa_y_ejercicio_con_tipos_distintos_coexisten(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $a = EvaluacionExterna::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'tipo' => TipoEvaluacionExterna::DISENO,
        ]);

        $b = EvaluacionExterna::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'tipo' => TipoEvaluacionExterna::PROCESOS,
        ]);

        $this->assertNotSame($a->id, $b->id);
        $this->assertSame(2, EvaluacionExterna::where('programa_presupuestario_id', $programa->id)
            ->where('ejercicio_fiscal', 2026)->count());
    }
}
