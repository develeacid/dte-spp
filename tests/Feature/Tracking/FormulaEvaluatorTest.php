<?php

namespace Tests\Feature\Tracking;

use App\Services\Tracking\FormulaEvaluatorService;
use Tests\TestCase;

class FormulaEvaluatorTest extends TestCase
{
    private FormulaEvaluatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FormulaEvaluatorService;
    }

    public function test_evalua_formula_basica(): void
    {
        $resultado = $this->service->evaluar('(A/B) * 100', ['A' => 50, 'B' => 200]);

        $this->assertNotNull($resultado);
        $this->assertEquals(25.0, $resultado);
    }

    public function test_evalua_formula_con_x(): void
    {
        $resultado = $this->service->evaluar('(A/B) x 100', ['A' => 50, 'B' => 200]);

        $this->assertNotNull($resultado);
        $this->assertEquals(25.0, $resultado);
    }

    public function test_formula_division_por_cero(): void
    {
        $resultado = $this->service->evaluar('A/B', ['A' => 50, 'B' => 0]);

        $this->assertNull($resultado);
    }

    public function test_formula_invalida(): void
    {
        $resultado = $this->service->evaluar('INVALID!!!', ['A' => 1]);

        $this->assertNull($resultado);
    }
}
