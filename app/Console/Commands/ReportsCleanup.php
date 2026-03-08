<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReportsCleanup extends Command
{
    protected $signature = 'reports:cleanup {--hours= : Override TTL hours from config} {--dry-run : Show what would be deleted}';

    protected $description = 'Delete generated report files older than the configured TTL';

    public function handle(): int
    {
        $disk = config('evaluation.exports.storage_disk', 'local');
        $basePath = config('evaluation.exports.storage_path', 'reportes');
        $ttlHours = (int) ($this->option('hours') ?? config('evaluation.exports.ttl_hours', 24));
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subHours($ttlHours);

        $this->info("Cleaning reports older than {$ttlHours} hours (before {$cutoff->toDateTimeString()})");

        if ($dryRun) {
            $this->warn('DRY RUN — no files will be deleted.');
        }

        $storage = Storage::disk($disk);
        $files = $storage->files($basePath);
        $deleted = 0;

        foreach ($files as $file) {
            $lastModified = $storage->lastModified($file);

            if ($lastModified < $cutoff->timestamp) {
                if ($dryRun) {
                    $this->line("  Would delete: {$file}");
                } else {
                    $storage->delete($file);
                    $this->line("  Deleted: {$file}");
                }
                $deleted++;
            }
        }

        $action = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$action} {$deleted} file(s).");

        return self::SUCCESS;
    }
}
