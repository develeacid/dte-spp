<?php

namespace Tests\Unit\Jobs\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Jobs\Transparencia\SyncPublicDatasetJob;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use App\Models\User;
use App\Services\Transparencia\Publishing\ProgramasPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class SyncPublicDatasetJobTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected bool $fakeBusInSetUp = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_job_codigo_no_mapeado_termina_sin_error_con_warning(): void
    {
        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-00',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);

        SyncPublicDatasetJob::dispatchSync($dataset->id, 'publish', null);

        $this->assertSame(0, TransparenciaPublicacion::count());
    }

    public function test_job_publish_si_publisher_falla_registra_fail(): void
    {
        $this->app->instance(ProgramasPublisher::class, new class extends ProgramasPublisher
        {
            public function publish(\App\Models\Transparencia\DatasetAbierto $dataset): array
            {
                throw new \RuntimeException('boom forzado');
            }
        });

        $user = User::factory()->create();
        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-01',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);

        try {
            SyncPublicDatasetJob::dispatchSync($dataset->id, 'publish', $user->id);
        } catch (\RuntimeException) {
            // expected — el publisher mockeado lanza, el job re-throw
        }

        $pub = TransparenciaPublicacion::firstWhere('dataset_clave', 'DS-01');
        $this->assertNotNull($pub);
        $this->assertFalse($pub->success);
        $this->assertStringContainsString('boom forzado', $pub->error_message);
    }
}
