<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class EmbeddingService implements EmbeddingServiceInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected int $dimension;
    protected int $timeout;
    protected int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('embedding.api_key');
        $this->apiUrl = config('embedding.api_url');
        $this->model = config('embedding.model');
        $this->dimension = config('embedding.dimension');
        $this->timeout = config('embedding.timeout', 30);
        $this->maxTokens = config('embedding.max_tokens', 8000);
    }

    /**
     * Genera un embedding vectorial para el texto proporcionado.
     */
    public function generate(string $text): array
    {
        $this->validateInput($text);

        $text = $this->truncateText($text);

        return $this->callApi($text);
    }

    /**
     * Genera embeddings para múltiples textos en una sola llamada al API.
     */
    public function generateBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $cleanTexts = array_map(fn (string $text) => $this->truncateText($text), $texts);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->apiUrl, [
                'model' => $this->model,
                'input' => array_values($cleanTexts),
            ]);

            if (!$response->successful()) {
                $error = $response->json('error.message', 'Error desconocido');
                $statusCode = $response->status();

                Log::error('Embedding batch API error', [
                    'status' => $statusCode,
                    'error' => $error,
                    'count' => count($texts),
                ]);

                throw new \RuntimeException("Embedding API error ({$statusCode}): {$error}");
            }

            $data = $response->json('data', []);

            // Sort by index to ensure correct order
            usort($data, fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

            return array_map(fn ($item) => $item['embedding'], $data);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Embedding batch API connection error', [
                'message' => $e->getMessage(),
                'count' => count($texts),
            ]);

            throw new \RuntimeException('Connection error to Embedding API');
        }
    }

    /**
     * Obtiene la dimensión del embedding.
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    /**
     * Obtiene el modelo configurado.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Valida el texto de entrada.
     */
    protected function validateInput(string $text): void
    {
        if (empty(trim($text))) {
            throw new \InvalidArgumentException('El texto no puede estar vacío');
        }
    }

    /**
     * Trunca el texto si excede el límite de tokens.
     */
    protected function truncateText(string $text): string
    {
        $maxLength = $this->maxTokens * 4; // Aproximación: 4 chars = 1 token

        if (strlen($text) > $maxLength) {
            Log::info('Text truncated for embedding generation', [
                'original_length' => strlen($text),
                'truncated_length' => $maxLength,
            ]);

            return substr($text, 0, $maxLength);
        }

        return $text;
    }

    /**
     * Realiza la llamada al API de embeddings.
     */
    protected function callApi(string $text): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->apiUrl, [
                'model' => $this->model,
                'input' => $text,
            ]);

            return $this->parseResponse($response);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Embedding API connection error', [
                'message' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);

            throw new \RuntimeException('Connection error to Embedding API');
        }
    }

    /**
     * Parsea la respuesta del API.
     */
    protected function parseResponse($response): array
    {
        if (!$response->successful()) {
            $error = $response->json('error.message', 'Error desconocido');
            $statusCode = $response->status();

            Log::error('Embedding API error', [
                'status' => $statusCode,
                'error' => $error,
            ]);

            throw new \RuntimeException("Embedding API error ({$statusCode}): {$error}");
        }

        $embedding = $response->json('data.0.embedding');

        $this->validateEmbedding($embedding);

        return $embedding;
    }

    /**
     * Valida la respuesta del embedding.
     */
    protected function validateEmbedding(mixed $embedding): void
    {
        if (!is_array($embedding)) {
            throw new \RuntimeException('Invalid embedding response: not an array');
        }

        if (count($embedding) !== $this->dimension) {
            throw new \RuntimeException(
                "Invalid embedding dimension: expected {$this->dimension}, got " . count($embedding)
            );
        }

        // Verificar que todos los elementos son numéricos
        foreach ($embedding as $i => $value) {
            if (!is_float($value) && !is_int($value)) {
                throw new \RuntimeException("Invalid embedding value at index {$i}");
            }
        }
    }
}
