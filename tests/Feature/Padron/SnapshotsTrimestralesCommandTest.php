<?php

namespace Tests\Feature\Padron;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SnapshotsTrimestralesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Carbon::setTestNow('2026-04-15 04:30:00');

        Http::fake([
            '*/snapshots/generate' => Http::sequence()
                ->push(['data' => ['id' => 100, 'snapshot_hash' => 'h1', 'row_count' => 0, 'cutoff_date' => '2026-06-30']], 201)
                ->push(['data' => ['id' => 101, 'snapshot_hash' => 'h2', 'row_count' => 0, 'cutoff_date' => '2026-06-30']], 201)
                ->push(['data' => ['id' => 102, 'snapshot_hash' => 'h3', 'row_count' => 0, 'cutoff_date' => '2026-06-30']], 201)
                ->push(['data' => ['id' => 103, 'snapshot_hash' => 'h4', 'row_count' => 0, 'cutoff_date' => '2026-06-30']], 201),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function programaConComponentes(int $cantidad, bool $padronActivo = true): ProgramaPresupuestario
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => $padronActivo,
            'team_id' => $owner->currentTeam->id,
        ]);

        for ($i = 1; $i <= $cantidad; $i++) {
            MirNivel::create([
                'programa_presupuestario_id' => $programa->id,
                'tipo_nivel' => TipoNivelMir::COMPONENTE,
                'resumen_narrativo' => "C{$i}",
                'orden' => $i,
            ]);
        }

        return $programa;
    }

    public function test_genera_un_snapshot_por_componente_de_programas_activos(): void
    {
        $this->programaConComponentes(2, padronActivo: true);

        $this->artisan('geobase:snapshot-trimestral')->assertSuccessful();

        $this->assertSame(2, AvanceEvidencia::whereNotNull('geobase_snapshot_id')->count());
    }

    public function test_omite_programas_con_padron_inactivo(): void
    {
        $this->programaConComponentes(1, padronActivo: false);

        $this->artisan('geobase:snapshot-trimestral')->assertSuccessful();

        $this->assertSame(0, AvanceEvidencia::whereNotNull('geobase_snapshot_id')->count());
    }

    public function test_dry_run_no_genera_snapshots(): void
    {
        $this->programaConComponentes(2, padronActivo: true);

        $this->artisan('geobase:snapshot-trimestral', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, AvanceEvidencia::whereNotNull('geobase_snapshot_id')->count());
    }

    public function test_idempotente_si_se_corre_dos_veces(): void
    {
        $this->programaConComponentes(2, padronActivo: true);

        $this->artisan('geobase:snapshot-trimestral')->assertSuccessful();
        $this->artisan('geobase:snapshot-trimestral')->assertSuccessful();

        // Each unique snapshot_id (100, 101) should have exactly one evidencia.
        $this->assertSame(1, AvanceEvidencia::where('geobase_snapshot_id', 100)->count());
        $this->assertSame(1, AvanceEvidencia::where('geobase_snapshot_id', 101)->count());
    }
}
