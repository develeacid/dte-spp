<?php

namespace Tests\Feature\Embeddings;

use App\Jobs\Embeddings\GenerateEmbedding;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedPlan;
use App\Observers\OdsMetaObserver;
use App\Observers\OdsObjetivoObserver;
use App\Observers\PedObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmbeddingObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function registerObservers(): void
    {
        OdsObjetivo::observe(OdsObjetivoObserver::class);
        OdsMeta::observe(OdsMetaObserver::class);
        PedPlan::observe(PedObserver::class);
        PedEje::observe(PedObserver::class);
    }

    // ============================================
    // Tests de Creación
    // ============================================

    public function test_crear_ods_objetivo_despacha_job(): void
    {
        $this->registerObservers();
        Queue::fake();

        $ods = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Fin de la Pobreza',
        ]);

        Queue::assertPushed(GenerateEmbedding::class, function ($job) use ($ods) {
            return $job->modelClass === OdsObjetivo::class
                && $job->modelId === $ods->id;
        });
    }

    public function test_crear_ods_meta_despacha_job(): void
    {
        $this->registerObservers();
        Queue::fake();

        $odsObjetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);

        $meta = OdsMeta::create([
            'ods_objetivo_id' => $odsObjetivo->id,
            'clave' => '1.1',
            'descripcion' => 'Meta de prueba',
        ]);

        Queue::assertPushed(GenerateEmbedding::class, function ($job) use ($meta) {
            return $job->modelClass === OdsMeta::class
                && $job->modelId === $meta->id;
        });
    }

    public function test_crear_registro_sin_descripcion_no_despacha_job(): void
    {
        $this->registerObservers();
        Queue::fake();

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        Queue::fake();

        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => 'Eje Test',
            'descripcion' => null,
        ]);

        // Observer base usa 'descripcion', si es null no despacha
        Queue::assertNotPushed(GenerateEmbedding::class);
    }

    // ============================================
    // Tests de Actualización
    // ============================================

    public function test_actualizar_descripcion_despacha_job(): void
    {
        $this->registerObservers();
        Queue::fake();

        $odsObjetivo = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Nombre Original',
        ]);

        Queue::assertPushed(GenerateEmbedding::class, 1);

        Queue::fake();

        $odsObjetivo->update(['nombre' => 'Nombre Actualizado']);

        Queue::assertPushed(GenerateEmbedding::class, function ($job) use ($odsObjetivo) {
            return $job->modelId === $odsObjetivo->id;
        });
    }

    public function test_actualizar_otro_campo_no_despacha_job(): void
    {
        $this->registerObservers();
        Queue::fake();

        $odsObjetivo = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Nombre Test',
        ]);

        Queue::assertPushed(GenerateEmbedding::class, 1);

        Queue::fake();

        $odsObjetivo->update(['numero' => 2]);

        Queue::assertNotPushed(GenerateEmbedding::class);
    }

    // ============================================
    // Tests de Cola
    // ============================================

    public function test_job_se_despacha_a_cola_embeddings(): void
    {
        $this->registerObservers();
        Queue::fake();

        OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Test',
        ]);

        Queue::assertPushedOn('embeddings', GenerateEmbedding::class);
    }

    // ============================================
    // Tests de Observers Deshabilitados
    // ============================================

    public function test_observers_deshabilitados_no_despachan_job(): void
    {
        // No registrar observers - simula observers_enabled=false
        Queue::fake();

        OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Test',
        ]);

        Queue::assertNotPushed(GenerateEmbedding::class);
    }
}
