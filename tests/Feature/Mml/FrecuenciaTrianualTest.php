<?php

namespace Tests\Feature\Mml;

use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoNivelMir;
use App\Services\Mml\IndicadorReglasService;
use Tests\TestCase;

class FrecuenciaTrianualTest extends TestCase
{
    public function test_trianual_existe_con_orden_entre_bianual_y_sexenal(): void
    {
        $this->assertSame('trianual', FrecuenciaMedicion::TRIANUAL->value);
        $this->assertSame('Trianual', FrecuenciaMedicion::TRIANUAL->label());
        $this->assertGreaterThan(FrecuenciaMedicion::BIANUAL->orden(), FrecuenciaMedicion::TRIANUAL->orden());
        $this->assertLessThan(FrecuenciaMedicion::SEXENAL->orden(), FrecuenciaMedicion::TRIANUAL->orden());
    }

    public function test_frecuencias_permitidas_ampliadas_por_nivel(): void
    {
        $this->assertContains(FrecuenciaMedicion::TRIANUAL, IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::FIN));
        $this->assertContains(FrecuenciaMedicion::TRIANUAL, IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::PROPOSITO));
        $this->assertContains(FrecuenciaMedicion::ANUAL, IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::COMPONENTE));
        $this->assertContains(FrecuenciaMedicion::SEMESTRAL, IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::ACTIVIDAD));
    }
}
