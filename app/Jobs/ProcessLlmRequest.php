<?php

namespace App\Jobs;

use App\Contracts\LlmServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLlmRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public array $backoff;

    public int $timeout;

    public function __construct(
        public readonly string $method,
        public readonly string $prompt,
        public readonly array $context = [],
        public readonly ?int $userId = null,
        public readonly ?string $callbackEvent = null,
    ) {
        $this->onQueue(config('llm.queue.name', 'llm'));
        $this->tries = config('llm.queue.tries', 3);
        $this->backoff = config('llm.queue.backoff', [10, 60, 300]);
        $this->timeout = config('llm.queue.timeout', 120);
    }

    public function handle(LlmServiceInterface $llmService): void
    {
        $result = match ($this->method) {
            'suggest' => $llmService->suggest($this->prompt, $this->context),
            'transform' => $llmService->transform($this->prompt, $this->context['instruction'] ?? ''),
            default => $llmService->suggest($this->prompt, $this->context),
        };

        if ($this->callbackEvent) {
            event($this->callbackEvent, ['result' => $result, 'user_id' => $this->userId]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessLlmRequest failed', [
            'method' => $this->method,
            'prompt' => substr($this->prompt, 0, 200),
            'error' => $exception->getMessage(),
        ]);
    }
}
