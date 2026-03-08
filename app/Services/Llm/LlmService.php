<?php

namespace App\Services\Llm;

use App\Contracts\LlmServiceInterface;
use App\DTOs\LlmValidationResult;
use App\Exceptions\LlmException;
use App\Models\LlmLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LlmService implements LlmServiceInterface
{
    private ?array $manifest = null;

    public function suggest(string $prompt, array $context = []): string
    {
        if ($this->isDegraded()) {
            return config('llm.fallback.message');
        }

        $messages = $this->buildMessages($prompt, $context);
        $cacheKey = $this->cacheKey('suggest', $prompt, $context);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('suggest', $prompt);

            return $cached;
        }

        $result = $this->call('suggest', $messages, $prompt);
        $this->toCache($cacheKey, $result);

        return $result;
    }

    public function validate(string $text, array $rules): LlmValidationResult
    {
        if ($this->isDegraded()) {
            return $this->degradedValidationResult();
        }

        $rulesStr = implode(', ', $rules);
        $prompt = "Valida el siguiente texto contra estas reglas: [{$rulesStr}]. Texto: \"{$text}\". Responde en JSON con: {\"is_valid\": bool, \"issues\": [string], \"suggestion\": string}";

        $cacheKey = $this->cacheKey('validate', $prompt);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('validate', $prompt);

            return LlmValidationResult::fromArray(json_decode($cached, true));
        }

        $messages = [
            ['role' => 'system', 'content' => 'Eres un validador de textos para metodología de marco lógico. Siempre responde en JSON válido.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $responseText = $this->call('validate', $messages, $prompt);

        $data = json_decode($responseText, true);

        if (!is_array($data)) {
            throw LlmException::invalidResponse('Response is not valid JSON');
        }

        $this->toCache($cacheKey, $responseText);

        return LlmValidationResult::fromArray($data);
    }

    public function transform(string $text, string $instruction): string
    {
        if ($this->isDegraded()) {
            return config('llm.fallback.message');
        }

        $prompt = "{$instruction}: \"{$text}\"";

        $messages = [
            ['role' => 'system', 'content' => 'Eres un asistente de redacción para metodología de marco lógico. Responde solo con el texto transformado, sin explicaciones adicionales.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        // transform() skips cache — always unique
        return $this->call('transform', $messages, $prompt);
    }

    public function renderPrompt(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }

    public function isDegraded(): bool
    {
        if (!config('llm.fallback.enabled', true)) {
            return false;
        }

        return empty(config('llm.api_key'));
    }

    // ─── Domain Methods ──────────────────────────────────────────────

    public function suggestNarrativeSyntax(string $nivel, string $texto): LlmValidationResult
    {
        if ($this->isDegraded()) {
            return $this->degradedValidationResult();
        }

        $templateKey = "mir/validar-sintaxis-{$nivel}";
        $view = "prompts.mir.validar-sintaxis-{$nivel}";
        $prompt = $this->renderPrompt($view, ['texto' => $texto]);
        $version = $this->getPromptVersion($templateKey);

        $cacheKey = $this->cacheKey('suggestNarrativeSyntax', $prompt);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('suggestNarrativeSyntax', $prompt);

            return LlmValidationResult::fromArray(json_decode($cached, true));
        }

        $messages = [
            ['role' => 'system', 'content' => 'Eres un validador de sintaxis narrativa para MIR. Siempre responde en JSON válido.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $responseText = $this->call('suggestNarrativeSyntax', $messages, $prompt, $templateKey, $version);
        $data = json_decode($responseText, true);

        if (!is_array($data)) {
            throw LlmException::invalidResponse('Response is not valid JSON');
        }

        $this->toCache($cacheKey, $responseText);

        return LlmValidationResult::fromArray($data);
    }

    public function validateCremaa(array $indicadorData): LlmValidationResult
    {
        if ($this->isDegraded()) {
            return $this->degradedValidationResult();
        }

        $templateKey = 'mir/validar-cremaa';
        $prompt = $this->renderPrompt('prompts.mir.validar-cremaa', $indicadorData);
        $version = $this->getPromptVersion($templateKey);

        $cacheKey = $this->cacheKey('validateCremaa', $prompt);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('validateCremaa', $prompt);

            return LlmValidationResult::fromArray(json_decode($cached, true));
        }

        $messages = [
            ['role' => 'system', 'content' => 'Eres un validador de criterios CREMAA para indicadores MIR. Siempre responde en JSON válido.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $responseText = $this->call('validateCremaa', $messages, $prompt, $templateKey, $version);
        $data = json_decode($responseText, true);

        if (!is_array($data)) {
            throw LlmException::invalidResponse('Response is not valid JSON');
        }

        $this->toCache($cacheKey, $responseText);

        return LlmValidationResult::fromArray($data);
    }

    public function validateVerticalLogic(array $mirData): LlmValidationResult
    {
        if ($this->isDegraded()) {
            return $this->degradedValidationResult();
        }

        $templateKey = 'mir/validar-logica-vertical';
        $prompt = $this->renderPrompt('prompts.mir.validar-logica-vertical', $mirData);
        $version = $this->getPromptVersion($templateKey);

        $cacheKey = $this->cacheKey('validateVerticalLogic', $prompt);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('validateVerticalLogic', $prompt);

            return LlmValidationResult::fromArray(json_decode($cached, true));
        }

        $messages = [
            ['role' => 'system', 'content' => 'Eres un validador de lógica vertical para MIR. Siempre responde en JSON válido.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $responseText = $this->call('validateVerticalLogic', $messages, $prompt, $templateKey, $version);
        $data = json_decode($responseText, true);

        if (!is_array($data)) {
            throw LlmException::invalidResponse('Response is not valid JSON');
        }

        $this->toCache($cacheKey, $responseText);

        return LlmValidationResult::fromArray($data);
    }

    public function validateHorizontalLogic(array $nivelData): LlmValidationResult
    {
        if ($this->isDegraded()) {
            return $this->degradedValidationResult();
        }

        $templateKey = 'mir/validar-logica-horizontal';
        $prompt = $this->renderPrompt('prompts.mir.validar-logica-horizontal', $nivelData);
        $version = $this->getPromptVersion($templateKey);

        $cacheKey = $this->cacheKey('validateHorizontalLogic', $prompt);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('validateHorizontalLogic', $prompt);

            return LlmValidationResult::fromArray(json_decode($cached, true));
        }

        $messages = [
            ['role' => 'system', 'content' => 'Eres un validador de lógica horizontal para MIR. Siempre responde en JSON válido.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $responseText = $this->call('validateHorizontalLogic', $messages, $prompt, $templateKey, $version);
        $data = json_decode($responseText, true);

        if (!is_array($data)) {
            throw LlmException::invalidResponse('Response is not valid JSON');
        }

        $this->toCache($cacheKey, $responseText);

        return LlmValidationResult::fromArray($data);
    }

    public function extractVariables(string $formula): array
    {
        if ($this->isDegraded()) {
            return [];
        }

        $templateKey = 'mir/extraer-variables';
        $prompt = $this->renderPrompt('prompts.mir.extraer-variables', ['formula' => $formula]);
        $version = $this->getPromptVersion($templateKey);

        $cacheKey = $this->cacheKey('extractVariables', $prompt);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('extractVariables', $prompt);

            return json_decode($cached, true) ?: [];
        }

        $messages = [
            ['role' => 'system', 'content' => 'Eres un asistente para extracción de variables de fórmulas de indicadores. Responde en JSON como array de strings.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $responseText = $this->call('extractVariables', $messages, $prompt, $templateKey, $version);
        $data = json_decode($responseText, true);

        if (!is_array($data)) {
            return [];
        }

        $this->toCache($cacheKey, $responseText);

        return $data;
    }

    public function generateJustification(array $avanceData): ?string
    {
        if ($this->isDegraded()) {
            return null;
        }

        $templateKey = 'tracking/justificar-avance';
        $prompt = $this->renderPrompt('prompts.tracking.justificar-avance', $avanceData);
        $version = $this->getPromptVersion($templateKey);

        $cacheKey = $this->cacheKey('generateJustification', $prompt);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('generateJustification', $prompt);

            return $cached;
        }

        $messages = $this->buildMessages($prompt, []);
        $result = $this->call('generateJustification', $messages, $prompt, $templateKey, $version);
        $this->toCache($cacheKey, $result);

        return $result;
    }

    public function suggestAlignment(string $texto, string $nivel): ?string
    {
        if ($this->isDegraded()) {
            return null;
        }

        $prompt = "Sugiere cómo alinear el siguiente texto al nivel '{$nivel}' de la MIR: \"{$texto}\"";

        $cacheKey = $this->cacheKey('suggestAlignment', $prompt);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('suggestAlignment', $prompt);

            return $cached;
        }

        $messages = $this->buildMessages($prompt, []);
        $result = $this->call('suggestAlignment', $messages, $prompt);
        $this->toCache($cacheKey, $result);

        return $result;
    }

    public function detectCausalBreaks(array $evaluacionData): ?string
    {
        if ($this->isDegraded()) {
            return null;
        }

        $templateKey = 'evaluation/analizar-rupturas';
        $prompt = $this->renderPrompt('prompts.evaluation.analizar-rupturas', $evaluacionData);
        $version = $this->getPromptVersion($templateKey);

        $cacheKey = $this->cacheKey('detectCausalBreaks', $prompt);

        if ($cached = $this->fromCache($cacheKey)) {
            $this->logCacheHit('detectCausalBreaks', $prompt);

            return $cached;
        }

        $messages = $this->buildMessages($prompt, []);
        $result = $this->call('detectCausalBreaks', $messages, $prompt, $templateKey, $version);
        $this->toCache($cacheKey, $result);

        return $result;
    }

    // ─── Private Helpers ─────────────────────────────────────────────

    private function call(string $method, array $messages, string $promptText, ?string $promptTemplate = null, ?string $promptVersion = null): string
    {
        $this->checkRateLimit();

        $logData = [
            'user_id' => auth()->id(),
            'method' => $method,
            'prompt_template' => $promptTemplate,
            'prompt_text' => $promptText,
            'model' => config('llm.model'),
            'status' => 'pending',
        ];

        $log = LlmLog::create($logData);

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('llm.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->timeout(config('llm.timeout', 60))
            ->post(config('llm.api_url'), [
                'model' => config('llm.model'),
                'messages' => $messages,
                'max_tokens' => config('llm.max_tokens', 2000),
                'temperature' => config('llm.temperature', 0.7),
            ]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $errorMsg = $response->json('error.message', 'Unknown error');
                $log->update([
                    'status' => 'error',
                    'error_message' => $errorMsg,
                    'duration_ms' => $durationMs,
                ]);

                if (config('llm.fallback.enabled', true)) {
                    Log::warning("LLM API error in {$method}, returning degraded response", [
                        'error' => $errorMsg,
                        'status_code' => $response->status(),
                    ]);

                    return $this->degradedTextResponse();
                }

                throw LlmException::apiError($errorMsg, $response->status());
            }

            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? '';
            $usage = $responseData['usage'] ?? [];

            $updateData = [
                'status' => 'success',
                'response_text' => $content,
                'prompt_tokens' => $usage['prompt_tokens'] ?? null,
                'completion_tokens' => $usage['completion_tokens'] ?? null,
                'total_tokens' => $usage['total_tokens'] ?? null,
                'duration_ms' => $durationMs,
            ];

            $log->update($updateData);

            return trim($content);

        } catch (LlmException $e) {
            throw $e;
        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $log->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            if (config('llm.fallback.enabled', true)) {
                Log::warning("LLM exception in {$method}, returning degraded response", [
                    'error' => $e->getMessage(),
                ]);

                return $this->degradedTextResponse();
            }

            if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                throw LlmException::timeout();
            }

            throw LlmException::apiError($e->getMessage(), 0);
        }
    }

    private function buildMessages(string $prompt, array $context): array
    {
        $messages = [
            ['role' => 'system', 'content' => 'Eres un asistente especializado en metodología de marco lógico para programas presupuestarios del sector público mexicano.'],
        ];

        if (!empty($context)) {
            $contextStr = collect($context)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");
            $messages[] = ['role' => 'user', 'content' => "Contexto:\n{$contextStr}\n\n{$prompt}"];
        } else {
            $messages[] = ['role' => 'user', 'content' => $prompt];
        }

        return $messages;
    }

    private function checkRateLimit(): void
    {
        $key = 'llm:'.(auth()->id() ?? 'system');
        $maxPerMinute = config('llm.rate_limit.max_per_minute', 30);

        if (!RateLimiter::attempt($key, $maxPerMinute, fn () => true, 60)) {
            throw LlmException::apiError('Rate limit exceeded', 429);
        }
    }

    // ─── Degraded Mode Helpers ───────────────────────────────────────

    private function degradedValidationResult(): LlmValidationResult
    {
        return LlmValidationResult::fromArray([
            'is_valid' => true,
            'issues' => [],
            'suggestion' => config('llm.fallback.message'),
        ]);
    }

    private function degradedTextResponse(): string
    {
        return config('llm.fallback.message');
    }

    // ─── Cache Helpers ───────────────────────────────────────────────

    private function cacheKey(string $method, string $prompt, array $context = []): ?string
    {
        if (!config('llm.cache.enabled', true)) {
            return null;
        }

        $payload = $method.$prompt.json_encode($context).config('llm.model');

        return 'llm:'.md5($payload);
    }

    private function fromCache(?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        $store = config('llm.cache.store');
        $cache = $store ? Cache::store($store) : Cache::store();

        return $cache->get($key);
    }

    private function toCache(?string $key, string $value): void
    {
        if ($key === null) {
            return;
        }

        $store = config('llm.cache.store');
        $cache = $store ? Cache::store($store) : Cache::store();
        $ttl = config('llm.cache.ttl', 3600);

        $cache->put($key, $value, $ttl);
    }

    private function logCacheHit(string $method, string $prompt): void
    {
        LlmLog::create([
            'user_id' => auth()->id(),
            'method' => $method,
            'prompt_text' => $prompt,
            'model' => config('llm.model'),
            'status' => 'cache_hit',
            'duration_ms' => 0,
        ]);
    }

    // ─── Manifest Helpers ────────────────────────────────────────────

    private function loadManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $path = config('llm.prompts.manifest_path');

        if ($path && file_exists($path)) {
            $contents = file_get_contents($path);
            $data = json_decode($contents, true);
            $this->manifest = $data['prompts'] ?? [];
        } else {
            $this->manifest = [];
        }

        return $this->manifest;
    }

    private function getPromptVersion(string $templateKey): ?string
    {
        $manifest = $this->loadManifest();

        return $manifest[$templateKey]['version'] ?? null;
    }
}
