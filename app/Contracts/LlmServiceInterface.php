<?php

namespace App\Contracts;

use App\DTOs\LlmValidationResult;

interface LlmServiceInterface
{
    public function suggest(string $prompt, array $context = []): string;

    public function validate(string $text, array $rules): LlmValidationResult;

    public function transform(string $text, string $instruction): string;

    public function renderPrompt(string $view, array $data = []): string;

    public function isDegraded(): bool;

    public function suggestNarrativeSyntax(string $nivel, string $texto): LlmValidationResult;

    public function validateCremaa(array $indicadorData): LlmValidationResult;

    public function validateVerticalLogic(array $mirData): LlmValidationResult;

    public function validateHorizontalLogic(array $nivelData): LlmValidationResult;

    public function extractVariables(string $formula): array;

    public function generateJustification(array $avanceData): ?string;

    public function suggestAlignment(string $texto, string $nivel): ?string;

    public function detectCausalBreaks(array $evaluacionData): ?string;
}
