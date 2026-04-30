<?php

namespace Tests\Unit\Listeners;

use App\Events\GeoBase\SnapshotGenerated;
use App\Listeners\GeoBase\StoreSnapshotHash;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StoreSnapshotHashTest extends TestCase
{
    use RefreshDatabase;

    private function createFullChain(string $period = '2026-Q1'): Avance
    {
        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-'.uniqid(),
            'padron_geobase_activo' => true,
        ]);

        $mirNivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente',
            'resumen_narrativo' => 'Test narrativo',
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $mirNivel->id,
            'nombre' => 'Indicador Test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
        ]);

        // period is "YYYY-QN" — extract trimestre + ejercicio
        preg_match('/^(\d{4})-Q([1-4])$/', $period, $m);
        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => (int) ($m[2] ?? 1),
            'meta_periodo' => 100.0000,
            'ejercicio_fiscal' => (int) ($m[1] ?? 2026),
        ]);

        return Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'resultado' => 50.0000,
            'semaforo_calculado' => 'verde',
            'estado' => 'en_captura',
            'capturado_por' => $user->id,
        ]);
    }

    private function makeEvent(int $sppProgramId = 99, string $period = '2026-Q1'): SnapshotGenerated
    {
        return new SnapshotGenerated(
            snapshotId: 42,
            period: $period,
            snapshotHash: 'abc123hash',
            sppMirNivelId: 10,
            sppProgramId: $sppProgramId,
            valorOficial: 500,
            timestamp: '2026-03-19T12:00:00Z',
        );
    }

    public function test_creates_evidencia_when_programa_and_avance_exist(): void
    {
        $avance = $this->createFullChain(period: '2026-Q1');
        $programaId = $avance->indicador->mirNivel->programa_presupuestario_id;

        $listener = new StoreSnapshotHash();
        $listener->handle($this->makeEvent(sppProgramId: $programaId, period: '2026-Q1'));

        $this->assertDatabaseHas('avance_evidencias', [
            'avance_id' => $avance->id,
            'nombre_archivo' => 'snapshot-42-2026-Q1.csv',
            'ruta_archivo' => '',
            'mime_type' => 'text/csv',
            'tamano_bytes' => 0,
            'hash_archivo' => 'abc123hash',
            'geobase_snapshot_id' => 42,
            'nombre_documento' => 'Snapshot GeoBase 2026-Q1',
            'area_generadora' => 'GeoBase (automatico)',
        ]);
    }

    public function test_logs_warning_when_programa_not_found(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $msg) => str_contains($msg, 'programa'));

        $listener = new StoreSnapshotHash();
        $listener->handle($this->makeEvent(sppProgramId: 999999));

        $this->assertDatabaseCount('avance_evidencias', 0);
    }

    public function test_does_not_duplicate_evidencia_when_dispatched_twice(): void
    {
        $avance = $this->createFullChain(period: '2026-Q1');
        $programaId = $avance->indicador->mirNivel->programa_presupuestario_id;
        $event = $this->makeEvent(sppProgramId: $programaId, period: '2026-Q1');

        $listener = new StoreSnapshotHash();
        $listener->handle($event);
        $listener->handle($event);

        $this->assertDatabaseCount('avance_evidencias', 1);
    }

    public function test_logs_info_when_avance_not_found(): void
    {
        User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Sin Avance',
            'clave' => 'PSA-'.uniqid(),
            'padron_geobase_activo' => true,
        ]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn (string $msg) => str_contains($msg, 'avance'));

        $listener = new StoreSnapshotHash();
        $listener->handle($this->makeEvent(sppProgramId: $programa->id, period: '2026-Q1'));

        $this->assertDatabaseCount('avance_evidencias', 0);
    }
}
