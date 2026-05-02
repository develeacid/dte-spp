<?php

namespace Tests\Unit\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use App\Jobs\Embeddings\GenerateEmbedding;
use App\Models\OdsObjetivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class GenerateEmbeddingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_guarda_embedding_correctamente(): void
    {
        $mockService = Mockery::mock(EmbeddingServiceInterface::class);
        $mockService->shouldReceive('generate')
            ->once()
            ->with('Texto de prueba')
            ->andReturn(array_fill(0, 1536, 0.5));

        $this->app->instance(EmbeddingServiceInterface::class, $mockService);

        $odsObjetivo = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Test',
        ]);

        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            $odsObjetivo->id,
            'Texto de prueba'
        );

        $job->handle($mockService);

        $this->assertDatabaseHas('ods_objetivos', [
            'id' => $odsObjetivo->id,
        ]);

        $result = DB::selectOne(
            'SELECT embedding FROM ods_objetivos WHERE id = ?',
            [$odsObjetivo->id]
        );

        $this->assertNotNull($result->embedding);
    }

    public function test_job_no_falla_si_modelo_no_existe(): void
    {
        Log::shouldReceive('warning')->once();

        $mockService = Mockery::mock(EmbeddingServiceInterface::class);
        $mockService->shouldNotReceive('generate');

        $this->app->instance(EmbeddingServiceInterface::class, $mockService);

        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            9999,
            'Texto de prueba'
        );

        $job->handle($mockService);

        $this->assertTrue(true);
    }

    public function test_job_tiene_tries_configurado(): void
    {
        config(['embedding.tries' => 5]);

        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            1,
            'Test'
        );

        $this->assertEquals(5, $job->tries);
    }

    public function test_job_tiene_backoff_configurado(): void
    {
        config(['embedding.backoff' => [15, 120, 600]]);

        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            1,
            'Test'
        );

        $this->assertEquals([15, 120, 600], $job->backoff);
    }

    public function test_job_tiene_tags_correctos(): void
    {
        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            123,
            'Test'
        );

        $tags = $job->tags();

        $this->assertContains('embedding', $tags);
        $this->assertContains('model:'.OdsObjetivo::class, $tags);
        $this->assertContains('id:123', $tags);
    }

    public function test_job_usa_cola_correcta(): void
    {
        config(['embedding.queue' => 'custom-embeddings']);

        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            1,
            'Test'
        );

        $this->assertEquals('custom-embeddings', $job->queue);
    }

    public function test_job_failed_registra_error(): void
    {
        Log::shouldReceive('error')->once();

        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            1,
            'Test'
        );

        $job->failed(new \Exception('Test error'));

        // No debe lanzar excepción
        $this->assertTrue(true);
    }
}
