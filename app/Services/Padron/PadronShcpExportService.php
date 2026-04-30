<?php

namespace App\Services\Padron;

use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PadronShcpExportService
{
    public function __construct(
        private readonly GeoBaseClient $client,
    ) {}

    /**
     * Build the SHCP-format padron rows for a programa + periodo.
     * Periodo defaults to the current trimestre (YYYY-QN) when omitted.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function build(ProgramaPresupuestario $programa, ?string $periodo = null): Collection
    {
        $periodo ??= $this->trimestreActual();

        $response = $this->client->getPadronShcp($programa->id, $periodo);

        return collect($response['data'] ?? [])->values();
    }

    public function filename(ProgramaPresupuestario $programa, ?string $periodo = null): string
    {
        $periodo ??= $this->trimestreActual();

        return sprintf('padron-shcp-%s-%s.xlsx', $programa->clave, $periodo);
    }

    private function trimestreActual(): string
    {
        $now = Carbon::now();

        return sprintf('%04d-Q%d', $now->year, (int) ceil($now->month / 3));
    }
}
