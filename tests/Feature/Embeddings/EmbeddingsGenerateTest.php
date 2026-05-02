<?php

namespace Tests\Feature\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedPlan;
use App\Models\PndEje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EmbeddingsGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeEmbedding(int $dimension = 1536): array
    {
        return array_fill(0, $dimension, 0.01);
    }

    protected function mockEmbeddingService(?array $embedding = null): EmbeddingServiceInterface
    {
        $embedding = $embedding ?? $this->fakeEmbedding();

        $mock = Mockery::mock(EmbeddingServiceInterface::class);
        $mock->shouldReceive('generate')
            ->andReturn($embedding);
        $mock->shouldReceive('generateBatch')
            ->andReturn([$embedding]);
        $mock->shouldReceive('getDimension')
            ->andReturn(1536);
        $mock->shouldReceive('getModel')
            ->andReturn('text-embedding-ada-002');

        $this->app->instance(EmbeddingServiceInterface::class, $mock);

        return $mock;
    }

    // ============================================
    // Test 1: Processes records with null embeddings
    // ============================================

    public function test_command_processes_records_with_null_embeddings(): void
    {
        $mock = Mockery::mock(EmbeddingServiceInterface::class);
        $mock->shouldReceive('generate')
            ->times(2)
            ->andReturn($this->fakeEmbedding());

        $this->app->instance(EmbeddingServiceInterface::class, $mock);

        OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Fin de la Pobreza',
            'descripcion' => 'Poner fin a la pobreza en todas sus formas',
        ]);
        OdsObjetivo::create([
            'numero' => 2,
            'nombre' => 'Hambre Cero',
            'descripcion' => 'Poner fin al hambre',
        ]);

        $this->artisan('app:embeddings-generate', ['--table' => 'ods_objetivos'])
            ->assertSuccessful()
            ->expectsOutputToContain('Generated')
            ->expectsOutputToContain('2');
    }

    // ============================================
    // Test 2: --force flag regenerates all embeddings
    // ============================================

    public function test_force_flag_regenerates_all_embeddings(): void
    {
        $mock = Mockery::mock(EmbeddingServiceInterface::class);
        $mock->shouldReceive('generate')
            ->times(2)
            ->andReturn($this->fakeEmbedding());

        $this->app->instance(EmbeddingServiceInterface::class, $mock);

        // Create records (normally they'd have null embeddings in test)
        OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Fin de la Pobreza',
        ]);
        OdsObjetivo::create([
            'numero' => 2,
            'nombre' => 'Hambre Cero',
        ]);

        $this->artisan('app:embeddings-generate', [
            '--table' => 'ods_objetivos',
            '--force' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Generated')
            ->expectsOutputToContain('2');
    }

    // ============================================
    // Test 3: --table flag limits to specific table
    // ============================================

    public function test_table_flag_limits_processing_to_specific_table(): void
    {
        $mock = Mockery::mock(EmbeddingServiceInterface::class);
        // Should only be called once for the single PND eje
        $mock->shouldReceive('generate')
            ->times(1)
            ->andReturn($this->fakeEmbedding());

        $this->app->instance(EmbeddingServiceInterface::class, $mock);

        // Create records in different tables
        OdsObjetivo::create(['numero' => 1, 'nombre' => 'ODS Test']);
        PndEje::create(['numero' => 1, 'nombre' => 'PND Eje Test']);

        // Only process pnd_ejes
        $this->artisan('app:embeddings-generate', ['--table' => 'pnd_ejes'])
            ->assertSuccessful()
            ->expectsOutputToContain('pnd_ejes');
    }

    // ============================================
    // Test 4: Invalid table returns failure
    // ============================================

    public function test_invalid_table_returns_failure(): void
    {
        $this->mockEmbeddingService();

        $this->artisan('app:embeddings-generate', ['--table' => 'nonexistent_table'])
            ->assertFailed()
            ->expectsOutputToContain('not a valid embeddable table');
    }

    // ============================================
    // Test 5: Retry logic on API failure
    // ============================================

    public function test_retry_logic_on_api_failure(): void
    {
        // Set fast backoff for testing
        config(['embedding.batch.max_retries' => 3]);
        config(['embedding.batch.backoff_base' => 0]);

        $mock = Mockery::mock(EmbeddingServiceInterface::class);
        $mock->shouldReceive('generate')
            ->times(3) // 3 retries
            ->andThrow(new \RuntimeException('API Error'));

        $this->app->instance(EmbeddingServiceInterface::class, $mock);

        OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);

        $this->artisan('app:embeddings-generate', ['--table' => 'ods_objetivos'])
            ->assertFailed()
            ->expectsOutputToContain('Failed')
            ->expectsOutputToContain('1');
    }

    // ============================================
    // Test 6: Summary report output
    // ============================================

    public function test_summary_report_is_displayed(): void
    {
        $this->mockEmbeddingService();

        OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test 1']);

        $this->artisan('app:embeddings-generate', ['--table' => 'ods_objetivos'])
            ->assertSuccessful()
            ->expectsOutputToContain('Embedding Generation Summary')
            ->expectsOutputToContain('Generated')
            ->expectsOutputToContain('Failed')
            ->expectsOutputToContain('Skipped');
    }

    // ============================================
    // Test 7: Skips records with empty text
    // ============================================

    public function test_skips_records_with_empty_embeddable_text(): void
    {
        $mock = Mockery::mock(EmbeddingServiceInterface::class);
        // Should NOT be called for empty text records
        $mock->shouldReceive('generate')->never();

        $this->app->instance(EmbeddingServiceInterface::class, $mock);

        // PedEje with no nombre and no descripcion
        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);
        PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => '',
            'descripcion' => '',
        ]);

        $this->artisan('app:embeddings-generate', ['--table' => 'ped_ejes'])
            ->assertSuccessful();
    }

    // ============================================
    // Test 8: HasEmbedding trait scopes
    // ============================================

    public function test_has_embedding_trait_needs_embedding_scope(): void
    {
        // All records in test DB will have NULL embedding
        OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test 1']);
        OdsObjetivo::create(['numero' => 2, 'nombre' => 'Test 2']);

        $needsEmbedding = OdsObjetivo::needsEmbedding()->count();
        $this->assertEquals(2, $needsEmbedding);
    }

    // ============================================
    // Test 9: HasEmbedding trait getEmbeddableText
    // ============================================

    public function test_get_embeddable_text_returns_combined_fields(): void
    {
        $ods = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Fin de la Pobreza',
            'descripcion' => 'Poner fin a la pobreza',
        ]);

        $text = $ods->getEmbeddableText();
        $this->assertStringContainsString('Fin de la Pobreza', $text);
        $this->assertStringContainsString('Poner fin a la pobreza', $text);
    }

    // ============================================
    // Test 10: chunk-size option controls batch size
    // ============================================

    public function test_chunk_size_controls_batch_processing(): void
    {
        $mock = Mockery::mock(EmbeddingServiceInterface::class);
        $mock->shouldReceive('generate')
            ->times(3)
            ->andReturn($this->fakeEmbedding());

        $this->app->instance(EmbeddingServiceInterface::class, $mock);

        OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test 1']);
        OdsObjetivo::create(['numero' => 2, 'nombre' => 'Test 2']);
        OdsObjetivo::create(['numero' => 3, 'nombre' => 'Test 3']);

        // Chunk size of 2 means 2 chunks: (2 records) + (1 record)
        $this->artisan('app:embeddings-generate', [
            '--table' => 'ods_objetivos',
            '--chunk-size' => 2,
            '--delay' => 0,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('3');
    }
}
