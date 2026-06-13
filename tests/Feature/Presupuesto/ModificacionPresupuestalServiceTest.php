<?php

namespace Tests\Feature\Presupuesto;

use App\Models\Presupuesto\ModificacionPresupuestal;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Services\Presupuesto\ModificacionPresupuestalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModificacionPresupuestalServiceTest extends TestCase
{
    use RefreshDatabase;

    private function partida(): PartidaPresupuestal
    {
        $team = Team::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create(['team_id' => $team->id]);

        return PartidaPresupuestal::create([
            'programa_presupuestario_id' => $programa->id, 'clave_partida' => '4000',
            'descripcion' => 'Subsidios', 'monto_aprobado' => 1000, 'monto_modificado' => null,
            'ejercicio_fiscal' => 2026, 'team_id' => $team->id,
        ]);
    }

    public function test_ampliacion_suma_al_monto_modificado(): void
    {
        $partida = $this->partida();

        app(ModificacionPresupuestalService::class)->registrar($partida, [
            'tipo' => 'ampliacion', 'monto' => 300, 'fecha' => '2026-03-01', 'oficio' => 'OF-001',
        ]);

        $this->assertDatabaseHas('modificaciones_presupuestales', [
            'partida_presupuestal_id' => $partida->id, 'tipo' => 'ampliacion', 'monto' => 300,
        ]);
        $this->assertEqualsWithDelta(1300, (float) $partida->fresh()->monto_modificado, 0.01);
    }

    public function test_reduccion_resta_del_monto_modificado(): void
    {
        $partida = $this->partida();
        $service = app(ModificacionPresupuestalService::class);

        $service->registrar($partida, ['tipo' => 'ampliacion', 'monto' => 300, 'fecha' => '2026-03-01']);
        $service->registrar($partida, ['tipo' => 'reduccion', 'monto' => 100, 'fecha' => '2026-06-01']);

        // 1000 + 300 - 100 = 1200
        $this->assertEqualsWithDelta(1200, (float) $partida->fresh()->monto_modificado, 0.01);
        $this->assertSame(2, ModificacionPresupuestal::where('partida_presupuestal_id', $partida->id)->count());
    }
}
