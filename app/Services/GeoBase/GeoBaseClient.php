<?php

namespace App\Services\GeoBase;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GeoBaseClient
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.geobase.url'), '/');
        $this->token = config('services.geobase.token');
    }

    // --- Beneficiarios ---

    public function getBeneficiary(int $id): array
    {
        return $this->get("/beneficiaries/{$id}");
    }

    public function upsertBeneficiary(array $data): array
    {
        return $this->post('/beneficiaries', $data);
    }

    // --- Inscripciones ---

    public function createEnrollment(array $data): array
    {
        return $this->post('/enrollments', $data);
    }

    /**
     * @param  array{spp_program_id?: int, spp_mir_nivel_id?: int, status?: string, beneficiary_id?: int, per_page?: int}  $filters
     */
    public function getEnrollments(array $filters = []): array
    {
        return $this->get('/enrollments', $filters);
    }

    // --- Validación ---

    public function validateCurp(string $curp): array
    {
        return $this->post('/validation/curp', ['curp' => $curp]);
    }

    public function validateLocation(float $lat, float $lng, int $programId): array
    {
        return $this->post('/validation/location', [
            'lat' => $lat,
            'lng' => $lng,
            'program_id' => $programId,
        ]);
    }

    // --- Programas ---
    //
    // Public API speaks in spp ids. The argument is the dte-spp programa.id
    // (== programa.spp_program_id from geobase's perspective). Geobase
    // resolves the local row internally.

    public function getProgramCoverage(int $sppProgramId): array
    {
        return $this->get("/programs/{$sppProgramId}/coverage");
    }

    // --- Reportes Territoriales ---

    public function getReporte(string $reporte, array $filters = []): array
    {
        return $this->get("/reportes/{$reporte}", $filters);
    }

    public function getCoberturaMunicipal(array $filters = []): array
    {
        return $this->getReporte('cobertura-municipal', $filters);
    }

    public function getInversionMunicipal(array $filters = []): array
    {
        return $this->getReporte('inversion-municipal', $filters);
    }

    public function getInversionRegional(array $filters = []): array
    {
        return $this->getReporte('inversion-regional', $filters);
    }

    public function getTerritorialReport(int $sppProgramId, ?int $sppMirNivelId = null, ?int $municipioId = null): array
    {
        if ($sppProgramId < 1) {
            throw new \InvalidArgumentException(
                "getTerritorialReport() expects a positive spp program id, {$sppProgramId} given."
            );
        }
        if ($sppMirNivelId !== null && $sppMirNivelId < 1) {
            throw new \InvalidArgumentException(
                "getTerritorialReport() expects a positive spp mir-nivel id, {$sppMirNivelId} given."
            );
        }
        if ($municipioId !== null && $municipioId < 1) {
            throw new \InvalidArgumentException(
                "getTerritorialReport() expects a positive municipio id, {$municipioId} given."
            );
        }

        $filters = array_filter([
            'spp_program_id' => $sppProgramId,
            'spp_mir_nivel_id' => $sppMirNivelId,
            'municipio_id' => $municipioId,
        ], fn ($value) => $value !== null);

        return $this->get('/territorial-report', $filters);
    }

    // --- Imagen de polígonos ---

    public function getPolygonImage(string $tipo, int $id, string $format = 'png'): string
    {
        $response = $this->request()->get("/imagen/poligono/{$tipo}/{$id}", ['format' => $format]);

        if ($response->failed()) {
            throw new GeoBaseException(
                message: "GeoBase image error: {$response->status()}",
                statusCode: $response->status(),
            );
        }

        return $response->body();
    }

    public function getMapImage(string $tipo, int $id, int $width = 800, int $height = 600): string
    {
        $response = $this->request()
            ->timeout(30)
            ->get("/imagen/mapa/{$tipo}/{$id}", compact('width', 'height'));

        if ($response->failed()) {
            throw new GeoBaseException(
                message: "GeoBase map image error: {$response->status()}",
                statusCode: $response->status(),
            );
        }

        return $response->body();
    }

    public function getConsultaImage(array $queryConfig): string
    {
        $response = $this->request()
            ->timeout(30)
            ->post('/imagen/consulta', $queryConfig);

        if ($response->failed()) {
            throw new GeoBaseException(
                message: "GeoBase consulta image error: {$response->status()}",
                statusCode: $response->status(),
            );
        }

        return $response->body();
    }

    // --- Snapshots ---

    /**
     * @param  array{spp_program_id: int, spp_mir_nivel_id: int, period: string, cutoff_date: string}  $params
     */
    public function requestSnapshot(array $params): array
    {
        return $this->post('/snapshots/generate', $params);
    }

    /**
     * Idempotent provisioning of a program in geobase using dte-spp as the
     * source of truth. Returns 201 the first time, 200 on subsequent calls.
     */
    public function registerProgram(array $payload): array
    {
        return $this->post('/programs', $payload);
    }

    /**
     * Idempotent provisioning of a MIR component (parent program looked up
     * by spp_program_id on the geobase side).
     */
    public function registerComponent(array $payload): array
    {
        return $this->post('/components', $payload);
    }

    public function getSnapshots(int $sppProgramId, ?int $sppMirNivelId = null): array
    {
        $query = ['spp_program_id' => $sppProgramId];
        if ($sppMirNivelId !== null) {
            $query['spp_mir_nivel_id'] = $sppMirNivelId;
        }

        return $this->get('/snapshots', $query);
    }

    public function getSnapshotKpis(int $snapshotId): array
    {
        return $this->get("/snapshots/{$snapshotId}");
    }

    public function getProgramComponentKpis(int $programId, int $componentId): array
    {
        // Modo vivo deshabilitado en N3 (Opción B del design doc 2026-04-26
        // sec. 9): GeoBase no expone un endpoint Anexo 11 unificado para
        // KPIs en vivo; requeriría componer equidad-genero + densidad-etnica
        // + cobertura-componente. Habilitar en sprint posterior.
        throw new \BadMethodCallException(
            'Modo vivo deshabilitado en este sprint; usa snapshots históricos.'
        );
    }

    // --- HTTP helpers ---

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->timeout(config('services.geobase.timeout', 15))
            ->retry(
                config('services.geobase.retry_times', 3),
                config('services.geobase.retry_sleep', 500),
                throw: false,
            );
    }

    private function get(string $path, array $query = []): array
    {
        $response = $this->request()->get($path, $query);

        return $this->handleResponse($response);
    }

    private function post(string $path, array $data = []): array
    {
        $response = $this->request()->post($path, $data);

        return $this->handleResponse($response);
    }

    private function handleResponse(Response $response): array
    {
        if ($response->failed()) {
            throw new GeoBaseException(
                message: "GeoBase API error: {$response->status()} — {$response->body()}",
                statusCode: $response->status(),
                responseBody: $response->json(),
            );
        }

        return $response->json();
    }
}
