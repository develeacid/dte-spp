<?php

namespace App\Console\Commands;

use App\Models\LlmLog;
use Illuminate\Console\Command;

class LlmLogsCleanup extends Command
{
    protected $signature = 'llm:cleanup-logs {--days=90 : Days to retain detailed logs} {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete detailed LLM logs older than N days, preserving monthly aggregated summaries';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days)->startOfDay();

        $this->info("Cleaning LLM logs older than {$days} days (before {$cutoff->toDateString()})");

        if ($dryRun) {
            $this->warn('DRY RUN — no data will be deleted.');
        }

        // Count records to delete
        $count = LlmLog::where('created_at', '<', $cutoff)->count();

        if ($count === 0) {
            $this->info('No logs to clean up.');

            return self::SUCCESS;
        }

        $this->info("Found {$count} log(s) to clean up.");

        // Create monthly summary before deleting (aggregate by month + method)
        $summaries = LlmLog::where('created_at', '<', $cutoff)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month")
            ->selectRaw('method')
            ->selectRaw('COUNT(*) as total_calls')
            ->selectRaw('COALESCE(SUM(prompt_tokens), 0) as total_prompt_tokens')
            ->selectRaw('COALESCE(SUM(completion_tokens), 0) as total_completion_tokens')
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
            ->selectRaw('COALESCE(SUM(cost_usd), 0) as total_cost')
            ->selectRaw('COALESCE(AVG(duration_ms), 0) as avg_duration')
            ->selectRaw('SUM(CASE WHEN status = \'error\' THEN 1 ELSE 0 END) as error_count')
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM'), method")
            ->get();

        if ($summaries->isNotEmpty()) {
            $this->info('Monthly summaries (before deletion):');
            $headers = ['Month', 'Method', 'Calls', 'Tokens', 'Cost USD', 'Errors'];
            $rows = $summaries->map(fn ($s) => [
                $s->month,
                $s->method,
                $s->total_calls,
                $s->total_tokens,
                '$'.number_format((float) $s->total_cost, 4),
                $s->error_count,
            ])->toArray();

            $this->table($headers, $rows);
        }

        if (! $dryRun) {
            $deleted = LlmLog::where('created_at', '<', $cutoff)->delete();
            $this->info("Deleted {$deleted} log(s).");
        } else {
            $this->info("Would delete {$count} log(s).");
        }

        return self::SUCCESS;
    }
}
