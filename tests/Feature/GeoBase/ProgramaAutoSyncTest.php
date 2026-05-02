<?php

namespace Tests\Feature\GeoBase;

use App\Enums\TipoNivelMir;
use App\Jobs\GeoBase\SyncMirNivelToGeoBase;
use App\Jobs\GeoBase\SyncProgramaToGeoBase;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProgramaAutoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_observer_dispatches_when_replicated_field_changes_on_active_programa(): void
    {
        Queue::fake();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
            'nombre' => 'Original',
        ]);

        $programa->update(['nombre' => 'Renombrado']);

        Queue::assertPushed(SyncProgramaToGeoBase::class, function ($job) use ($programa) {
            return $job->sppProgramId === $programa->id
                && $job->name === 'Renombrado';
        });
    }

    public function test_observer_does_nothing_when_padron_inactive(): void
    {
        Queue::fake();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
            'nombre' => 'Original',
        ]);

        $programa->update(['nombre' => 'Renombrado']);

        Queue::assertNotPushed(SyncProgramaToGeoBase::class);
    }

    public function test_observer_does_nothing_when_only_unrelated_fields_change(): void
    {
        Queue::fake();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
            'estado' => 'borrador',
        ]);

        $programa->update(['estado' => 'activo']);

        Queue::assertNotPushed(SyncProgramaToGeoBase::class);
    }

    public function test_activation_does_not_retrigger_programa_sync(): void
    {
        Queue::fake();

        // Activation flips padron_geobase_activo from false to true; we don't
        // want that flip alone to dispatch a SyncProgramaToGeoBase, because
        // PadronProvisioningService::register already pushed the program
        // upstream synchronously.
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
            'nombre' => 'Programa X',
        ]);

        $programa->update(['padron_geobase_activo' => true]);

        Queue::assertNotPushed(SyncProgramaToGeoBase::class);
    }

    public function test_clave_change_cascades_to_componente_resync(): void
    {
        Queue::fake();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
            'clave' => 'ISM-001',
        ]);

        // The observer registered on MirNivel will also push when we create
        // these — drain by faking again before the relevant action.
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'C1',
            'orden' => 1,
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'C2',
            'orden' => 2,
        ]);

        Queue::fake();
        $programa->update(['clave' => 'ISM-002']);

        Queue::assertPushed(SyncProgramaToGeoBase::class, 1);
        Queue::assertPushed(SyncMirNivelToGeoBase::class, function ($job) {
            return $job->programaClave === 'ISM-002';
        });
        // Two componentes → two cascade jobs
        Queue::assertPushed(SyncMirNivelToGeoBase::class, 2);
    }

    public function test_job_calls_register_program_with_current_fields(): void
    {
        Http::fake([
            '*/programs' => Http::response(['data' => []], 200),
        ]);

        $job = new SyncProgramaToGeoBase(
            sppProgramId: 7,
            clave: 'ISM-001',
            name: 'Impulso al Sector Mezcalero',
            ejercicioFiscal: 2026,
        );

        $job->handle(app(GeoBaseClient::class));

        Http::assertSent(function ($req) {
            return $req->method() === 'POST'
                && str_contains($req->url(), '/programs')
                && $req['spp_program_id'] === 7
                && $req['clave'] === 'ISM-001'
                && $req['name'] === 'Impulso al Sector Mezcalero'
                && $req['ejercicio_fiscal'] === 2026
                && $req['activo'] === true;
        });
    }
}
