<?php

namespace Tests\Feature\GeoBase;

use App\Enums\TipoNivelMir;
use App\Jobs\GeoBase\SyncMirNivelToGeoBase;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MirNivelAutoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_observer_dispatches_job_when_componente_saved_on_active_programa(): void
    {
        Queue::fake();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'C1 narrativa',
            'orden' => 1,
        ]);

        Queue::assertPushed(SyncMirNivelToGeoBase::class, function ($job) use ($programa) {
            return $job->sppProgramId === $programa->id
                && $job->programaClave === $programa->clave
                && $job->activo === true;
        });
    }

    public function test_observer_does_nothing_when_padron_inactive(): void
    {
        Queue::fake();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => false]);

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'C1',
            'orden' => 1,
        ]);

        Queue::assertNotPushed(SyncMirNivelToGeoBase::class);
    }

    public function test_observer_ignores_fin_proposito_actividad(): void
    {
        Queue::fake();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);

        foreach ([TipoNivelMir::FIN, TipoNivelMir::PROPOSITO, TipoNivelMir::ACTIVIDAD] as $tipo) {
            MirNivel::create([
                'programa_presupuestario_id' => $programa->id,
                'tipo_nivel' => $tipo,
                'resumen_narrativo' => "Nivel {$tipo->value}",
                'orden' => 1,
            ]);
        }

        Queue::assertNotPushed(SyncMirNivelToGeoBase::class);
    }

    public function test_observer_dispatches_with_activo_false_on_delete(): void
    {
        Queue::fake();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);

        $componente = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'C1',
            'orden' => 1,
        ]);

        Queue::clearResolvedInstances();
        Queue::fake();

        $componente->delete();

        Queue::assertPushed(SyncMirNivelToGeoBase::class, function ($job) {
            return $job->activo === false;
        });
    }

    public function test_job_calls_register_component_with_stable_clave(): void
    {
        Http::fake([
            '*/components' => Http::response(['data' => []], 201),
        ]);

        $job = new SyncMirNivelToGeoBase(
            mirNivelId: 42,
            sppProgramId: 7,
            programaClave: 'ISM-001',
            resumenNarrativo: 'Componente capacitación',
            activo: true,
        );

        $job->handle(app(\App\Services\GeoBase\GeoBaseClient::class));

        Http::assertSent(function ($req) {
            return $req->method() === 'POST'
                && str_contains($req->url(), '/components')
                && $req['spp_mir_nivel_id'] === 42
                && $req['spp_program_id'] === 7
                && $req['clave'] === 'ISM-001-MN42'
                && $req['activo'] === true;
        });
    }

    public function test_job_propagates_geobase_failure_for_retry(): void
    {
        Http::fake(['*/components' => Http::response(['error' => 'down'], 500)]);

        $job = new SyncMirNivelToGeoBase(
            mirNivelId: 1,
            sppProgramId: 1,
            programaClave: 'X',
            resumenNarrativo: 'C',
            activo: true,
        );

        $this->expectException(\App\Services\GeoBase\GeoBaseException::class);
        $job->handle(app(\App\Services\GeoBase\GeoBaseClient::class));
    }
}
