<?php

namespace Tests\Feature\Padron;

use App\Jobs\GeoBase\RegisterProgramOnGeoBase;
use App\Models\ProgramaPresupuestario;
use App\Services\Padron\PadronProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RegisterProgramJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_llama_al_service_register(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
        ]);

        $service = Mockery::mock(PadronProvisioningService::class);
        $service->shouldReceive('register')
            ->once()
            ->withArgs(fn (ProgramaPresupuestario $arg) => $arg->id === $programa->id)
            ->andReturn(['programa' => $programa->id, 'componentes_registrados' => 0]);

        (new RegisterProgramOnGeoBase($programa->id))->handle($service);
    }

    public function test_job_silencia_si_programa_eliminado(): void
    {
        $service = Mockery::mock(PadronProvisioningService::class);
        $service->shouldNotReceive('register');

        // El job recibe un id que no existe; debe terminar sin error.
        (new RegisterProgramOnGeoBase(999_999))->handle($service);

        $this->assertTrue(true); // no exception
    }
}
