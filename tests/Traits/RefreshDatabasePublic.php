<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Artisan;

/**
 * Aprovisiona la BD pública testing y la trunca entre tests.
 *
 * Uso: combinar con RefreshDatabase del trait estándar de Laravel.
 * El trait estándar limpia pgsql; este limpia pgsql_public.
 */
trait RefreshDatabasePublic
{
    protected function setUpRefreshDatabasePublic(): void
    {
        // 1. Asegurar que la BD y rol existen.
        Artisan::call('transparencia:provision-public-db');

        // 2. Aplicar migrations sobre la conexión pgsql_public.
        Artisan::call('migrate:fresh', [
            '--path' => 'database/migrations/public',
            '--database' => 'pgsql_public',
            '--force' => true,
        ]);
    }
}
