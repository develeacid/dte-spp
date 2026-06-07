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
        $this->diagnostico = new MirDiagnosticoService;
    }

    public function test_complete_mir_has_no_critical_gaps(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $data = (new MirParserService)->fromMarkdown($content);

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

    public function test_rojo_min_cero_genera_hallazgo_b6(): void
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
                        'sentido' => 'ascendente',
                        'linea_base' => 0,
                        'meta' => 80,
                        'clave_unidad' => 'PCT',
                        'rangos_semaforo' => [
                            'rango_verde_min' => 80, 'rango_verde_max' => 100,
                            'rango_amarillo_min' => 60, 'rango_amarillo_max' => 79,
                            'rango_rojo_min' => 0, 'rango_rojo_max' => 59,
                        ],
                        'variables' => [],
                        'medios' => [['nombre' => 'Informe', 'fuente' => null]],
                    ],
                ],
            ],
        ]);

        $gaps = $this->diagnostico->diagnosticar($data);
        $reglasGaps = array_filter(
            $gaps,
            fn ($g) => $g['campo'] === 'rangos_semaforo' && $g['severidad'] === 'advertencia'
        );

        $this->assertNotEmpty($reglasGaps);
        $mensajes = implode(' ', array_column($reglasGaps, 'mensaje'));
        $this->assertStringContainsString('rojo no puede iniciar en cero', $mensajes);
    }

    public function test_meta_fuera_de_verde_genera_hallazgo_b3(): void
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
                        'sentido' => 'ascendente',
                        'linea_base' => 0,
                        'meta' => 50, // fuera del rango verde [80, 100]
                        'rangos_semaforo' => [
                            'rango_verde_min' => 80, 'rango_verde_max' => 100,
                            'rango_amarillo_min' => 60, 'rango_amarillo_max' => 79,
                            'rango_rojo_min' => 1, 'rango_rojo_max' => 59,
                        ],
                        'variables' => [],
                        'medios' => [['nombre' => 'Informe', 'fuente' => null]],
                    ],
                ],
            ],
        ]);

        $gaps = $this->diagnostico->diagnosticar($data);
        $reglasGaps = array_filter(
            $gaps,
            fn ($g) => $g['campo'] === 'rangos_semaforo' && $g['severidad'] === 'advertencia'
        );

        $this->assertNotEmpty($reglasGaps);
        $mensajes = implode(' ', array_column($reglasGaps, 'mensaje'));
        $this->assertStringContainsString('rango verde', $mensajes);
    }

    public function test_mv_anual_en_indicador_trimestral_genera_hallazgo_b7(): void
    {
        $data = new ImportedMirData(niveles: [
            [
                'tipo_nivel' => 'componente',
                'resumen_narrativo' => 'Componente algo',
                'supuestos' => 'Algo',
                'orden' => 1,
                'indicadores' => [
                    [
                        'nombre' => 'Ind1',
                        'formula_texto' => 'A/B',
                        'tipo' => 'gestion',
                        'dimension' => 'eficacia',
                        'frecuencia' => 'trimestral',
                        'sentido' => 'ascendente',
                        'linea_base' => 0,
                        'meta' => 100,
                        'rangos_semaforo' => null,
                        'variables' => [],
                        // MV con frecuencia anual (orden 4) > indicador trimestral (orden 2)
                        'medios' => [['nombre' => 'Informe', 'fuente' => null, 'frecuencia' => 'anual']],
                    ],
                ],
            ],
        ]);

        $gaps = $this->diagnostico->diagnosticar($data);
        $b7Gaps = array_filter(
            $gaps,
            fn ($g) => $g['campo'] === 'medios' && $g['severidad'] === 'advertencia'
        );

        $this->assertNotEmpty($b7Gaps);
        $this->assertStringContainsString('frecuencia', array_values($b7Gaps)[0]['mensaje']);
    }

    public function test_clean_import_no_nuevos_hallazgos_de_reglas(): void
    {
        $data = new ImportedMirData(niveles: [
            [
                'tipo_nivel' => 'componente',
                'resumen_narrativo' => 'Componente algo',
                'supuestos' => 'Algo',
                'orden' => 1,
                'indicadores' => [
                    [
                        'nombre' => 'Ind1',
                        'formula_texto' => 'A/B',
                        'tipo' => 'gestion',
                        'dimension' => 'eficacia',
                        'frecuencia' => 'trimestral',
                        'sentido' => 'ascendente',
                        'linea_base' => 0,
                        'meta' => 90,
                        'clave_unidad' => 'PCT',
                        'rangos_semaforo' => [
                            'rango_verde_min' => 80, 'rango_verde_max' => 100,
                            'rango_amarillo_min' => 60, 'rango_amarillo_max' => 79,
                            'rango_rojo_min' => 1, 'rango_rojo_max' => 59,
                        ],
                        'variables' => [],
                        // MV trimestral (orden 2) <= indicador trimestral (orden 2): ok
                        'medios' => [['nombre' => 'Informe', 'fuente' => null, 'frecuencia' => 'trimestral']],
                    ],
                ],
            ],
        ]);

        $gaps = $this->diagnostico->diagnosticar($data);
        $reglasGaps = array_filter(
            $gaps,
            fn ($g) => in_array($g['campo'], ['rangos_semaforo', 'medios'], true) && $g['severidad'] === 'advertencia'
        );

        $this->assertEmpty($reglasGaps);
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
