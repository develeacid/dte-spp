<?php

namespace App\Services\Llm;

use App\Contracts\LlmServiceInterface;
use App\DTOs\LlmValidationResult;
use App\Exceptions\LlmException;
use App\Models\LlmLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class LlmService implements LlmServiceInterface
{
    public function suggest(string $prompt, array $context = []): string
    {
        $messages = $this->buildMessages($prompt, $context);

        return $this->call('suggest', $messages, $prompt);
    }

    public function validate(string $text, array $rules): LlmValidationResult
    {
        $rulesStr = implode(', ', $rules);
        $prompt = "Valida el siguiente texto contra estas reglas: [{$rulesStr}]. Texto: \"{$text}\". Responde en JSON con: {\"is_valid\": bool, \"issues\": [string], \"suggestion\": string}";

        $messages = [
            ['role' => 'system', 'content' => 'Eres un validador de textos para metodología de marco lógico. Siempre responde en JSON válido.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $responseText = $this->call('validate', $messages, $prompt);

        $data = json_decode($responseText, true);

        if (!is_array($data)) {
            throw LlmException::invalidResponse('Response is not valid JSON');
        }

        return LlmValidationResult::fromArray($data);
    }

    public function transform(string $text, string $instruction): string
    {
        $prompt = "{$instruction}: \"{$text}\"";

        $messages = [
            ['role' => 'system', 'content' => 'Eres un asistente de redacción para metodología de marco lógico. Responde solo con el texto transformado, sin explicaciones adicionales.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        return $this->call('transform', $messages, $prompt);
    }

    public function renderPrompt(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }

    private function call(string $method, array $messages, string $promptText, ?string $promptTemplate = null): string
    {
        $this->checkRateLimit();

        $log = LlmLog::create([
            'user_id' => auth()->id(),
            'method' => $method,
            'prompt_template' => $promptTemplate,
            'prompt_text' => $promptText,
            'model' => config('llm.model'),
            'status' => 'pending',
        ]);

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('llm.api_key'),
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
                throw LlmException::apiError($errorMsg, $response->status());
            }

            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? '';
            $usage = $responseData['usage'] ?? [];

            $log->update([
                'status' => 'success',
                'response_text' => $content,
                'prompt_tokens' => $usage['prompt_tokens'] ?? null,
                'completion_tokens' => $usage['completion_tokens'] ?? null,
                'total_tokens' => $usage['total_tokens'] ?? null,
                'duration_ms' => $durationMs,
            ]);

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
        $key = 'llm:' . (auth()->id() ?? 'system');
        $maxPerMinute = config('llm.rate_limit.max_per_minute', 30);

        if (!RateLimiter::attempt($key, $maxPerMinute, fn () => true, 60)) {
            throw LlmException::apiError('Rate limit exceeded', 429);
        }
    }
}
