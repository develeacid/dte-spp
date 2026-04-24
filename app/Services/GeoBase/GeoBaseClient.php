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

    public function getProgramCoverage(int $programId): array
    {
        return $this->get("/programs/{$programId}/coverage");
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

    public function getTerritorialReport(int $programId, ?int $componentId = null, ?int $municipioId = null): array
    {
        $filters = array_filter([
            'program_id' => $programId,
            'component_id' => $componentId,
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

    public function requestSnapshot(array $params): array
    {
        return $this->post('/snapshot', $params);
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
