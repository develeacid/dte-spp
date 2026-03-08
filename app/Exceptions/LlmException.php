<?php

namespace App\Exceptions;

use RuntimeException;

class LlmException extends RuntimeException
{
    public static function apiError(string $message, int $statusCode): self
    {
        return new self("LLM API error ({$statusCode}): {$message}", $statusCode);
    }

    public static function timeout(): self
    {
        return new self('LLM API request timed out');
    }

    public static function invalidResponse(string $details): self
    {
        return new self("LLM API returned invalid response: {$details}");
    }
}
