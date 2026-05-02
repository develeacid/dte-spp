<?php

namespace Tests\Feature\Tracking;

use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Tracking\SemaforoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemaforoTest extends TestCase
{
    use RefreshDatabase;

    private SemaforoService $service;

    private Indicador $indicador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->service = new SemaforoService;

        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PS-001',
            'team_id' => $user->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100, 'activo_seguimiento' => true, 'orden' => 1,
        ]);
    }

    public function test_semaforo_ascendente_verde(): void
    {
        // 95% of meta 100 = verde (>= 90%)
        $result = $this->service->calcular(95.0, $this->indicador, 100.0);

        $this->assertEquals('verde', $result);
    }

    public function test_semaforo_ascendente_amarillo(): void
    {
        // 75% of meta 100 = amarillo (>= 70%, < 90%)
        $result = $this->service->calcular(75.0, $this->indicador, 100.0);

        $this->assertEquals('amarillo', $result);
    }

    public function test_semaforo_rojo_bajo_umbral(): void
    {
        // 50% of meta 100 = rojo (< 70%)
        $result = $this->service->calcular(50.0, $this->indicador, 100.0);

        $this->assertEquals('rojo', $result);
    }
}
