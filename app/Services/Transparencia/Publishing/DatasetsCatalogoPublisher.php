<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Transparencia\DatasetAbierto;

class DatasetsCatalogoPublisher implements PublisherInterface
{
    public function publish(DatasetAbierto $dataset): array
    {
        throw new \RuntimeException('Not implemented yet (Task 15)');
    }

    public function retire(DatasetAbierto $dataset): void
    {
        throw new \RuntimeException('Not implemented yet (Task 15)');
    }

    public function code(): string
    {
        return '(catalogo)';
    }
}
