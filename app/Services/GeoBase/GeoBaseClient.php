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
