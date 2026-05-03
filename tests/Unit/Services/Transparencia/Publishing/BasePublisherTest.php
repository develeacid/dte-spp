<?php

namespace Tests\Unit\Services\Transparencia\Publishing;

use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\BasePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class BasePublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_publish_inserta_filas_y_devuelve_count_hash(): void
    {
        $publisher = new TestablePublisher;
        $dataset = DatasetAbierto::factory()->create();

        $result = $publisher->publish($dataset);

        $this->assertSame(2, $result['count']);
        $this->assertSame(64, strlen($result['hash']));
        $this->assertSame(2, DB::connection('pgsql_public')->table('pub_programas')->count());
    }

    public function test_publish_es_idempotente_mismo_hash(): void
    {
        $publisher = new TestablePublisher;
        $dataset = DatasetAbierto::factory()->create();

        $first = $publisher->publish($dataset);
        $second = $publisher->publish($dataset);

        $this->assertSame($first['hash'], $second['hash']);
        $this->assertSame(2, DB::connection('pgsql_public')->table('pub_programas')->count(), 'No debe duplicar filas');
    }

    public function test_retire_vacia_la_tabla(): void
    {
        $publisher = new TestablePublisher;
        $dataset = DatasetAbierto::factory()->create();
        $publisher->publish($dataset);

        $publisher->retire($dataset);

        $this->assertSame(0, DB::connection('pgsql_public')->table('pub_programas')->count());
    }
}

class TestablePublisher extends BasePublisher
{
    protected function tabla(): string
    {
        return 'pub_programas';
    }

    public function code(): string
    {
        return 'TEST';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        return [
            ['ejercicio_fiscal' => 2026, 'programa_clave' => 'A', 'programa_nombre' => 'A', 'unidad_responsable' => 'X', 'activo' => true, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
            ['ejercicio_fiscal' => 2026, 'programa_clave' => 'B', 'programa_nombre' => 'B', 'unidad_responsable' => 'Y', 'activo' => true, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
        ];
    }
}
