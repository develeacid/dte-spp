<?php

namespace Tests\Unit\Observers;

use App\Jobs\GeoBase\DeactivateProgramOnGeoBase;
use App\Jobs\GeoBase\RegisterProgramOnGeoBase;
use App\Jobs\GeoBase\SyncProgramaToGeoBase;
use App\Models\ProgramaPresupuestario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProgramaPresupuestarioGeoBaseObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_register_cuando_flag_cambia_a_true(): void
    {
        Bus::fake();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        $programa->update(['padron_geobase_activo' => true]);

        Bus::assertDispatched(RegisterProgramOnGeoBase::class, fn ($job) => $job->programaId === $programa->id);
        Bus::assertNotDispatched(DeactivateProgramOnGeoBase::class);
    }

    public function test_dispatch_deactivate_cuando_flag_cambia_a_false(): void
    {
        Bus::fake();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
        ]);

        $programa->update(['padron_geobase_activo' => false]);

        Bus::assertDispatched(DeactivateProgramOnGeoBase::class, fn ($job) => $job->programaId === $programa->id);
        Bus::assertNotDispatched(RegisterProgramOnGeoBase::class);
    }

    public function test_no_dispatch_si_flag_no_cambia(): void
    {
        Bus::fake();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
            'nombre' => 'Original',
        ]);

        // Cambia un campo replicado pero no el flag.
        $programa->update(['nombre' => 'Editado']);

        Bus::assertNotDispatched(RegisterProgramOnGeoBase::class);
        Bus::assertNotDispatched(DeactivateProgramOnGeoBase::class);
    }

    public function test_dispatch_register_no_dispara_sync_fields_extra(): void
    {
        // Si en el mismo update cambian flag Y nombre, solo se dispatcha
        // el register (que ya re-sincroniza fields via el service);
        // SyncProgramaToGeoBase queda silenciado para evitar doble work.
        Bus::fake();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
            'nombre' => 'Original',
        ]);

        $programa->update([
            'padron_geobase_activo' => true,
            'nombre' => 'Editado',
        ]);

        Bus::assertDispatched(RegisterProgramOnGeoBase::class);
        Bus::assertNotDispatched(SyncProgramaToGeoBase::class);
    }
}
