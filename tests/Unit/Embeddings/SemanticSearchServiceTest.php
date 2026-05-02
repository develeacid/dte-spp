<?php

namespace Tests\Unit\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use App\DTOs\SimilarityResult;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedLineaAccion;
use App\Models\User;
use App\Services\Embeddings\SemanticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class SemanticSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'embedding.similarity_threshold' => 0.7,
            'embedding.max_results' => 5,
            'embedding.hnsw_ef_search' => 40,
        ]);
    }

    // ============================================
    // Tests de Búsqueda Básica
    // ============================================

    public function test_find_similar_retorna_resultados_ordenados_por_score(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $ods = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);

        $meta1 = OdsMeta::create([
            'ods_objetivo_id' => $ods->id,
            'clave' => '1.1',
            'descripcion' => 'Reducir la pobreza extrema',
        ]);

        $meta2 = OdsMeta::create([
            'ods_objetivo_id' => $ods->id,
            'clave' => '1.2',
            'descripcion' => 'Mejorar la educación',
        ]);

        // Insertar embeddings simulados
        DB::statement("UPDATE ods_metas SET embedding = '[".implode(',', array_fill(0, 1536, 0.5))."]'::vector WHERE id = ?", [$meta1->id]);
        DB::statement("UPDATE ods_metas SET embedding = '[".implode(',', array_fill(0, 1536, 0.4))."]'::vector WHERE id = ?", [$meta2->id]);

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('reducir pobreza', OdsMeta::class);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(SimilarityResult::class, $results->first());
        $this->assertGreaterThanOrEqual($results->last()->score, $results->first()->score);
    }

    public function test_find_similar_respeta_limite(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $objetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);

        $embeddingVector = '['.implode(',', array_fill(0, 1536, 0.5)).']';
        for ($i = 1; $i <= 10; $i++) {
            $meta = OdsMeta::create([
                'ods_objetivo_id' => $objetivo->id,
                'clave' => "1.{$i}",
                'descripcion' => "Meta {$i}",
            ]);
            DB::statement("UPDATE ods_metas SET embedding = '{$embeddingVector}'::vector WHERE id = ?", [$meta->id]);
        }

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('test', OdsMeta::class, limit: 3);

        $this->assertCount(3, $results);
    }

    public function test_find_similar_filtra_por_umbral(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $objetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);

        $meta1 = OdsMeta::create([
            'ods_objetivo_id' => $objetivo->id,
            'clave' => '1.1',
            'descripcion' => 'Alta similitud',
        ]);
        DB::statement("UPDATE ods_metas SET embedding = '[".implode(',', array_fill(0, 1536, 0.5))."]'::vector WHERE id = ?", [$meta1->id]);

        $meta2 = OdsMeta::create([
            'ods_objetivo_id' => $objetivo->id,
            'clave' => '1.2',
            'descripcion' => 'Baja similitud',
        ]);
        DB::statement("UPDATE ods_metas SET embedding = '[".implode(',', array_fill(0, 1536, -0.9))."]'::vector WHERE id = ?", [$meta2->id]);

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('test', OdsMeta::class, threshold: 0.9);

        $this->assertLessThanOrEqual(1, $results->count());
    }

    // ============================================
    // Tests de Validación
    // ============================================

    public function test_lanza_excepcion_si_modelo_no_es_buscable(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $service = new SemanticSearchService($mockEmbedding);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not searchable');

        $service->findSimilar('test', User::class);
    }

    public function test_lanza_excepcion_si_threshold_invalido(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $service = new SemanticSearchService($mockEmbedding);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Threshold must be between 0 and 1');

        $service->findSimilar('test', OdsMeta::class, threshold: 1.5);
    }

    // ============================================
    // Tests de DTO
    // ============================================

    public function test_dto_contiene_propiedades_correctas(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $objetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);
        $meta = OdsMeta::create([
            'ods_objetivo_id' => $objetivo->id,
            'clave' => '1.1',
            'descripcion' => 'Test descripcion',
        ]);
        DB::statement("UPDATE ods_metas SET embedding = '[".implode(',', array_fill(0, 1536, 0.5))."]'::vector WHERE id = ?", [$meta->id]);

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('test', OdsMeta::class);

        $first = $results->first();

        $this->assertInstanceOf(OdsMeta::class, $first->model);
        $this->assertIsFloat($first->score);
        $this->assertIsFloat($first->distance);
        $this->assertGreaterThanOrEqual(0, $first->score);
        $this->assertLessThanOrEqual(1, $first->score);
    }

    public function test_dto_get_percentage_attribute(): void
    {
        $result = new SimilarityResult(
            model: new OdsMeta,
            score: 0.856,
            distance: 0.144
        );

        $this->assertEquals('85.6%', $result->getPercentageAttribute());
    }

    public function test_dto_is_high_quality(): void
    {
        $highQuality = new SimilarityResult(
            model: new OdsMeta,
            score: 0.90,
            distance: 0.10
        );

        $lowQuality = new SimilarityResult(
            model: new OdsMeta,
            score: 0.80,
            distance: 0.20
        );

        $this->assertTrue($highQuality->isHighQuality());
        $this->assertFalse($lowQuality->isHighQuality());
        $this->assertTrue($lowQuality->isHighQuality(0.75));
    }

    // ============================================
    // Tests de Manejo de Errores
    // ============================================

    public function test_retorna_coleccion_vacia_si_embedding_service_falla(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('test', OdsMeta::class);

        $this->assertTrue($results->isEmpty());
    }

    // ============================================
    // Tests de Utilidades
    // ============================================

    public function test_get_searchable_models(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $service = new SemanticSearchService($mockEmbedding);

        $models = $service->getSearchableModels();

        $this->assertIsArray($models);
        $this->assertContains(OdsMeta::class, $models);
        $this->assertContains(PedLineaAccion::class, $models);
    }

    public function test_is_searchable(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $service = new SemanticSearchService($mockEmbedding);

        $this->assertTrue($service->isSearchable(OdsMeta::class));
        $this->assertFalse($service->isSearchable(User::class));
    }
}
