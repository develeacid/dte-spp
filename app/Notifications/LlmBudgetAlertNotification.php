<?php

namespace App\Notifications;

use App\Models\LlmBudget;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LlmBudgetAlertNotification extends Notification
{
    use Queueable;

    public function __construct(private LlmBudget $budget) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $scopeLabel = match ($this->budget->scope) {
            'global' => 'Global',
            'team' => "Equipo #{$this->budget->scope_id}",
            'user' => "Usuario #{$this->budget->scope_id}",
            default => $this->budget->scope,
        };

        return [
            'type' => 'llm_budget_alert',
            'scope' => $this->budget->scope,
            'scope_id' => $this->budget->scope_id,
            'month' => $this->budget->month->format('Y-m'),
            'spent_usd' => (float) $this->budget->spent_usd,
            'budget_usd' => (float) $this->budget->budget_usd,
            'percentage' => $this->budget->percentUsed(),
            'message' => "Alerta de presupuesto IA: {$scopeLabel} ha alcanzado el {$this->budget->percentUsed()}% del presupuesto de \${$this->budget->budget_usd} USD para {$this->budget->month->format('Y-m')}.",
        ];
    }
}
