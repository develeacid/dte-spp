<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Transparencia\DatasetAbierto;

class ProgramasPublisher implements PublisherInterface
{
    public function publish(DatasetAbierto $dataset): array
    {
        throw new \RuntimeException('Not implemented yet (Task 9)');
    }

    public function retire(DatasetAbierto $dataset): void
    {
        throw new \RuntimeException('Not implemented yet (Task 9)');
    }

    public function code(): string
    {
        return 'DS-01';
    }
}
