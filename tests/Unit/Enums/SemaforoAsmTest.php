<?php

namespace Tests\Unit\Enums;

use App\Enums\SemaforoAsm;
use App\Enums\StatusAsm;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class SemaforoAsmTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-04-24 12:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function returns_cumplido_cuando_status_es_cumplido_independiente_de_la_fecha(): void
    {
        $resultado = SemaforoAsm::calcular(CarbonImmutable::parse('2025-01-01'), StatusAsm::CUMPLIDO);
        $this->assertSame(SemaforoAsm::CUMPLIDO, $resultado);
    }

    /** @test */
    public function returns_vencido_cuando_fecha_compromiso_es_pasada_y_status_no_es_cumplido(): void
    {
        $resultado = SemaforoAsm::calcular(CarbonImmutable::parse('2026-04-20'), StatusAsm::EN_PROCESO);
        $this->assertSame(SemaforoAsm::VENCIDO, $resultado);
    }

    /** @test */
    public function returns_rojo_cuando_faltan_menos_de_7_dias(): void
    {
        $resultado = SemaforoAsm::calcular(CarbonImmutable::parse('2026-04-27'), StatusAsm::PENDIENTE);
        $this->assertSame(SemaforoAsm::ROJO, $resultado);
    }

    /** @test */
    public function returns_rojo_en_6_dias_exactos_limite_inferior(): void
    {
        $resultado = SemaforoAsm::calcular(CarbonImmutable::parse('2026-04-30'), StatusAsm::PENDIENTE);
        $this->assertSame(SemaforoAsm::ROJO, $resultado);
    }

    /** @test */
    public function returns_amarillo_en_7_dias_exactos_limite(): void
    {
        $resultado = SemaforoAsm::calcular(CarbonImmutable::parse('2026-05-01'), StatusAsm::PENDIENTE);
        $this->assertSame(SemaforoAsm::AMARILLO, $resultado);
    }

    /** @test */
    public function returns_amarillo_en_30_dias_exactos_limite_superior(): void
    {
        $resultado = SemaforoAsm::calcular(CarbonImmutable::parse('2026-05-24'), StatusAsm::PENDIENTE);
        $this->assertSame(SemaforoAsm::AMARILLO, $resultado);
    }

    /** @test */
    public function returns_verde_en_31_dias(): void
    {
        $resultado = SemaforoAsm::calcular(CarbonImmutable::parse('2026-05-25'), StatusAsm::PENDIENTE);
        $this->assertSame(SemaforoAsm::VERDE, $resultado);
    }

    /** @test */
    public function returns_verde_en_60_dias(): void
    {
        $resultado = SemaforoAsm::calcular(CarbonImmutable::parse('2026-06-23'), StatusAsm::PENDIENTE);
        $this->assertSame(SemaforoAsm::VERDE, $resultado);
    }

    /** @test */
    public function cumplido_gana_sobre_vencido_cuando_la_fecha_ya_paso(): void
    {
        $resultado = SemaforoAsm::calcular(
            CarbonImmutable::parse('2024-01-01'),
            StatusAsm::CUMPLIDO,
        );
        $this->assertSame(SemaforoAsm::CUMPLIDO, $resultado);
    }

    /** @test */
    public function returns_rojo_cuando_fecha_compromiso_es_hoy_mismo(): void
    {
        $resultado = SemaforoAsm::calcular(
            CarbonImmutable::parse('2026-04-24'),
            StatusAsm::PENDIENTE,
        );
        $this->assertSame(SemaforoAsm::ROJO, $resultado);
    }
}
