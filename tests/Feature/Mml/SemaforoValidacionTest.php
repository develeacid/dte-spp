<?php

namespace Tests\Feature\Mml;

use App\Models\CatalogoUnidadMedida;
use App\Models\Mml\Indicador;
use App\Services\Mml\IndicadorReglasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemaforoValidacionTest extends TestCase
{
    use RefreshDatabase;

    private function rangos(array $overrides = []): array
    {
        return array_merge([
            'rango_verde_min' => null,
            'rango_verde_max' => null,
            'rango_amarillo_min' => null,
            'rango_amarillo_max' => null,
            'rango_rojo_min' => null,
            'rango_rojo_max' => null,
            'rango_rojo_alto_min' => null,
            'rango_rojo_alto_max' => null,
        ], $overrides);
    }

    private function indicadorConUnidad(string $clave, array $attrs = []): Indicador
    {
        $unidad = CatalogoUnidadMedida::firstOrCreate(
            ['clave' => $clave],
            ['nombre' => $clave === 'PCT' ? 'Porcentaje' : 'Número']
        );

        return Indicador::factory()->create(array_merge(['unidad_medida_id' => $unidad->id], $attrs));
    }

    // --- B3 (C-066) meta en verde ---

    public function test_b3_meta_fuera_del_rango_verde_genera_error(): void
    {
        $indicador = Indicador::factory()->create(['meta' => 50]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos(['rango_verde_min' => 60, 'rango_verde_max' => 80])
        );

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('meta anual', $errores[0]);
    }

    public function test_b3_meta_dentro_del_rango_verde_sin_error(): void
    {
        $indicador = Indicador::factory()->create(['meta' => 70]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos(['rango_verde_min' => 60, 'rango_verde_max' => 80])
        );

        $this->assertSame([], $errores);
    }

    public function test_b3_no_aplica_si_rango_verde_incompleto(): void
    {
        $indicador = Indicador::factory()->create(['meta' => 50]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos(['rango_verde_min' => 60])
        );

        $this->assertSame([], $errores);
    }

    // --- B4 (C-067) sin solapamiento ---

    public function test_b4_rangos_solapados_genera_error(): void
    {
        $indicador = Indicador::factory()->create(['meta' => null]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos([
                'rango_verde_min' => 80, 'rango_verde_max' => 100,
                'rango_amarillo_min' => 70, 'rango_amarillo_max' => 85,
            ])
        );

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('se solapan', $errores[0]);
    }

    public function test_b4_bordes_contiguos_son_validos(): void
    {
        $indicador = Indicador::factory()->create(['meta' => null]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos([
                'rango_verde_min' => 80, 'rango_verde_max' => 100,
                'rango_amarillo_min' => 60, 'rango_amarillo_max' => 80,
            ])
        );

        $this->assertSame([], $errores);
    }

    public function test_b4_min_mayor_que_max_genera_error(): void
    {
        $indicador = Indicador::factory()->create(['meta' => null]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos(['rango_rojo_min' => 50, 'rango_rojo_max' => 30])
        );

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('mínimo mayor que máximo', $errores[0]);
    }

    // --- B5 (C-068) coherencia unidad ---

    public function test_b5_pct_con_limite_mayor_a_100_genera_error(): void
    {
        $indicador = $this->indicadorConUnidad('PCT', ['meta' => null]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos(['rango_verde_max' => 150])
        );

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('Porcentaje', $errores[0]);
    }

    public function test_b5_num_con_limite_mayor_a_100_sin_error(): void
    {
        $indicador = $this->indicadorConUnidad('NUM', ['meta' => null]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos(['rango_verde_max' => 150])
        );

        $this->assertSame([], $errores);
    }

    public function test_b5_pct_con_limites_validos_sin_error(): void
    {
        $indicador = $this->indicadorConUnidad('PCT', ['meta' => null]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos([
                'rango_verde_min' => 80, 'rango_verde_max' => 100,
                'rango_amarillo_min' => 60, 'rango_amarillo_max' => 80,
            ])
        );

        $this->assertSame([], $errores);
    }

    // --- B6 (C-069) rojo no inicia en cero ---

    public function test_b6_rojo_min_cero_genera_error(): void
    {
        $indicador = Indicador::factory()->create(['meta' => null]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos(['rango_rojo_min' => 0])
        );

        $this->assertCount(1, $errores);
        $this->assertStringContainsString('rojo no puede iniciar en cero', $errores[0]);
    }

    public function test_b6_rojo_min_no_cero_sin_error(): void
    {
        $indicador = Indicador::factory()->create(['meta' => null]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos(['rango_rojo_min' => 0.01])
        );

        $this->assertSame([], $errores);
    }

    public function test_b6_rojo_min_null_sin_error(): void
    {
        $indicador = Indicador::factory()->create(['meta' => null]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos(['rango_rojo_min' => null])
        );

        $this->assertSame([], $errores);
    }

    // --- combinadas ---

    public function test_violaciones_combinadas_b3_y_b6(): void
    {
        $indicador = Indicador::factory()->create(['meta' => 50]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos([
                'rango_verde_min' => 60, 'rango_verde_max' => 80,
                'rango_rojo_min' => 0,
            ])
        );

        $this->assertCount(2, $errores);
    }

    public function test_todo_null_sin_errores(): void
    {
        $indicador = Indicador::factory()->create(['meta' => 70]);
        $errores = IndicadorReglasService::validarRangosSemaforo(
            $indicador,
            $this->rangos()
        );

        $this->assertSame([], $errores);
    }
}
