<?php

namespace Tests\Unit\Embeddings;

use App\Services\Embeddings\EmbeddingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'embedding.api_key' => 'test-api-key',
            'embedding.api_url' => 'https://api.test.com/v1/embeddings',
            'embedding.model' => 'test-model',
            'embedding.dimension' => 1536,
            'embedding.timeout' => 30,
            'embedding.max_tokens' => 8000,
        ]);
    }

    public function test_genera_embedding_correctamente(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.1)],
                ],
            ], 200),
        ]);

        $service = new EmbeddingService;
        $embedding = $service->generate('Texto de prueba');

        $this->assertCount(1536, $embedding);
        $this->assertEquals(0.1, $embedding[0]);
    }

    public function test_lanza_excepcion_si_texto_vacio(): void
    {
        $service = new EmbeddingService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede estar vacío');

        $service->generate('');
    }

    public function test_lanza_excepcion_si_texto_solo_espacios(): void
    {
        $service = new EmbeddingService;

        $this->expectException(\InvalidArgumentException::class);

        $service->generate('   ');
    }

    public function test_lanza_excepcion_si_api_falla(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'error' => ['message' => 'Invalid API key'],
            ], 401),
        ]);

        $service = new EmbeddingService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Embedding API error');

        $service->generate('Texto de prueba');
    }

    public function test_lanza_excepcion_si_respuesta_dimension_incorrecta(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => [0.1, 0.2]], // Solo 2 elementos
                ],
            ], 200),
        ]);

        $service = new EmbeddingService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid embedding dimension');

        $service->generate('Texto de prueba');
    }

    public function test_lanza_excepcion_si_respuesta_no_es_array(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => 'invalid'],
                ],
            ], 200),
        ]);

        $service = new EmbeddingService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not an array');

        $service->generate('Texto de prueba');
    }

    public function test_trunca_texto_largo(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.5)],
                ],
            ], 200),
        ]);

        $service = new EmbeddingService;
        $textoLargo = str_repeat('a', 50000);

        $embedding = $service->generate($textoLargo);

        $this->assertCount(1536, $embedding);

        // Verificar que se truncó antes de enviar
        Http::assertSent(function ($request) {
            $input = $request->data()['input'];

            return strlen($input) <= 32000; // 8000 * 4
        });
    }

    public function test_get_dimension(): void
    {
        $service = new EmbeddingService;

        $this->assertEquals(1536, $service->getDimension());
    }

    public function test_get_model(): void
    {
        $service = new EmbeddingService;

        $this->assertEquals('test-model', $service->getModel());
    }

    public function test_connection_error_generates_log(): void
    {
        Http::fake([
            'api.test.com/*' => Http::failedConnection(),
        ]);

        $service = new EmbeddingService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection error');

        $service->generate('Texto de prueba');
    }
}
