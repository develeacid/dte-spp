<?php

namespace Tests\Feature\Transparencia\Public;

use App\Enums\EstadoDatasetAbierto;
use App\Jobs\Transparencia\SyncPublicDatasetJob;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use App\Services\Transparencia\Publishing\ProgramasPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class SyncPipelineErrorHandlingTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected bool $fakeBusInSetUp = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_si_publisher_lanza_excepcion_pub_table_no_cambia_y_audita_fail(): void
    {
        $this->app->instance(ProgramasPublisher::class, new class extends ProgramasPublisher
        {
            public function publish(DatasetAbierto $dataset): array
            {
                throw new \RuntimeException('boom');
            }
        });

        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-01',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);

        try {
            SyncPublicDatasetJob::dispatchSync($dataset->id, 'publish', null);
        } catch (\RuntimeException) {
            // expected: el job re-throw para queue retry
        }

        $this->assertSame(0, DB::connection('pgsql_public')->table('pub_programas')->count());

        $fail = TransparenciaPublicacion::where('dataset_clave', 'DS-01')->where('success', false)->first();
        $this->assertNotNull($fail);
        $this->assertStringContainsString('boom', $fail->error_message);
    }

    public function test_si_publisher_falla_no_re_sincroniza_catalogo(): void
    {
        $this->app->instance(ProgramasPublisher::class, new class extends ProgramasPublisher
        {
            public function publish(DatasetAbierto $dataset): array
            {
                throw new \RuntimeException('boom catalogo');
            }
        });

        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-01',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);

        try {
            SyncPublicDatasetJob::dispatchSync($dataset->id, 'publish', null);
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(0, DB::connection('pgsql_public')->table('pub_datasets_catalogo')->count(),
            'Catálogo no debe re-sincronizarse cuando el publisher principal falla');
    }
}
