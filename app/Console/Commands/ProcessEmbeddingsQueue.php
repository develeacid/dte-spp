<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessEmbeddingsQueue extends Command
{
    protected $signature = 'queue:embeddings
        {--timeout= : Timeout en segundos}
        {--tries= : Número de intentos}
        {--max-jobs=100 : Máximo de jobs antes de parar}
        {--max-time=3600 : Máximo tiempo de ejecución}
        {--stop-when-empty : Parar cuando la cola esté vacía}';

    protected $description = 'Procesa la cola de embeddings exclusivamente';

    public function handle(): int
    {
        $this->info('Iniciando worker para cola de embeddings...');
        $this->info('Presiona Ctrl+C para detener');

        $params = [
            '--queue' => config('embedding.queue', 'embeddings'),
            '--timeout' => $this->option('timeout') ?? config('embedding.timeout_job', 60),
            '--tries' => $this->option('tries') ?? config('embedding.tries', 3),
            '--max-jobs' => $this->option('max-jobs'),
            '--max-time' => $this->option('max-time'),
        ];

        if ($this->option('stop-when-empty')) {
            $params['--stop-when-empty'] = true;
        }

        $this->call('queue:work', $params);

        return Command::SUCCESS;
    }
}
