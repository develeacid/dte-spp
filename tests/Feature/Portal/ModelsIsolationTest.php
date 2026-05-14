<?php

namespace Tests\Feature\Portal;

use App\Models\Portal\PubPrograma;
use Illuminate\Database\QueryException;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class ModelsIsolationTest extends TestCase
{
    use RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_modelo_portal_via_conexion_read_only_no_puede_escribir(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/permission denied|insufficient privilege/i');

        $programa = new PubPrograma([
            'ejercicio_fiscal' => 2026,
            'programa_clave' => 'BLOCKED',
            'programa_nombre' => 'X',
            'unidad_responsable' => 'X',
            'activo' => true,
        ]);
        $programa->save();
    }
}
