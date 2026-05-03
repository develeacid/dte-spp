<?php

namespace Tests\Feature\Transparencia\Public;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * NOTA: este test NO usa RefreshDatabase porque el comando ejecuta
 * CREATE DATABASE/CREATE ROLE, sentencias que PostgreSQL no permite
 * dentro de una transacción. Además sólo escribe en la BD pública
 * (separada de la conexión `pgsql` de la app), por lo que no requiere
 * rollback en la BD privada.
 */
class ProvisionPublicDatabaseCommandTest extends TestCase
{
    public function test_falla_si_password_no_seteado_en_env(): void
    {
        config(['database.connections.pgsql_public_read.password' => '']);

        $this->artisan('transparencia:provision-public-db')
            ->assertExitCode(1)
            ->expectsOutput('DB_PUBLIC_PORTAL_PASSWORD no está configurado.');
    }

    public function test_crea_db_si_no_existe_y_skip_si_ya_existe(): void
    {
        // Si la BD ya está aprovisionada por una corrida previa, este test
        // ejercita el path "skip" en la primera invocación; si no, ejercita
        // el path "create". En ambos casos la segunda invocación es no-op.
        $this->artisan('transparencia:provision-public-db')->assertExitCode(0);
        $this->artisan('transparencia:provision-public-db')
            ->assertExitCode(0)
            ->expectsOutputToContain('ya existe');
    }

    public function test_db_y_rol_existen_post_provision(): void
    {
        $this->artisan('transparencia:provision-public-db')->assertExitCode(0);

        $dbExists = DB::connection('pgsql')
            ->select('SELECT 1 FROM pg_database WHERE datname = ?', [config('database.connections.pgsql_public.database')]);
        $this->assertNotEmpty($dbExists, 'La BD pública debe existir tras provision');

        $roleExists = DB::connection('pgsql')
            ->select('SELECT 1 FROM pg_roles WHERE rolname = ?', [config('database.connections.pgsql_public_read.username')]);
        $this->assertNotEmpty($roleExists, 'El rol spp_portal debe existir tras provision');
    }

    public function test_idempotente_n_corridas(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->artisan('transparencia:provision-public-db')->assertExitCode(0);
        }
        $this->assertTrue(true);
    }
}
