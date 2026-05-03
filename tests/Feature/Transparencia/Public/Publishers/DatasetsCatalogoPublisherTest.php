<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\DatasetsCatalogoPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class DatasetsCatalogoPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_publish_solo_incluye_datasets_publicados(): void
    {
        DatasetAbierto::factory()->create(['dataset_clave' => 'DS-01', 'status' => EstadoDatasetAbierto::PUBLICADO]);
        DatasetAbierto::factory()->create(['dataset_clave' => 'DS-02', 'status' => EstadoDatasetAbierto::PUBLICADO]);
        DatasetAbierto::factory()->create(['dataset_clave' => 'DS-99', 'status' => EstadoDatasetAbierto::BORRADOR]);
        DatasetAbierto::factory()->create(['dataset_clave' => 'DS-03', 'status' => EstadoDatasetAbierto::RETIRADO]);

        $publisher = app(DatasetsCatalogoPublisher::class);
        $publisher->publish(DatasetAbierto::factory()->make());

        $codigos = DB::connection('pgsql_public')->table('pub_datasets_catalogo')->pluck('codigo')->all();
        $this->assertEqualsCanonicalizing(['DS-01', 'DS-02'], $codigos);
    }

    public function test_publish_es_idempotente_sin_duplicar(): void
    {
        DatasetAbierto::factory()->create(['dataset_clave' => 'DS-01', 'status' => EstadoDatasetAbierto::PUBLICADO]);

        $publisher = app(DatasetsCatalogoPublisher::class);
        $publisher->publish(DatasetAbierto::factory()->make());
        $publisher->publish(DatasetAbierto::factory()->make());

        $this->assertSame(1, DB::connection('pgsql_public')->table('pub_datasets_catalogo')->count());
    }
}
