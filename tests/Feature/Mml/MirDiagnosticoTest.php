<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Services\Mml\MirDiagnosticoService;
use App\Services\Mml\MirParserService;
use Tests\TestCase;

class MirDiagnosticoTest extends TestCase
{
    private MirDiagnosticoService $diagnostico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->diagnostico = new MirDiagnosticoService();
    }

    public function test_complete_mir_has_no_critical_gaps(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $data = (new MirParserService())->fromMarkdown($content);

        $gaps = $this->diagnostico->diagnosticar($data);
        $criticos = array_filter($gaps, fn ($g) => $g['severidad'] === 'critico');

        // The sample fixture is well-formed, so no critical gaps expected
        $this->assertEmpty($criticos);
    }

    public function test_missing_resumen_narrativo_is_critical(): void
    {
        $data = new ImportedMirData(niveles: [
            [
                'tipo_nivel' => 'fin',
                'resumen_narrativo' => null,
                'supuestos' => 'Algo',
                'orden' => 1,
                'indicadores' => [
                    [
                        'nombre' => 'Ind1',
                        'formula_texto' => 'A/B',
                        'tipo' => 'estrategico',
                        'dimension' => 'eficacia',
                        'frecuencia' => 'anual',
                        'sentido' => 'ascendente',
                        'linea_base' => 0,
                        'meta' => 100,
                        'rangos_semaforo' => null,
                        'variables' => [],
                        'medios' => [['nombre' => 'Informe', 'fuente' => null]],
                    ],
                ],
            ],
        ]);

        $gaps = $this->diagnostico->diagnosticar($data);
        $resumenGaps = array_filter($gaps, fn ($g) => $g['campo'] === 'resumen_narrativo');

        $this->assertNotEmpty($resumenGaps);
        $this->assertEquals('critico', array_values($resumenGaps)[0]['severidad']);
    }

    public function test_missing_formula_is_critical(): void
    {
        $data = new ImportedMirData(niveles: [
            [
                'tipo_nivel' => 'fin',
                'resumen_narrativo' => 'Contribuir a algo',
                'supuestos' => 'Algo',
                'orden' => 1,
                'indicadores' => [
                    [
                        'nombre' => 'Indicador sin fórmula',
                        'formula_texto' => null,
                        'tipo' => 'estrategico',
                        'dimension' => 'eficacia',
                        'frecuencia' => 'anual',
                        'sentido' => null,
                        'linea_base' => null,
                        'meta' => null,
                        'rangos_semaforo' => null,
                        'variables' => [],
                        'medios' => [['nombre' => 'Informe', 'fuente' => null]],
                    ],
                ],
            ],
        ]);

        $gaps = $this->diagnostico->diagnosticar($data);
        $formulaGaps = array_filter($gaps, fn ($g) => $g['campo'] === 'formula_texto');

        $this->assertNotEmpty($formulaGaps);
        $this->assertEquals('critico', array_values($formulaGaps)[0]['severidad']);
    }

    public function test_missing_sentido_is_minor(): void
    {
        $data = new ImportedMirData(niveles: [
            [
                'tipo_nivel' => 'fin',
                'resumen_narrativo' => 'Contribuir a algo',
                'supuestos' => 'Algo',
                'orden' => 1,
                'indicadores' => [
                    [
                        'nombre' => 'Ind1',
                        'formula_texto' => 'A/B',
                        'tipo' => 'estrategico',
                        'dimension' => 'eficacia',
                        'frecuencia' => 'anual',
                        'sentido' => null,
                        'linea_base' => null,
                        'meta' => null,
                        'rangos_semaforo' => null,
                        'variables' => [],
                        'medios' => [['nombre' => 'Informe', 'fuente' => null]],
                    ],
                ],
            ],
        ]);

        $gaps = $this->diagnostico->diagnosticar($data);
        $sentidoGaps = array_filter($gaps, fn ($g) => $g['campo'] === 'sentido');

        $this->assertNotEmpty($sentidoGaps);
        $this->assertEquals('menor', array_values($sentidoGaps)[0]['severidad']);
    }

    public function test_unrecognized_enum_value_is_warning(): void
    {
        $data = new ImportedMirData(niveles: [
            [
                'tipo_nivel' => 'fin',
                'resumen_narrativo' => 'Algo',
                'supuestos' => 'Algo',
                'orden' => 1,
                'indicadores' => [
                    [
                        'nombre' => 'Ind1',
                        'formula_texto' => 'A/B',
                        'tipo' => 'desconocido',
                        'dimension' => 'eficacia',
                        'frecuencia' => 'anual',
                        'sentido' => null,
                        'linea_base' => null,
                        'meta' => null,
                        'rangos_semaforo' => null,
                        'variables' => [],
                        'medios' => [['nombre' => 'Informe', 'fuente' => null]],
                    ],
                ],
            ],
        ]);

        $gaps = $this->diagnostico->diagnosticar($data);
        $warnings = array_filter($gaps, fn ($g) => $g['severidad'] === 'advertencia' && $g['campo'] === 'tipo');

        $this->assertNotEmpty($warnings);
    }

    public function test_conteo_counts_by_severity(): void
    {
        $gaps = [
            ['nivel_idx' => 0, 'indicador_idx' => null, 'campo' => 'resumen_narrativo', 'severidad' => 'critico', 'mensaje' => 'Falta'],
            ['nivel_idx' => 0, 'indicador_idx' => 0, 'campo' => 'formula_texto', 'severidad' => 'critico', 'mensaje' => 'Falta'],
            ['nivel_idx' => 0, 'indicador_idx' => 0, 'campo' => 'sentido', 'severidad' => 'menor', 'mensaje' => 'Falta'],
            ['nivel_idx' => 0, 'indicador_idx' => 0, 'campo' => 'tipo', 'severidad' => 'advertencia', 'mensaje' => 'No reconocido'],
        ];

        $conteo = $this->diagnostico->conteo($gaps);

        $this->assertEquals(2, $conteo['critico']);
        $this->assertEquals(1, $conteo['menor']);
        $this->assertEquals(1, $conteo['advertencia']);
        $this->assertEquals(4, $conteo['total']);
    }
}
