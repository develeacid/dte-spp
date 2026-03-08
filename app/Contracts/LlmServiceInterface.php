<?php

namespace App\Contracts;

use App\DTOs\LlmValidationResult;

interface LlmServiceInterface
{
    public function suggest(string $prompt, array $context = []): string;

    public function validate(string $text, array $rules): LlmValidationResult;

    public function transform(string $text, string $instruction): string;
}
