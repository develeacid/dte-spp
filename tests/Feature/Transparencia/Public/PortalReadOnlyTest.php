<?php

namespace Tests\Feature\Transparencia\Public;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

/**
 * Garantía load-bearing: el rol spp_portal NO puede mutar nada en spp_public.
 * Si alguien futuro agrega GRANT INSERT/UPDATE/DELETE o promueve el rol a
 * SUPERUSER, estos tests fallan loudly.
 */
class PortalReadOnlyTest extends TestCase
{
    use RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_spp_portal_no_puede_insertar(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/permission denied|insufficient privilege/i');

        DB::connection('pgsql_public_read')->table('pub_programas')->insert([
            'ejercicio_fiscal' => 2026,
            'programa_clave' => 'HACK',
            'programa_nombre' => 'X',
            'unidad_responsable' => 'X',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_spp_portal_no_puede_actualizar(): void
    {
        // Insertar data como writer para que haya algo que actualizar.
        DB::connection('pgsql_public')->table('pub_programas')->insert([
            'ejercicio_fiscal' => 2026,
            'programa_clave' => 'TEST-002',
            'programa_nombre' => 'X',
            'unidad_responsable' => 'X',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/permission denied|insufficient privilege/i');

        DB::connection('pgsql_public_read')->table('pub_programas')
            ->where('programa_clave', 'TEST-002')
            ->update(['programa_nombre' => 'Hacked']);
    }

    public function test_spp_portal_no_puede_eliminar(): void
    {
        DB::connection('pgsql_public')->table('pub_programas')->insert([
            'ejercicio_fiscal' => 2026,
            'programa_clave' => 'TEST-003',
            'programa_nombre' => 'X',
            'unidad_responsable' => 'X',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/permission denied|insufficient privilege/i');

        DB::connection('pgsql_public_read')->table('pub_programas')
            ->where('programa_clave', 'TEST-003')
            ->delete();
    }

    public function test_spp_portal_no_puede_crear_tabla(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/permission denied|insufficient privilege/i');

        DB::connection('pgsql_public_read')->statement(
            'CREATE TABLE hack_attempt (id integer)'
        );
    }
}
