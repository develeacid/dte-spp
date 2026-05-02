<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Services\Mml\MirParserService;
use Tests\TestCase;

class MirParserTest extends TestCase
{
    private MirParserService $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MirParserService;
    }

    public function test_parse_markdown_extracts_header_metadata(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $result = $this->parser->fromMarkdown($content);

        $this->assertInstanceOf(ImportedMirData::class, $result);
        $this->assertEquals('Programa de Mejora Educativa', $result->nombre);
        $this->assertEquals('PME-2026', $result->clave);
        $this->assertEquals(2026, $result->ejercicioFiscal);
    }

    public function test_parse_markdown_extracts_all_niveles(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $result = $this->parser->fromMarkdown($content);

        $this->assertCount(5, $result->niveles);
        $this->assertEquals('fin', $result->niveles[0]['tipo_nivel']);
        $this->assertEquals('proposito', $result->niveles[1]['tipo_nivel']);
        $this->assertEquals('componente', $result->niveles[2]['tipo_nivel']);
        $this->assertEquals('actividad', $result->niveles[3]['tipo_nivel']);
        $this->assertEquals('actividad', $result->niveles[4]['tipo_nivel']);
    }

    public function test_parse_markdown_normalizes_accented_tipo_nivel(): void
    {
        $md = <<<'MD'
# Programa: Test (TST)
## Ejercicio: 2026

| Nivel | Resumen Narrativo | Indicador | Fórmula | Tipo | Dimensión | Frecuencia | Medio Verificación | Supuestos |
|---|---|---|---|---|---|---|---|---|
| Propósito | Texto de propósito | Ind. cobertura | A/B*100 | Estratégico | Eficacia | Anual | Informe | Supuesto1 |
MD;

        $result = $this->parser->fromMarkdown($md);

        $this->assertEquals('proposito', $result->niveles[0]['tipo_nivel']);
    }

    public function test_parse_markdown_extracts_indicadores(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $result = $this->parser->fromMarkdown($content);

        $finIndicadores = $result->niveles[0]['indicadores'];
        $this->assertCount(1, $finIndicadores);
        $this->assertEquals('Índice de desarrollo humano', $finIndicadores[0]['nombre']);
        $this->assertEquals('(Salud + Educación + Ingreso) / 3', $finIndicadores[0]['formula_texto']);
        $this->assertEquals('estrategico', $finIndicadores[0]['tipo']);
        $this->assertEquals('eficacia', $finIndicadores[0]['dimension']);
    }

    public function test_parse_markdown_extracts_medios_verificacion(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $result = $this->parser->fromMarkdown($content);

        $medios = $result->niveles[0]['indicadores'][0]['medios'];
        $this->assertCount(1, $medios);
        $this->assertEquals('Informe PNUD', $medios[0]['nombre']);
    }

    public function test_parse_markdown_assigns_componente_idx_to_actividades(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $result = $this->parser->fromMarkdown($content);

        // Componente is at index 2 (orden 3), activities should link to componente_idx 0
        $this->assertNull($result->niveles[2]['componente_idx']); // componente itself
        $this->assertEquals(0, $result->niveles[3]['componente_idx']); // actividad 1
        $this->assertEquals(0, $result->niveles[4]['componente_idx']); // actividad 2
    }

    public function test_parse_csv_extracts_all_niveles(): void
    {
        $result = $this->parser->fromCsv(base_path('tests/fixtures/mir-sample.csv'));

        $this->assertInstanceOf(ImportedMirData::class, $result);
        $this->assertCount(5, $result->niveles);
        $this->assertNull($result->nombre); // CSV has no header metadata
    }

    public function test_parse_csv_handles_missing_fields(): void
    {
        $result = $this->parser->fromCsv(base_path('tests/fixtures/mir-sample.csv'));

        // Row 4 (Actividad - Realizar diagnóstico) has empty formula, tipo, dimension
        $actividadInd = $result->niveles[3]['indicadores'][0];
        $this->assertNull($actividadInd['formula_texto']);
        $this->assertNull($actividadInd['tipo']);
        $this->assertNull($actividadInd['dimension']);
        $this->assertEmpty($actividadInd['medios']); // no medio
    }

    public function test_to_array_and_from_array_round_trip(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $original = $this->parser->fromMarkdown($content);

        $array = $original->toArray();
        $restored = ImportedMirData::fromArray($array);

        $this->assertEquals($original->nombre, $restored->nombre);
        $this->assertEquals($original->clave, $restored->clave);
        $this->assertEquals($original->ejercicioFiscal, $restored->ejercicioFiscal);
        $this->assertCount(count($original->niveles), $restored->niveles);
    }

    public function test_parse_markdown_with_empty_content(): void
    {
        $result = $this->parser->fromMarkdown('');

        $this->assertEmpty($result->niveles);
        $this->assertNull($result->nombre);
    }
}
