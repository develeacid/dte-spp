<?php

namespace App\Listeners\Transparencia;

use App\Events\Transparencia\DatasetAbiertoPublicado;
use App\Events\Transparencia\DatasetAbiertoRetirado;
use App\Jobs\Transparencia\SyncPublicDatasetJob;

class DispatchSyncPublicTable
{
    public function handle(DatasetAbiertoPublicado|DatasetAbiertoRetirado $event): void
    {
        $action = $event instanceof DatasetAbiertoPublicado ? 'publish' : 'retire';

        $userId = $event instanceof DatasetAbiertoPublicado
            ? $event->publicador?->id
            : $event->retirador?->id;

        SyncPublicDatasetJob::dispatch(
            $event->dataset->id,
            $action,
            $userId,
        );
    }
}
