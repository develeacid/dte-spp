<?php

namespace App\Events\GeoBase;

use Illuminate\Foundation\Events\Dispatchable;

class SyncProcessed
{
    use Dispatchable;

    public function __construct(
        public readonly int $entryId,
        public readonly string $operation,
        public readonly ?string $resultType,
        public readonly ?int $resultId,
        public readonly string $timestamp,
    ) {}
}
