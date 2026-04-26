<?php

namespace App\Events\GeoBase;

use Illuminate\Foundation\Events\Dispatchable;

class EnrollmentStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $enrollmentId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly int $sppProgramId,
        public readonly string $timestamp,
    ) {}
}
