<?php

namespace Tests\Unit\Llm;

use App\Contracts\LlmServiceInterface;
use App\DTOs\LlmValidationResult;
use App\Models\LlmLog;
use App\Services\Llm\LlmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmServiceTest extends TestCase
{
    use RefreshDatabase;

    private LlmService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['llm.api_key' => 'test-key']);
        config(['llm.api_url' => 'https://api.openai.com/v1/chat/completions']);
        config(['llm.model' => 'gpt-4o-mini']);
        config(['llm.logging.enabled' => true]);
        $this->service = new LlmService();
    }

    public function test_suggest_returns_text(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Sugerencia de IA']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        $result = $this->service->suggest('Mejora esta redacción', ['text' => 'texto original']);

        $this->assertEquals('Sugerencia de IA', $result);
    }

    public function test_suggest_logs_request(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Respuesta']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        $this->service->suggest('Test prompt');

        $this->assertDatabaseHas('llm_logs', [
            'method' => 'suggest',
            'status' => 'success',
        ]);
    }

    public function test_validate_returns_validation_result(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'is_valid' => false,
                    'issues' => ['Contiene verbos de solución'],
                    'suggestion' => 'Reformule sin verbos',
                ])]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 15, 'total_tokens' => 25],
            ], 200),
        ]);

        $result = $this->service->validate('Implementar sistema', ['no_verbos_solucion']);

        $this->assertInstanceOf(LlmValidationResult::class, $result);
        $this->assertFalse($result->isValid);
        $this->assertCount(1, $result->issues);
    }

    public function test_transform_returns_transformed_text(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Texto transformado positivamente']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 8, 'total_tokens' => 18],
            ], 200),
        ]);

        $result = $this->service->transform('Alta deserción escolar', 'Convertir a positivo');

        $this->assertEquals('Texto transformado positivamente', $result);
    }

    public function test_handles_api_error(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'Rate limited']], 429),
        ]);

        $this->expectException(\App\Exceptions\LlmException::class);

        $this->service->suggest('Test prompt');
    }

    public function test_logs_error_on_failure(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'Server error']], 500),
        ]);

        try {
            $this->service->suggest('Test prompt');
        } catch (\App\Exceptions\LlmException $e) {
            // Expected
        }

        $this->assertDatabaseHas('llm_logs', [
            'method' => 'suggest',
            'status' => 'error',
        ]);
    }

    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(LlmServiceInterface::class, $this->service);
    }
}
