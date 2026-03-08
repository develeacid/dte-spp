<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use App\DTOs\SimilarityResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SemanticSearchService
{
    protected EmbeddingServiceInterface $embeddingService;
    protected float $defaultThreshold;
    protected int $defaultLimit;
    protected int $hnswEfSearch;

    /**
     * Modelos que tienen columna embedding y son buscables.
     */
    protected array $searchableModels = [
        \App\Models\OdsObjetivo::class,
        \App\Models\OdsMeta::class,
        \App\Models\PndEje::class,
        \App\Models\PndObjetivo::class,
        \App\Models\PndEstrategia::class,
        \App\Models\PedEje::class,
        \App\Models\PedTema::class,
        \App\Models\PedObjetivoEstrategico::class,
        \App\Models\PedEstrategia::class,
        \App\Models\PedLineaAccion::class,
        \App\Models\ProgramaDerivadoObjetivo::class,
    ];

    public function __construct(EmbeddingServiceInterface $embeddingService)
    {
        $this->embeddingService = $embeddingService;
        $this->defaultThreshold = config('embedding.similarity_threshold', 0.7);
        $this->defaultLimit = config('embedding.max_results', 5);
        $this->hnswEfSearch = config('embedding.hnsw_ef_search', 40);
    }

    /**
     * Busca registros similares al texto proporcionado.
     *
     * @param string $text Texto de búsqueda
     * @param string $modelClass Clase del modelo donde buscar
     * @param int|null $limit Número máximo de resultados
     * @param float|null $threshold Umbral mínimo de similitud (0-1)
     * @return Collection<SimilarityResult>
     */
    public function findSimilar(
        string $text,
        string $modelClass,
        ?int $limit = null,
        ?float $threshold = null
    ): Collection {
        $limit = $limit ?? $this->defaultLimit;
        $threshold = $threshold ?? $this->defaultThreshold;

        $this->validateModel($modelClass);
        $this->validateThreshold($threshold);

        try {
            // Generar embedding del texto de búsqueda
            $embedding = $this->embeddingService->generate($text);
            $embeddingString = '[' . implode(',', $embedding) . ']';

            /** @var Model $modelInstance */
            $modelInstance = new $modelClass();
            $tableName = $modelInstance->getTable();

            // Configurar ef_search para esta sesión (optimización HNSW)
            DB::statement("SET hnsw.ef_search = {$this->hnswEfSearch}");

            // Ejecutar búsqueda vectorial
            // <=> es distancia coseno: 0 = idéntico, 2 = opuesto
            // score = 1 - distancia (para que 1 sea máxima similitud)
            $results = DB::select("
                SELECT
                    *,
                    embedding <=> ?::vector AS distance,
                    1 - (embedding <=> ?::vector) AS score
                FROM {$tableName}
                WHERE embedding IS NOT NULL
                  AND 1 - (embedding <=> ?::vector) >= ?
                ORDER BY embedding <=> ?::vector
                LIMIT ?
            ", [$embeddingString, $embeddingString, $embeddingString, $threshold, $embeddingString, $limit]);

            return collect($results)->map(
                fn($result) => SimilarityResult::fromQuery($result, $modelClass)
            );

        } catch (\Exception $e) {
            Log::error('Semantic search failed', [
                'text_length' => strlen($text),
                'model_class' => $modelClass,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Busca en múltiples modelos simultáneamente.
     */
    public function findSimilarInMultiple(
        string $text,
        array $modelClasses,
        int $limitPerModel = 3,
        ?float $threshold = null
    ): Collection {
        $allResults = collect();

        foreach ($modelClasses as $modelClass) {
            $results = $this->findSimilar($text, $modelClass, $limitPerModel, $threshold);
            $allResults = $allResults->merge($results);
        }

        return $allResults->sortByDesc('score')->values();
    }

    /**
     * Busca la línea de acción del PED más similar al texto.
     */
    public function findSimilarLineaAccion(string $text, int $limit = 5, float $threshold = 0.7): Collection
    {
        return $this->findSimilar($text, \App\Models\PedLineaAccion::class, $limit, $threshold);
    }

    /**
     * Busca objetivos ODS similares al texto.
     */
    public function findSimilarOds(string $text, int $limit = 5, float $threshold = 0.7): Collection
    {
        $metas = $this->findSimilar($text, \App\Models\OdsMeta::class, $limit, $threshold);
        $objetivos = $this->findSimilar($text, \App\Models\OdsObjetivo::class, $limit, $threshold);

        return $metas->merge($objetivos)->sortByDesc('score')->take($limit)->values();
    }

    /**
     * Busca en toda la cascada de planes.
     */
    public function searchInCascade(string $text, int $totalLimit = 10, float $threshold = 0.7): Collection
    {
        return $this->findSimilarInMultiple(
            $text,
            $this->searchableModels,
            3,
            $threshold
        )->take($totalLimit);
    }

    /**
     * Retorna los modelos buscables configurados.
     */
    public function getSearchableModels(): array
    {
        return $this->searchableModels;
    }

    /**
     * Verifica si un modelo es buscable.
     */
    public function isSearchable(string $modelClass): bool
    {
        return in_array($modelClass, $this->searchableModels);
    }

    /**
     * Valida que el modelo sea buscable.
     */
    protected function validateModel(string $modelClass): void
    {
        if (!in_array($modelClass, $this->searchableModels)) {
            throw new \InvalidArgumentException("Model {$modelClass} is not searchable");
        }
    }

    /**
     * Valida el umbral de similitud.
     */
    protected function validateThreshold(float $threshold): void
    {
        if ($threshold < 0 || $threshold > 1) {
            throw new \InvalidArgumentException('Threshold must be between 0 and 1');
        }
    }
}
