<?php

namespace Tests\Feature\Transparencia\Public;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class PublicConnectionTest extends TestCase
{
    use RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_conexion_pgsql_public_funciona(): void
    {
        $result = DB::connection('pgsql_public')->select('SELECT 1 AS one');
        $this->assertSame(1, (int) $result[0]->one);
    }

    public function test_conexion_pgsql_public_read_funciona(): void
    {
        $result = DB::connection('pgsql_public_read')->select('SELECT 1 AS one');
        $this->assertSame(1, (int) $result[0]->one);
    }

    public function test_pgsql_public_puede_insertar_y_leer(): void
    {
        DB::connection('pgsql_public')->table('pub_programas')->insert([
            'ejercicio_fiscal' => 2026,
            'programa_clave' => 'TEST-001',
            'programa_nombre' => 'Programa de prueba',
            'unidad_responsable' => 'SE',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = DB::connection('pgsql_public')->table('pub_programas')
            ->where('programa_clave', 'TEST-001')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Programa de prueba', $rows[0]->programa_nombre);
    }
}
