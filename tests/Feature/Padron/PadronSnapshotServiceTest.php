<?php

namespace Tests\Feature\Padron;

use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Padron\PadronSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PadronSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generar_solicita_snapshot_persiste_evidencia_y_logea(): void
    {
        Http::fake([
            '*/snapshots/generate' => Http::response([
                'data' => [
                    'id' => 4421,
                    'snapshot_hash' => 'a3f7c9e2deadbeef',
                    'row_count' => 1847,
                    'cutoff_date' => '2026-03-31T23:59:59.000000Z',
                    'period' => '2026-Q1',
                    'valor_oficial' => 1820,
                ],
            ], 201),
        ]);

        $programa = ProgramaPresupuestario::factory()->create(['geobase_program_id' => 12]);
        $user = User::factory()->withPersonalTeam()->create();

        $service = app(PadronSnapshotService::class);
        $evidencia = $service->generar($programa, componenteId: 3, user: $user);

        $this->assertInstanceOf(AvanceEvidencia::class, $evidencia);
        $this->assertSame('a3f7c9e2deadbeef', $evidencia->hash_archivo);
        $this->assertSame(4421, $evidencia->geobase_snapshot_id);
        $this->assertSame($user->id, $evidencia->subido_por);
        $this->assertNull($evidencia->avance_id);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'padron-snapshot',
            'subject_id' => $evidencia->id,
            'subject_type' => AvanceEvidencia::class,
            'causer_id' => $user->id,
        ]);
    }

    public function test_generar_envia_period_en_formato_q(): void
    {
        Http::fake([
            '*/snapshots/generate' => Http::response([
                'data' => ['id' => 1, 'snapshot_hash' => 'h', 'row_count' => 0, 'cutoff_date' => '2026-03-31'],
            ], 201),
        ]);

        $programa = ProgramaPresupuestario::factory()->create(['geobase_program_id' => 12]);
        $user = User::factory()->withPersonalTeam()->create();

        app(PadronSnapshotService::class)->generar($programa, 3, $user);

        Http::assertSent(function ($req) {
            return preg_match('/^\d{4}-Q[1-4]$/', $req['period']) === 1;
        });
    }

    public function test_generar_lanza_excepcion_si_geobase_falla(): void
    {
        Http::fake(['*' => Http::response(['error' => 'down'], 500)]);

        $programa = ProgramaPresupuestario::factory()->create(['geobase_program_id' => 12]);
        $user = User::factory()->withPersonalTeam()->create();

        $this->expectException(GeoBaseException::class);
        app(PadronSnapshotService::class)->generar($programa, 3, $user);
    }

    public function test_generar_no_persiste_si_geobase_falla(): void
    {
        Http::fake(['*' => Http::response(['error' => 'down'], 500)]);

        $programa = ProgramaPresupuestario::factory()->create(['geobase_program_id' => 12]);
        $user = User::factory()->withPersonalTeam()->create();

        try {
            app(PadronSnapshotService::class)->generar($programa, 3, $user);
        } catch (GeoBaseException) {
            // expected
        }

        $this->assertSame(0, AvanceEvidencia::count());
    }
}
