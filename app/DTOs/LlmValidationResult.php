<?php

namespace App\DTOs;

class LlmValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $issues,
        public readonly string $suggestion,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isValid: $data['is_valid'] ?? false,
            issues: $data['issues'] ?? [],
            suggestion: $data['suggestion'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'issues' => $this->issues,
            'suggestion' => $this->suggestion,
        ];
    }
}
