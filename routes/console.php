<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('mir:abrir-periodos')->dailyAt('06:00');
Schedule::command('geobase:sync-avances')->dailyAt('22:30')->withoutOverlapping();
Schedule::command('mir:cerrar-vencidos')->dailyAt('23:00');
Schedule::command('app:embeddings-generate')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('reports:cleanup')->dailyAt('03:00');
Schedule::command('llm:cleanup-logs')->monthly();
