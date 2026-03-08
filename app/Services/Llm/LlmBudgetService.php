<?php

namespace App\Services\Llm;

use App\Models\LlmBudget;
use App\Models\LlmLog;
use App\Models\User;
use App\Notifications\LlmBudgetAlertNotification;
use Illuminate\Support\Facades\Log;

class LlmBudgetService
{
    /**
     * Approximate pricing per 1M tokens (gpt-4o-mini defaults).
     */
    private const INPUT_COST_PER_MILLION = 0.15;

    private const OUTPUT_COST_PER_MILLION = 0.60;

    /**
     * Estimate cost for a log entry based on token usage.
     */
    public static function estimateCost(LlmLog $log): float
    {
        $inputCost = (($log->prompt_tokens ?? 0) / 1_000_000) * self::INPUT_COST_PER_MILLION;
        $outputCost = (($log->completion_tokens ?? 0) / 1_000_000) * self::OUTPUT_COST_PER_MILLION;

        return round($inputCost + $outputCost, 6);
    }

    /**
     * After an API call, update the log cost and check budgets.
     */
    public function checkAndAlert(LlmLog $log): void
    {
        $cost = self::estimateCost($log);
        $log->update(['cost_usd' => $cost]);

        if ($cost <= 0) {
            return;
        }

        $month = now()->startOfMonth()->toDateString();

        // Update global budget
        $this->incrementBudget('global', null, $month, $cost);

        // Update team budget if user has a team
        if ($log->user_id) {
            $user = User::find($log->user_id);
            if ($user && $user->currentTeam) {
                $this->incrementBudget('team', $user->currentTeam->id, $month, $cost);
            }

            // Update user budget
            $this->incrementBudget('user', $log->user_id, $month, $cost);
        }
    }

    /**
     * Get or create a budget for a given scope.
     */
    public function getOrCreateBudget(string $scope, ?int $scopeId, string $month): LlmBudget
    {
        return LlmBudget::firstOrCreate(
            [
                'scope' => $scope,
                'scope_id' => $scopeId,
                'month' => $month,
            ],
            [
                'budget_usd' => $this->defaultBudget($scope),
                'spent_usd' => 0,
                'alert_threshold' => 0.80,
            ]
        );
    }

    private function incrementBudget(string $scope, ?int $scopeId, string $month, float $cost): void
    {
        $budget = $this->getOrCreateBudget($scope, $scopeId, $month);
        $budget->increment('spent_usd', $cost);
        $budget->refresh();

        if ($budget->isOverThreshold() && $budget->alerted_at === null) {
            $this->sendAlert($budget);
        }
    }

    private function sendAlert(LlmBudget $budget): void
    {
        $budget->update(['alerted_at' => now()]);

        // Notify all admins
        $admins = User::permission('administrar_usuarios')->get();

        foreach ($admins as $admin) {
            $admin->notify(new LlmBudgetAlertNotification($budget));
        }

        Log::warning('LLM budget alert triggered', [
            'scope' => $budget->scope,
            'scope_id' => $budget->scope_id,
            'month' => $budget->month->format('Y-m'),
            'spent' => $budget->spent_usd,
            'budget' => $budget->budget_usd,
            'percent' => $budget->percentUsed(),
        ]);
    }

    private function defaultBudget(string $scope): float
    {
        return match ($scope) {
            'global' => 50.00,
            'team' => 10.00,
            'user' => 5.00,
            default => 10.00,
        };
    }
}
