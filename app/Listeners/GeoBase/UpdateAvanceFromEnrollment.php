<?php

namespace App\Listeners\GeoBase;

use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Support\Facades\Log;

class UpdateAvanceFromEnrollment
{
    public function __construct(
        private GeoBaseClient $client,
    ) {}

    public function handle(EnrollmentStatusChanged $event): void
    {
        $programa = ProgramaPresupuestario::where('geobase_program_id', $event->programId)->first();

        if (! $programa) {
            return;
        }

        try {
            $coverage = $this->client->getProgramCoverage($event->programId);

            Log::info('GeoBase coverage refreshed', [
                'programa_id' => $programa->id,
                'geobase_program_id' => $event->programId,
                'coverage' => $coverage['data'] ?? [],
            ]);
        } catch (GeoBaseException $e) {
            Log::warning('Failed to refresh GeoBase coverage', [
                'programa_id' => $programa->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
