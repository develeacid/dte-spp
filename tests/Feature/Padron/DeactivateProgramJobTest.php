<?php

namespace Tests\Feature\Padron;

use App\Jobs\GeoBase\DeactivateProgramOnGeoBase;
use App\Models\ProgramaPresupuestario;
use App\Services\Padron\PadronProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DeactivateProgramJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_llama_al_service_deactivate(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        $service = Mockery::mock(PadronProvisioningService::class);
        $service->shouldReceive('deactivate')
            ->once()
            ->withArgs(fn (ProgramaPresupuestario $arg) => $arg->id === $programa->id)
            ->andReturn(['programa' => $programa->id, 'componentes_desactivados' => 0]);

        (new DeactivateProgramOnGeoBase($programa->id))->handle($service);
    }

    public function test_job_silencia_si_programa_eliminado(): void
    {
        $service = Mockery::mock(PadronProvisioningService::class);
        $service->shouldNotReceive('deactivate');

        (new DeactivateProgramOnGeoBase(999_999))->handle($service);

        $this->assertTrue(true);
    }
}
