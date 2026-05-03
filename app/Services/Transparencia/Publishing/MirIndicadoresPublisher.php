<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Transparencia\DatasetAbierto;

class MirIndicadoresPublisher implements PublisherInterface
{
    public function publish(DatasetAbierto $dataset): array
    {
        throw new \RuntimeException('Not implemented yet (Task 10)');
    }

    public function retire(DatasetAbierto $dataset): void
    {
        throw new \RuntimeException('Not implemented yet (Task 10)');
    }

    public function code(): string
    {
        return 'DS-02';
    }
}
