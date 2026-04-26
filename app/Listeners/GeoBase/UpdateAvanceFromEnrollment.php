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
        $programa = ProgramaPresupuestario::find($event->sppProgramId);

        if (! $programa) {
            return;
        }

        try {
            $coverage = $this->client->getProgramCoverage($event->sppProgramId);

            Log::info('GeoBase coverage refreshed', [
                'programa_id' => $programa->id,
                'spp_program_id' => $event->sppProgramId,
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
