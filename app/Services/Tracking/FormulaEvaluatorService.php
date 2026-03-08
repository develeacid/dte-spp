<?php

namespace App\Services\Tracking;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class FormulaEvaluatorService
{
    public function evaluar(string $formula, array $variables): ?float
    {
        try {
            // Normalize MIR notation: "(A/B) x 100" -> "(A/B) * 100"
            $expr = preg_replace('/\bx\b/i', '*', $formula);
            $expr = preg_replace('/[^\w\s\+\-\*\/\(\)\.\,]/', '', $expr);

            $lang = new ExpressionLanguage();
            $result = $lang->evaluate($expr, $variables);

            return is_numeric($result) ? round((float) $result, 4) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
