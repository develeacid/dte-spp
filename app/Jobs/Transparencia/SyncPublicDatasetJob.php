<?php

namespace App\Jobs\Transparencia;

use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Transparencia\Publishing\DatasetsCatalogoPublisher;
use App\Services\Transparencia\Publishing\PublisherResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncPublicDatasetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $datasetId,
        public readonly string $action,
        public readonly ?int $userId = null,
    ) {}

    public function handle(PublisherResolver $resolver, DatasetsCatalogoPublisher $catalogo): void
    {
        $dataset = DatasetAbierto::find($this->datasetId);
        if (! $dataset) {
            Log::warning("SyncPublicDatasetJob: dataset {$this->datasetId} no encontrado, omitiendo.");

            return;
        }

        $publisher = $resolver->for($dataset->dataset_clave);

        if (! $publisher) {
            Log::warning("SyncPublicDatasetJob: clave '{$dataset->dataset_clave}' sin publisher mapeado, omitiendo (no es error).");

            return;
        }

        try {
            if ($this->action === 'publish') {
                $result = $publisher->publish($dataset);
                $this->record($dataset, true, $result['hash'], $result['count']);
            } else {
                $publisher->retire($dataset);
                $this->record($dataset, true, null, null);
            }

            $catalogo->publish($dataset);
        } catch (GeoBaseException $e) {
            $this->record($dataset, false, null, null, "GeoBase {$e->statusCode}: {$e->getMessage()}");
            Log::error("SyncPublicDatasetJob falló (GeoBase {$e->statusCode}) para {$dataset->dataset_clave}: {$e->getMessage()}");

            // Permanentes: no retry. Transitorios: re-throw para que la queue reintente.
            $permanente = in_array($e->statusCode, [400, 401, 403, 404, 422], true);
            if ($permanente) {
                return;
            }
            throw $e;
        } catch (Throwable $e) {
            $this->record($dataset, false, null, null, $e->getMessage());
            Log::error("SyncPublicDatasetJob falló para dataset {$dataset->dataset_clave}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function record(DatasetAbierto $dataset, bool $success, ?string $hash, ?int $count, ?string $error = null): void
    {
        TransparenciaPublicacion::create([
            'dataset_abierto_id' => $dataset->id,
            'dataset_clave' => $dataset->dataset_clave,
            'action' => $this->action,
            'success' => $success,
            'payload_hash' => $hash,
            'registros_count' => $count,
            'publicado_por_user_id' => $this->userId,
            'error_message' => $error,
            'publicado_at' => now(),
        ]);
    }
}
