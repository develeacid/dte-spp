<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LlmBudget extends Model
{
    protected $fillable = [
        'scope',
        'scope_id',
        'month',
        'budget_usd',
        'spent_usd',
        'alert_threshold',
        'alerted_at',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'budget_usd' => 'decimal:2',
            'spent_usd' => 'decimal:6',
            'alert_threshold' => 'decimal:2',
            'alerted_at' => 'datetime',
        ];
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->where('month', $month);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', 'global');
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('scope', 'team')->where('scope_id', $teamId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('scope', 'user')->where('scope_id', $userId);
    }

    // ─── Methods ────────────────────────────────────────────────────

    public function percentUsed(): float
    {
        if ((float) $this->budget_usd <= 0) {
            return 0;
        }

        return round(((float) $this->spent_usd / (float) $this->budget_usd) * 100, 2);
    }

    public function isOverThreshold(): bool
    {
        if ((float) $this->budget_usd <= 0) {
            return false;
        }

        return ((float) $this->spent_usd / (float) $this->budget_usd) >= (float) $this->alert_threshold;
    }
}
