<?php

namespace Tests\Unit\Llm;

use App\DTOs\LlmValidationResult;
use App\Models\LlmLog;
use App\Services\Llm\LlmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmServiceRefactorTest extends TestCase
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
        config(['llm.cache.enabled' => true]);
        config(['llm.cache.ttl' => 3600]);
        config(['llm.cache.store' => null]);
        config(['llm.fallback.enabled' => true]);
        config(['llm.fallback.message' => 'Análisis de IA no disponible temporalmente.']);
        config(['llm.prompts.manifest_path' => resource_path('views/prompts/manifest.json')]);
        $this->service = new LlmService;
    }

    public function test_degraded_mode_when_no_api_key_suggest(): void
    {
        config(['llm.api_key' => '']);
        Http::fake(); // should NOT be called

        $service = new LlmService;
        $result = $service->suggest('Test prompt');

        $this->assertEquals('Análisis de IA no disponible temporalmente.', $result);
        Http::assertNothingSent();
    }

    public function test_degraded_mode_when_no_api_key_validate(): void
    {
        config(['llm.api_key' => '']);

        $service = new LlmService;
        $result = $service->validate('Test text', ['rule1']);

        $this->assertInstanceOf(LlmValidationResult::class, $result);
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->issues);
    }

    public function test_degraded_mode_on_api_error_returns_fallback(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'Service down']], 500),
        ]);

        $result = $this->service->suggest('Test prompt');

        $this->assertEquals('Análisis de IA no disponible temporalmente.', $result);
    }

    public function test_cache_hit_returns_cached_response(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Fresh response']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        // First call — cache miss, should hit API
        $result1 = $this->service->suggest('Unique test prompt');
        $this->assertEquals('Fresh response', $result1);

        // Second call — same prompt, should return cached
        Http::fake(); // reset to empty — any HTTP call would fail
        $service2 = new LlmService;
        config(['llm.api_key' => 'test-key']);
        $result2 = $service2->suggest('Unique test prompt');

        $this->assertEquals('Fresh response', $result2);
    }

    public function test_cache_miss_calls_api_and_stores(): void
    {
        config(['llm.cache.enabled' => true]);
        Cache::flush();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'API response']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        $result = $this->service->suggest('Cache miss prompt');

        $this->assertEquals('API response', $result);
        Http::assertSentCount(1);

        // Verify something was stored in cache
        $cacheKey = 'llm:'.md5('suggest'.'Cache miss prompt'.json_encode([]).'gpt-4o-mini');
        $this->assertEquals('API response', Cache::get($cacheKey));
    }

    public function test_domain_method_validate_cremaa_delegates(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'is_valid' => false,
                    'issues' => ['No cumple claridad'],
                    'suggestion' => 'Mejorar redacción',
                ])]]],
                'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 10, 'total_tokens' => 30],
            ], 200),
        ]);

        $result = $this->service->validateCremaa([
            'nombre' => 'Test indicator',
            'tipo' => 'Estratégico',
            'dimension' => 'Eficacia',
            'frecuencia' => 'Anual',
            'formula' => 'A/B*100',
        ]);

        $this->assertInstanceOf(LlmValidationResult::class, $result);
        $this->assertFalse($result->isValid);
        $this->assertCount(1, $result->issues);
        Http::assertSentCount(1);
    }

    public function test_manifest_loaded_and_version_available(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'is_valid' => true,
                    'issues' => [],
                    'suggestion' => '',
                ])]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        $this->service->validateCremaa([
            'nombre' => 'Test',
            'tipo' => 'Estratégico',
            'dimension' => 'Eficacia',
            'frecuencia' => 'Anual',
            'formula' => 'A/B*100',
        ]);

        // The log should have been created with the prompt_template
        $this->assertDatabaseHas('llm_logs', [
            'method' => 'validateCremaa',
            'prompt_template' => 'mir/validar-cremaa',
            'status' => 'success',
        ]);
    }

    public function test_generate_justification_returns_null_in_degraded_mode(): void
    {
        config(['llm.api_key' => '']);

        $service = new LlmService;
        $result = $service->generateJustification(['meta' => 100, 'avance' => 80]);

        $this->assertNull($result);
    }

    public function test_is_degraded_returns_true_when_no_key(): void
    {
        config(['llm.api_key' => '']);

        $service = new LlmService;
        $this->assertTrue($service->isDegraded());
    }

    public function test_is_degraded_returns_false_with_key(): void
    {
        $this->assertFalse($this->service->isDegraded());
    }

    public function test_cache_hit_logs_entry(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Cached content']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        // First call populates cache
        $this->service->suggest('Log test prompt');

        // Second call hits cache
        $service2 = new LlmService;
        config(['llm.api_key' => 'test-key']);
        $service2->suggest('Log test prompt');

        $this->assertDatabaseHas('llm_logs', [
            'method' => 'suggest',
            'status' => 'cache_hit',
        ]);
    }

    public function test_transform_skips_cache(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push([
                    'choices' => [['message' => ['content' => 'First transform']]],
                    'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
                ], 200)
                ->push([
                    'choices' => [['message' => ['content' => 'Second transform']]],
                    'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
                ], 200),
        ]);

        $result1 = $this->service->transform('Text', 'Rewrite');
        $result2 = $this->service->transform('Text', 'Rewrite');

        // Both should have hit the API
        $this->assertEquals('First transform', $result1);
        $this->assertEquals('Second transform', $result2);
        Http::assertSentCount(2);
    }

    public function test_extract_variables_returns_empty_in_degraded(): void
    {
        config(['llm.api_key' => '']);

        $service = new LlmService;
        $result = $service->extractVariables('A/B*100');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_detect_causal_breaks_returns_null_in_degraded(): void
    {
        config(['llm.api_key' => '']);

        $service = new LlmService;
        $result = $service->detectCausalBreaks(['data' => 'test']);

        $this->assertNull($result);
    }

    public function test_suggest_alignment_returns_null_in_degraded(): void
    {
        config(['llm.api_key' => '']);

        $service = new LlmService;
        $result = $service->suggestAlignment('texto', 'fin');

        $this->assertNull($result);
    }
}
