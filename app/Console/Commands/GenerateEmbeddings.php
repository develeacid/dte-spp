<?php

namespace App\Console\Commands;

use App\Contracts\EmbeddingServiceInterface;
use App\Models\LlmLog;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedTema;
use App\Models\PndEje;
use App\Models\PndEstrategia;
use App\Models\PndObjetivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:embeddings-generate
        {--chunk-size=50 : Number of records to process per chunk}
        {--delay=1000 : Delay in milliseconds between chunks}
        {--force : Regenerate all embeddings, even existing ones}
        {--table= : Process only a specific table}';

    /**
     * The console command description.
     */
    protected $description = 'Generate or regenerate embeddings for PED, ODS, and PND tables in batch';

    /**
     * Tables to process in priority order.
     */
    protected array $tableMap = [
        // PED
        'ped_ejes' => PedEje::class,
        'ped_temas' => PedTema::class,
        'ped_objetivos_estrategicos' => PedObjetivoEstrategico::class,
        'ped_estrategias' => PedEstrategia::class,
        'ped_lineas_accion' => PedLineaAccion::class,
        // ODS
        'ods_objetivos' => OdsObjetivo::class,
        'ods_metas' => OdsMeta::class,
        // PND
        'pnd_ejes' => PndEje::class,
        'pnd_estrategias' => PndEstrategia::class,
        'pnd_objetivos' => PndObjetivo::class,
    ];

    /**
     * Stats tracking.
     */
    protected int $generated = 0;

    protected int $failed = 0;

    protected int $skipped = 0;

    /**
     * Execute the console command.
     */
    public function handle(EmbeddingServiceInterface $embeddingService): int
    {
        $chunkSize = (int) $this->option('chunk-size');
        $delayMs = (int) $this->option('delay');
        $force = (bool) $this->option('force');
        $tableFilter = $this->option('table');
        $maxRetries = (int) config('embedding.batch.max_retries', 3);
        $backoffBase = (int) config('embedding.batch.backoff_base', 1);

        $tablesToProcess = $this->resolveTables($tableFilter);

        if (empty($tablesToProcess)) {
            $this->error("Table '{$tableFilter}' is not a valid embeddable table.");
            $this->line('Valid tables: '.implode(', ', array_keys($this->tableMap)));

            return self::FAILURE;
        }

        $this->info('Starting embedding generation...');
        $this->info("Options: chunk-size={$chunkSize}, delay={$delayMs}ms, force=".($force ? 'yes' : 'no'));
        $this->newLine();

        foreach ($tablesToProcess as $tableName => $modelClass) {
            $this->processTable(
                $tableName,
                $modelClass,
                $embeddingService,
                $chunkSize,
                $delayMs,
                $force,
                $maxRetries,
                $backoffBase
            );
        }

        $this->printSummary();

        return ($this->failed > 0) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Resolve which tables to process.
     */
    protected function resolveTables(?string $tableFilter): array
    {
        if ($tableFilter === null) {
            return $this->tableMap;
        }

        if (isset($this->tableMap[$tableFilter])) {
            return [$tableFilter => $this->tableMap[$tableFilter]];
        }

        return [];
    }

    /**
     * Process a single table.
     */
    protected function processTable(
        string $tableName,
        string $modelClass,
        EmbeddingServiceInterface $embeddingService,
        int $chunkSize,
        int $delayMs,
        bool $force,
        int $maxRetries,
        int $backoffBase
    ): void {
        $query = $modelClass::query();

        if (! $force) {
            $query->needsEmbedding();
        }

        $total = $query->count();

        if ($total === 0) {
            $this->line("  [{$tableName}] No records to process.");

            return;
        }

        $this->info("  [{$tableName}] Processing {$total} records...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $isFirstChunk = true;

        $modelClass::query()
            ->when(! $force, fn ($q) => $q->needsEmbedding())
            ->chunkById($chunkSize, function ($records) use (
                $embeddingService,
                $tableName,
                $delayMs,
                $maxRetries,
                $backoffBase,
                $bar,
                &$isFirstChunk
            ) {
                // Rate limiting: sleep between chunks (not before the first)
                if (! $isFirstChunk && $delayMs > 0) {
                    usleep($delayMs * 1000);
                }
                $isFirstChunk = false;

                foreach ($records as $record) {
                    $text = $record->getEmbeddableText();

                    if (empty(trim($text))) {
                        $this->skipped++;
                        $bar->advance();

                        continue;
                    }

                    $this->processRecord(
                        $record,
                        $text,
                        $tableName,
                        $embeddingService,
                        $maxRetries,
                        $backoffBase
                    );

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
    }

    /**
     * Process a single record with retry logic.
     */
    protected function processRecord(
        $record,
        string $text,
        string $tableName,
        EmbeddingServiceInterface $embeddingService,
        int $maxRetries,
        int $backoffBase
    ): void {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $embedding = $embeddingService->generate($text);

                $this->saveEmbedding($record, $embedding);

                $this->logSuccess($tableName, $record->id, $text, count($embedding));

                $this->generated++;

                return;

            } catch (\Exception $e) {
                $attempt++;
                $waitSeconds = $backoffBase * pow(2, $attempt - 1);

                Log::warning("Embedding generation failed for {$tableName}#{$record->id}", [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'wait_seconds' => $waitSeconds,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxRetries) {
                    sleep($waitSeconds);
                }
            }
        }

        // All retries exhausted
        $this->failed++;
        Log::error("Embedding generation permanently failed for {$tableName}#{$record->id}");
    }

    /**
     * Save embedding to database using raw SQL (vector column).
     */
    protected function saveEmbedding($model, array $embedding): void
    {
        $tableName = $model->getTable();
        $embeddingString = '['.implode(',', $embedding).']';

        DB::statement(
            "UPDATE {$tableName} SET embedding = ?::vector WHERE id = ?",
            [$embeddingString, $model->id]
        );
    }

    /**
     * Log a successful embedding generation.
     */
    protected function logSuccess(string $tableName, int $modelId, string $text, int $dimension): void
    {
        LlmLog::create([
            'user_id' => null,
            'method' => 'embedding',
            'prompt_template' => "batch:{$tableName}",
            'prompt_text' => mb_substr($text, 0, 500),
            'response_text' => "dim={$dimension}",
            'model' => config('embedding.model'),
            'status' => 'success',
            'duration_ms' => 0,
        ]);
    }

    /**
     * Print final summary.
     */
    protected function printSummary(): void
    {
        $this->newLine();
        $this->info('=== Embedding Generation Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Generated', $this->generated],
                ['Failed', $this->failed],
                ['Skipped (empty text)', $this->skipped],
                ['Total processed', $this->generated + $this->failed + $this->skipped],
            ]
        );

        if ($this->failed > 0) {
            $this->warn('Some records failed. Check logs for details.');
        } else {
            $this->info('All records processed successfully.');
        }
    }
}
