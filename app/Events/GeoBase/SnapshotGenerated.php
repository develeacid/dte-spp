<?php

namespace App\Events\GeoBase;

use Illuminate\Foundation\Events\Dispatchable;

class SnapshotGenerated
{
    use Dispatchable;

    public function __construct(
        public readonly int $snapshotId,
        public readonly string $period,
        public readonly string $snapshotHash,
        public readonly int $componentId,
        public readonly int $programId,
        public readonly int $valorOficial,
        public readonly string $timestamp,
    ) {}
}
