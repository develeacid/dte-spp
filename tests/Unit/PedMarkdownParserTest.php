<?php

namespace Tests\Unit;

use App\Services\PedMarkdownParser;
use PHPUnit\Framework\TestCase;

class PedMarkdownParserTest extends TestCase
{
    protected PedMarkdownParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new PedMarkdownParser;
    }

    public function test_parsea_plan_basico(): void
    {
        $content = '# Plan Estatal de Desarrollo 2025-2030';

        $result = $this->parser->parse($content);

        $this->assertTrue($result['valid']);
        $this->assertEquals('Plan Estatal de Desarrollo 2025-2030', $result['tree']['nombre']);
        $this->assertEquals(2025, $result['tree']['periodo_inicio']);
        $this->assertEquals(2030, $result['tree']['periodo_fin']);
    }

    public function test_parsea_jerarquia_completa(): void
    {
        $content = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Bienestar Social
### Tema 1.1: Educación
#### Objetivo 1.1.1: Garantizar acceso
##### Estrategia 1.1.1.1: Ampliar cobertura
- Línea de Acción 1.1.1.1.1: Construir escuelas
- Línea de Acción 1.1.1.1.2: Becas educativas

## Eje 2: Economía
### Tema 2.1: Empleo
#### Objetivo 2.1.1: Fomentar empleos
##### Estrategia 2.1.1.1: Apoyar PyMEs
- Línea de Acción 2.1.1.1.1: Financiamiento
MD;

        $result = $this->parser->parse($content);

        $this->assertTrue($result['valid']);
        $this->assertCount(2, $result['tree']['ejes']);

        // Eje 1
        $this->assertEquals('1', $result['tree']['ejes'][0]['numero']);
        $this->assertEquals('Bienestar Social', $result['tree']['ejes'][0]['nombre']);
        $this->assertCount(1, $result['tree']['ejes'][0]['temas']);

        // Tema 1.1
        $this->assertEquals('1.1', $result['tree']['ejes'][0]['temas'][0]['numero']);

        // Objetivo 1.1.1
        $this->assertEquals('1.1.1', $result['tree']['ejes'][0]['temas'][0]['objetivos'][0]['clave']);

        // Estrategia con 2 líneas
        $estrategia = $result['tree']['ejes'][0]['temas'][0]['objetivos'][0]['estrategias'][0];
        $this->assertCount(2, $estrategia['lineas']);
    }

    public function test_detecta_error_sin_plan(): void
    {
        $content = '## Eje 1: Sin plan';

        $result = $this->parser->parse($content);

        $this->assertFalse($result['valid']);
    }

    public function test_detecta_linea_sin_estrategia(): void
    {
        $content = <<<'MD'
# Plan Test
## Eje 1: Test
- Línea suelta sin estrategia
MD;

        $result = $this->parser->parse($content);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_estadisticas_correctas(): void
    {
        $content = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Bienestar
### Tema 1.1: Educación
#### Objetivo 1.1.1: Garantizar acceso
##### Estrategia 1.1.1.1: Ampliar cobertura
- Línea de Acción 1.1.1.1.1: Construir escuelas
- Línea de Acción 1.1.1.1.2: Becas

### Tema 1.2: Salud
#### Objetivo 1.2.1: Acceso salud
##### Estrategia 1.2.1.1: Infraestructura
- Línea de Acción 1.2.1.1.1: Centros de salud
MD;

        $result = $this->parser->parse($content);
        $stats = $this->parser->getStats($result);

        $this->assertEquals(1, $stats['plan']);
        $this->assertEquals(1, $stats['ejes']);
        $this->assertEquals(2, $stats['temas']);
        $this->assertEquals(2, $stats['objetivos']);
        $this->assertEquals(2, $stats['estrategias']);
        $this->assertEquals(3, $stats['lineas']);
    }

    public function test_ignora_lineas_vacias(): void
    {
        $content = <<<'MD'
# Plan Test

## Eje 1: Test

### Tema 1.1: Test
MD;

        $result = $this->parser->parse($content);

        $this->assertTrue($result['valid']);
        $this->assertCount(1, $result['tree']['ejes']);
    }

    public function test_extrae_periodo_con_guion_largo(): void
    {
        $content = '# Plan Estatal de Desarrollo 2025–2030';

        $result = $this->parser->parse($content);

        $this->assertEquals(2025, $result['tree']['periodo_inicio']);
        $this->assertEquals(2030, $result['tree']['periodo_fin']);
    }
}
