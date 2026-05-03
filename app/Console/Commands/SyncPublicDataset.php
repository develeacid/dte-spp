<?php

namespace App\Console\Commands;

use App\Enums\EstadoDatasetAbierto;
use App\Jobs\Transparencia\SyncPublicDatasetJob;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\PublisherResolver;
use Illuminate\Console\Command;

class SyncPublicDataset extends Command
{
    protected $signature = 'transparencia:sync-public {clave : Clave DS del dataset (e.g., DS-01)}';

    protected $description = 'Sincroniza manualmente la tabla pub_* del dataset dado. Útil para recovery.';

    public function handle(PublisherResolver $resolver): int
    {
        $clave = $this->argument('clave');

        if (! $resolver->for($clave)) {
            $this->error("Clave '{$clave}' sin publisher mapeado. Soportadas: ".implode(', ', $resolver->supportedCodes()));

            return self::FAILURE;
        }

        $dataset = DatasetAbierto::where('dataset_clave', $clave)
            ->where('status', EstadoDatasetAbierto::PUBLICADO)
            ->latest('id')
            ->first();

        if (! $dataset) {
            $this->error("Dataset '{$clave}' en estado PUBLICADO no encontrado.");

            return self::FAILURE;
        }

        $this->info("Sincronizando {$clave}...");
        SyncPublicDatasetJob::dispatchSync($dataset->id, 'publish', null);
        $this->info('Sync completado.');

        return self::SUCCESS;
    }
}
