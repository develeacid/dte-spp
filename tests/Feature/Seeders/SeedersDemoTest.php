<?php

namespace Tests\Feature\Seeders;

use App\Models\Evaluation\Asm;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\Evaluation\Hallazgo;
use App\Models\Evaluation\InformeEvaluacion;
use App\Models\Evaluation\Recomendacion;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\Mml\MirSupuesto;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Services\Mml\IndicadorReglasService;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_indicador_con_semaforo_pasa_validacion_de_rangos(): void
    {
        $indicador = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'ISM-001'))
            ->where('nombre', 'Índice de satisfacción de productores capacitados')
            ->firstOrFail();

        $errores = IndicadorReglasService::validarRangosSemaforoPrimitivos(
            $indicador->meta !== null ? (float) $indicador->meta : null,
            $indicador->unidadMedida?->clave,
            [
                'rango_verde_min' => $indicador->rango_verde_min,
                'rango_verde_max' => $indicador->rango_verde_max,
                'rango_amarillo_min' => $indicador->rango_amarillo_min,
                'rango_amarillo_max' => $indicador->rango_amarillo_max,
                'rango_rojo_min' => $indicador->rango_rojo_min,
                'rango_rojo_max' => $indicador->rango_rojo_max,
                'rango_rojo_alto_min' => $indicador->rango_rojo_alto_min,
                'rango_rojo_alto_max' => $indicador->rango_rojo_alto_max,
            ],
        );

        $this->assertSame([], $errores, 'Los rangos del indicador deben ser válidos según B3-B6: '.json_encode($errores));
    }

    public function test_niveles_de_ism001_tienen_supuestos_estructurados(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $niveles = MirNivel::where('programa_presupuestario_id', $programa->id)->get();

        foreach ($niveles as $nivel) {
            $this->assertTrue(
                MirSupuesto::where('mir_nivel_id', $nivel->id)->exists(),
                "El nivel {$nivel->tipo_nivel->value} de ISM-001 debe tener al menos un MirSupuesto estructurado."
            );
        }
    }

    public function test_existe_supuesto_con_validez_parcial(): void
    {
        // Para que la UI muestre ambos estados (válido / parcial), al menos
        // un supuesto debe NO ser 3/3 (es_externo true, resto false).
        $this->assertTrue(
            MirSupuesto::where('es_externo', true)
                ->where('es_relevante', false)
                ->where('probabilidad_razonable', false)
                ->exists(),
            'Debe existir al menos un supuesto con validez parcial para demo.'
        );

        $this->assertTrue(
            MirSupuesto::where('es_externo', true)
                ->where('es_relevante', true)
                ->where('probabilidad_razonable', true)
                ->exists(),
            'Debe existir al menos un supuesto 3/3 válido para demo.'
        );
    }

    public function test_cadena_completa_evaluacion_externa_existe(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $concluida = EvaluacionExterna::where('programa_presupuestario_id', $programa->id)
            ->where('tipo', 'diseno')
            ->where('estado', 'concluida')
            ->first();
        $this->assertNotNull($concluida, 'Debe existir la evaluación externa de diseño concluida.');

        $enProceso = EvaluacionExterna::where('programa_presupuestario_id', $programa->id)
            ->where('tipo', 'procesos')
            ->where('estado', 'en_proceso')
            ->first();
        $this->assertNotNull($enProceso, 'Debe existir la evaluación externa de procesos en proceso.');
        $this->assertNull($enProceso->fecha_fin, 'La evaluación en proceso no debe tener fecha_fin.');

        $informe = InformeEvaluacion::where('evaluacion_externa_id', $concluida->id)->first();
        $this->assertNotNull($informe, 'La evaluación concluida debe tener informe.');
        $this->assertNotEmpty($informe->resumen_ejecutivo);
        $this->assertNotEmpty($informe->metodologia);
        $this->assertNotEmpty($informe->conclusiones);
        $this->assertNotEmpty($informe->fichas);

        $hallazgos = Hallazgo::where('informe_evaluacion_id', $informe->id)->get();
        $this->assertCount(2, $hallazgos, 'El informe concluido debe tener 2 hallazgos.');
        $this->assertTrue($hallazgos->contains(fn ($h) => $h->severidad->value === 'alta'));
        $this->assertTrue($hallazgos->contains(fn ($h) => $h->severidad->value === 'media'));

        $hallazgoAlta = $hallazgos->firstWhere(fn ($h) => $h->severidad->value === 'alta');
        $this->assertNotEmpty($hallazgoAlta->evidencia_url);

        $recomendaciones = Recomendacion::whereIn('hallazgo_id', $hallazgos->pluck('id'))->get();
        $this->assertCount(3, $recomendaciones, 'Debe haber 3 recomendaciones (2 + 1).');

        // ASM vinculada a una recomendación
        $asmVinculada = Asm::whereNotNull('recomendacion_id')
            ->where('programa_presupuestario_id', $programa->id)
            ->first();
        $this->assertNotNull($asmVinculada, 'Debe existir al menos una ASM vinculada a una recomendación.');
        $this->assertTrue(
            $recomendaciones->pluck('id')->contains($asmVinculada->recomendacion_id),
            'La ASM vinculada debe apuntar a una recomendación de la cadena.'
        );

        // ASM legacy sin vínculo
        $this->assertTrue(
            Asm::whereNull('recomendacion_id')
                ->where('programa_presupuestario_id', $programa->id)
                ->exists(),
            'Debe existir al menos una ASM legacy sin recomendacion_id.'
        );

        // ASM cumplida con fecha_cumplimiento
        $this->assertTrue(
            Asm::where('programa_presupuestario_id', $programa->id)
                ->where('status', 'cumplido')
                ->whereNotNull('fecha_cumplimiento')
                ->exists(),
            'Debe existir una ASM cumplida con fecha de cumplimiento.'
        );
    }

    public function test_existe_avance_rojo_alto_con_analisis_desviacion(): void
    {
        $avance = Avance::where('semaforo_calculado', 'rojo_alto')->first();
        $this->assertNotNull($avance, 'Debe existir al menos un avance con semáforo rojo_alto.');

        $analisis = $avance->analisis_desviacion;
        $this->assertIsArray($analisis);
        foreach (['dato', 'causa', 'accion', 'proyeccion'] as $key) {
            $this->assertArrayHasKey($key, $analisis, "analisis_desviacion debe tener la clave {$key}.");
            $this->assertNotEmpty($analisis[$key]);
        }
    }

    public function test_avances_amarillo_rojo_tienen_analisis_desviacion(): void
    {
        $avances = Avance::whereIn('semaforo_calculado', ['amarillo', 'rojo'])
            ->whereNotNull('analisis_desviacion')
            ->get();

        $this->assertGreaterThan(0, $avances->count(), 'Debe haber avances amarillo/rojo con analisis_desviacion.');

        foreach ($avances as $avance) {
            $analisis = $avance->analisis_desviacion;
            $this->assertIsArray($analisis);
            foreach (['dato', 'causa', 'accion', 'proyeccion'] as $key) {
                $this->assertArrayHasKey($key, $analisis);
            }
        }
    }

    public function test_seeder_demo_es_idempotente(): void
    {
        $evalAntes = EvaluacionExterna::count();
        $informeAntes = InformeEvaluacion::count();
        $hallazgoAntes = Hallazgo::count();
        $recomAntes = Recomendacion::count();
        $asmAntes = Asm::count();
        $supuestoAntes = MirSupuesto::count();

        $this->seed(QaTestingSeeder::class);

        $this->assertSame($evalAntes, EvaluacionExterna::count());
        $this->assertSame($informeAntes, InformeEvaluacion::count());
        $this->assertSame($hallazgoAntes, Hallazgo::count());
        $this->assertSame($recomAntes, Recomendacion::count());
        $this->assertSame($asmAntes, Asm::count());
        $this->assertSame($supuestoAntes, MirSupuesto::count());
    }
}
