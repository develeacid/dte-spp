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

    // --- Path por rangos: rojo_alto (sobrecumplimiento) ---

    public function test_rangos_ascendente_rojo_alto(): void
    {
        $indicador = Indicador::factory()->create([
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'rango_verde_min' => 90,
            'rango_amarillo_min' => 70,
            'rango_rojo_alto_min' => 130,
        ]);

        $this->assertEquals('rojo_alto', $this->service->calcular(150.0, $indicador));
    }

    public function test_rangos_ascendente_verde_no_roto_por_rojo_alto(): void
    {
        $indicador = Indicador::factory()->create([
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'rango_verde_min' => 90,
            'rango_amarillo_min' => 70,
            'rango_rojo_alto_min' => 130,
        ]);

        $this->assertEquals('verde', $this->service->calcular(95.0, $indicador));
    }

    public function test_rangos_descendente_rojo_alto(): void
    {
        $indicador = Indicador::factory()->create([
            'sentido' => SentidoIndicador::DESCENDENTE->value,
            'rango_verde_max' => 100,
            'rango_amarillo_max' => 130,
            'rango_rojo_alto_max' => 50,
        ]);

        $this->assertEquals('rojo_alto', $this->service->calcular(30.0, $indicador));
    }

    public function test_rangos_descendente_verde_no_roto_por_rojo_alto(): void
    {
        $indicador = Indicador::factory()->create([
            'sentido' => SentidoIndicador::DESCENDENTE->value,
            'rango_verde_max' => 100,
            'rango_amarillo_max' => 130,
            'rango_rojo_alto_max' => 50,
        ]);

        $this->assertEquals('verde', $this->service->calcular(80.0, $indicador));
    }

    public function test_rangos_sin_rojo_alto_resultado_alto_sigue_verde(): void
    {
        $indicador = Indicador::factory()->create([
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'rango_verde_min' => 90,
            'rango_amarillo_min' => 70,
            'rango_rojo_alto_min' => null,
        ]);

        $this->assertEquals('verde', $this->service->calcular(500.0, $indicador));
    }

    public function test_rangos_solo_rojo_alto_definido_usa_path_rangos(): void
    {
        $indicador = Indicador::factory()->create([
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'rango_verde_min' => null,
            'rango_verde_max' => null,
            'rango_amarillo_min' => null,
            'rango_amarillo_max' => null,
            'rango_rojo_alto_min' => 130,
        ]);

        // hasRanges true por rojo_alto: 150 >= 130 -> rojo_alto
        $this->assertEquals('rojo_alto', $this->service->calcular(150.0, $indicador));
        // path por rangos (no meta): 50 < 130, sin verde/amarillo -> rojo
        $this->assertEquals('rojo', $this->service->calcular(50.0, $indicador));
    }

    // --- Path fallback por meta: rojo_alto (umbral configurable) ---

    public function test_fallback_meta_ascendente_rojo_alto(): void
    {
        // U=130 default; 135% > 130 -> rojo_alto
        $this->assertEquals('rojo_alto', $this->service->calcular(135.0, $this->indicador, 100.0));
    }

    public function test_fallback_meta_ascendente_meta_exacta_verde(): void
    {
        $this->assertEquals('verde', $this->service->calcular(100.0, $this->indicador, 100.0));
    }

    public function test_fallback_meta_ascendente_umbral_exacto_es_verde(): void
    {
        // 130% no es > 130 -> verde
        $this->assertEquals('verde', $this->service->calcular(130.0, $this->indicador, 100.0));
    }

    public function test_fallback_meta_ascendente_umbral_configurable(): void
    {
        config(['tracking.umbral_sobrecumplimiento' => 150]);

        // 135% no supera 150 -> verde
        $this->assertEquals('verde', $this->service->calcular(135.0, $this->indicador, 100.0));
    }

    public function test_fallback_meta_descendente_rojo_alto(): void
    {
        $indicador = Indicador::factory()->create([
            'sentido' => SentidoIndicador::DESCENDENTE->value,
            'rango_verde_min' => null,
            'rango_verde_max' => null,
            'rango_amarillo_min' => null,
            'rango_amarillo_max' => null,
        ]);

        // U=130 -> resultado < meta * (2 - 1.3) = 100 * 0.7 = 70; 65 < 70 -> rojo_alto
        $this->assertEquals('rojo_alto', $this->service->calcular(65.0, $indicador, 100.0));
    }

    public function test_fallback_meta_descendente_resultado_bueno_verde(): void
    {
        $indicador = Indicador::factory()->create([
            'sentido' => SentidoIndicador::DESCENDENTE->value,
            'rango_verde_min' => null,
            'rango_verde_max' => null,
            'rango_amarillo_min' => null,
            'rango_amarillo_max' => null,
        ]);

        // 95 <= 100 -> verde
        $this->assertEquals('verde', $this->service->calcular(95.0, $indicador, 100.0));
    }

    public function test_fallback_meta_descendente_umbral_exacto_es_verde(): void
    {
        $indicador = Indicador::factory()->create([
            'sentido' => SentidoIndicador::DESCENDENTE->value,
            'rango_verde_min' => null,
            'rango_verde_max' => null,
            'rango_amarillo_min' => null,
            'rango_amarillo_max' => null,
        ]);

        // 70 no es < 70 -> verde
        $this->assertEquals('verde', $this->service->calcular(70.0, $indicador, 100.0));
    }
}
