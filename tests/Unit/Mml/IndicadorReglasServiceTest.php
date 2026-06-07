<?php

namespace Tests\Unit\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;
use App\Services\Mml\IndicadorReglasService;
use PHPUnit\Framework\TestCase;

class IndicadorReglasServiceTest extends TestCase
{
    public function test_fin_tipo_fijo_estrategico(): void
    {
        $this->assertTrue(IndicadorReglasService::esTipoFijo(TipoNivelMir::FIN));
        $this->assertEquals([TipoIndicador::ESTRATEGICO], IndicadorReglasService::tipoPermitido(TipoNivelMir::FIN));
        $this->assertEquals(TipoIndicador::ESTRATEGICO, IndicadorReglasService::tipoDefault(TipoNivelMir::FIN));
    }

    public function test_componente_tipo_editable(): void
    {
        $this->assertFalse(IndicadorReglasService::esTipoFijo(TipoNivelMir::COMPONENTE));
        $tipos = IndicadorReglasService::tipoPermitido(TipoNivelMir::COMPONENTE);
        $this->assertContains(TipoIndicador::ESTRATEGICO, $tipos);
        $this->assertContains(TipoIndicador::GESTION, $tipos);
    }

    public function test_actividad_tipo_fijo_gestion(): void
    {
        $this->assertTrue(IndicadorReglasService::esTipoFijo(TipoNivelMir::ACTIVIDAD));
        $this->assertEquals([TipoIndicador::GESTION], IndicadorReglasService::tipoPermitido(TipoNivelMir::ACTIVIDAD));
        $this->assertEquals(TipoIndicador::GESTION, IndicadorReglasService::tipoDefault(TipoNivelMir::ACTIVIDAD));
    }

    public function test_dimensiones_por_nivel(): void
    {
        $this->assertEquals(
            [DimensionIndicador::EFICACIA],
            IndicadorReglasService::dimensionesPermitidas(TipoNivelMir::FIN)
        );

        $this->assertEquals(
            [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA],
            IndicadorReglasService::dimensionesPermitidas(TipoNivelMir::PROPOSITO)
        );

        $this->assertEquals(
            [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA, DimensionIndicador::CALIDAD],
            IndicadorReglasService::dimensionesPermitidas(TipoNivelMir::COMPONENTE)
        );

        $this->assertEquals(
            [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA, DimensionIndicador::ECONOMIA],
            IndicadorReglasService::dimensionesPermitidas(TipoNivelMir::ACTIVIDAD)
        );
    }

    public function test_frecuencias_por_nivel(): void
    {
        $this->assertEquals(
            [FrecuenciaMedicion::ANUAL, FrecuenciaMedicion::BIANUAL, FrecuenciaMedicion::SEXENAL],
            IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::FIN)
        );

        $this->assertEquals(
            [FrecuenciaMedicion::SEMESTRAL, FrecuenciaMedicion::ANUAL],
            IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::PROPOSITO)
        );

        $this->assertEquals(
            [FrecuenciaMedicion::TRIMESTRAL, FrecuenciaMedicion::SEMESTRAL],
            IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::COMPONENTE)
        );

        $this->assertEquals(
            [FrecuenciaMedicion::MENSUAL, FrecuenciaMedicion::TRIMESTRAL],
            IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::ACTIVIDAD)
        );
    }

    public function test_reglas_para_nivel_retorna_estructura_completa(): void
    {
        $reglas = IndicadorReglasService::reglasParaNivel(TipoNivelMir::FIN);

        $this->assertArrayHasKey('tipo_fijo', $reglas);
        $this->assertArrayHasKey('tipo_default', $reglas);
        $this->assertArrayHasKey('tipos', $reglas);
        $this->assertArrayHasKey('dimensiones', $reglas);
        $this->assertArrayHasKey('frecuencias', $reglas);

        $this->assertTrue($reglas['tipo_fijo']);
        $this->assertEquals('estrategico', $reglas['tipo_default']);
        $this->assertEquals(['estrategico'], $reglas['tipos']);
        $this->assertEquals(['eficacia'], $reglas['dimensiones']);
        $this->assertEquals(['anual', 'bianual', 'sexenal'], $reglas['frecuencias']);
    }

    // --- Regla B9 (C-073): FIN/PROPÓSITO requieren MV de fuente externa -------

    public function test_b9_fin_y_proposito_rechazan_fuente_no_externa(): void
    {
        $this->assertNotNull(IndicadorReglasService::validarTipoFuenteMv(TipoNivelMir::FIN, 'administrativa_propia'));
        $this->assertNotNull(IndicadorReglasService::validarTipoFuenteMv(TipoNivelMir::PROPOSITO, 'evaluacion_externa'));
    }

    public function test_b9_fin_y_proposito_aceptan_fuente_externa(): void
    {
        $this->assertNull(IndicadorReglasService::validarTipoFuenteMv(TipoNivelMir::FIN, 'externa'));
        $this->assertNull(IndicadorReglasService::validarTipoFuenteMv(TipoNivelMir::PROPOSITO, 'externa'));
    }

    public function test_b9_componente_y_actividad_aceptan_cualquier_fuente(): void
    {
        $this->assertNull(IndicadorReglasService::validarTipoFuenteMv(TipoNivelMir::COMPONENTE, 'administrativa_propia'));
        $this->assertNull(IndicadorReglasService::validarTipoFuenteMv(TipoNivelMir::ACTIVIDAD, 'evaluacion_externa'));
    }

    public function test_b9_fuente_null_no_bloquea(): void
    {
        $this->assertNull(IndicadorReglasService::validarTipoFuenteMv(TipoNivelMir::FIN, null));
        $this->assertNull(IndicadorReglasService::validarTipoFuenteMv(TipoNivelMir::PROPOSITO, ''));
    }
}
