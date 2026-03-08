<?php

namespace App\Jobs\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos antes de fallar.
     */
    public int $tries;

    /**
     * Backoff exponencial entre reintentos (en segundos).
     */
    public array $backoff;

    /**
     * Tiempo máximo de ejecución del job.
     */
    public int $timeout;

    /**
     * Modelo para el que se generará el embedding.
     */
    public string $modelClass;

    /**
     * ID del modelo.
     */
    public int $modelId;

    /**
     * Texto para generar embedding.
     */
    public string $text;

    /**
     * Columna donde guardar el embedding.
     */
    public string $embeddingColumn;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $modelClass,
        int $modelId,
        string $text,
        string $embeddingColumn = 'embedding'
    ) {
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->text = $text;
        $this->embeddingColumn = $embeddingColumn;

        // Configuración desde config
        $this->tries = config('embedding.tries', 3);
        $this->backoff = config('embedding.backoff', [10, 60, 300]);
        $this->timeout = config('embedding.timeout_job', 60);

        // Usar cola específica para embeddings
        $this->onQueue(config('embedding.queue', 'embeddings'));
    }

    /**
     * Execute the job.
     */
    public function handle(EmbeddingServiceInterface $embeddingService): void
    {
        // Verificar que el modelo existe
        $model = $this->modelClass::find($this->modelId);

        if (!$model) {
            Log::warning('Model not found for embedding generation', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
            ]);
            return;
        }

        try {
            // Generar embedding
            $embedding = $embeddingService->generate($this->text);

            // Guardar en la base de datos
            $this->saveEmbedding($model, $embedding);

            Log::info('Embedding generated successfully', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
                'dimension' => count($embedding),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate embedding', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Guarda el embedding en la base de datos.
     */
    protected function saveEmbedding($model, array $embedding): void
    {
        $tableName = $model->getTable();
        $embeddingString = '[' . implode(',', $embedding) . ']';

        // Usar raw SQL porque Eloquent no soporta nativamente columnas vectoriales
        DB::statement(
            "UPDATE {$tableName} SET {$this->embeddingColumn} = ?::vector WHERE id = ?",
            [$embeddingString, $this->modelId]
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Embedding job failed permanently', [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'embedding',
            "model:{$this->modelClass}",
            "id:{$this->modelId}",
        ];
    }

    /**
     * Determina el tiempo de espera antes del próximo intento.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }
}
