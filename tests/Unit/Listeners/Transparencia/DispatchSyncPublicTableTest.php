<?php

namespace Tests\Unit\Listeners\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Events\Transparencia\DatasetAbiertoPublicado;
use App\Events\Transparencia\DatasetAbiertoRetirado;
use App\Jobs\Transparencia\SyncPublicDatasetJob;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DispatchSyncPublicTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_publicado_dispatcha_job_con_action_publish(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $dataset = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::APROBADO]);

        DatasetAbiertoPublicado::dispatch($dataset, $user);

        Bus::assertDispatched(SyncPublicDatasetJob::class, function ($job) use ($dataset, $user) {
            return $job->datasetId === $dataset->id
                && $job->action === 'publish'
                && $job->userId === $user->id;
        });
    }

    public function test_retirado_dispatcha_job_con_action_retire(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $dataset = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::PUBLICADO]);

        DatasetAbiertoRetirado::dispatch($dataset, $user);

        Bus::assertDispatched(SyncPublicDatasetJob::class, function ($job) use ($dataset, $user) {
            return $job->datasetId === $dataset->id
                && $job->action === 'retire'
                && $job->userId === $user->id;
        });
    }

    public function test_evento_sin_user_dispatcha_job_con_userid_null(): void
    {
        Bus::fake();
        $dataset = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::APROBADO]);

        DatasetAbiertoPublicado::dispatch($dataset, null);

        Bus::assertDispatched(SyncPublicDatasetJob::class, function ($job) {
            return $job->userId === null;
        });
    }
}
