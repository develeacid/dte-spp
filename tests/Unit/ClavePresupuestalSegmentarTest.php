<?php

namespace Tests\Unit;

use App\Services\Presupuesto\ClavePresupuestalService;
use PHPUnit\Framework\TestCase;

class ClavePresupuestalSegmentarTest extends TestCase
{
    public function test_segmenta_clave_sefip_de_32_en_cuatro_bloques(): void
    {
        // Ejemplo oficial: Admin 101001 · Programática 14402000001 · Objeto 313303 · Financiamiento AEBAA0125.
        $r = ClavePresupuestalService::segmentar('10100114402000001313303AEBAA0125');

        $this->assertSame([
            'grupo' => 1,
            'unidad_responsable' => 1,
            'unidad_ejecutora' => 1,
            'programa_clave' => 144,
            'subprograma' => 2,
            'proyecto' => 0,
            'actividad' => 1,
        ], $r['segmentos']);

        $this->assertSame('313303', $r['informativos']['objeto_del_gasto']);
        $this->assertSame('AEBAA0125', $r['informativos']['financiamiento']);
        $this->assertSame([], $r['avisos']);
    }

    public function test_financiamiento_alfanumerico_se_preserva(): void
    {
        $r = ClavePresupuestalService::segmentar('10100114402000001313303AEBAA0125');

        $this->assertSame('AEBAA0125', $r['informativos']['financiamiento']);
    }

    public function test_limpia_separadores_espacios_y_guiones(): void
    {
        $r = ClavePresupuestalService::segmentar('101001 14402000001-313303 AEBAA0125');

        $this->assertSame(144, $r['segmentos']['programa_clave']);
        $this->assertSame([], $r['avisos']);
    }

    public function test_avisa_si_longitud_distinta_de_32(): void
    {
        $r = ClavePresupuestalService::segmentar('10100114402000001');

        $this->assertNotEmpty($r['avisos']);
        $this->assertStringContainsString('32', implode(' ', $r['avisos']));
        // Aun así segmenta lo que puede (no bloquea).
        $this->assertSame(1, $r['segmentos']['grupo']);
    }

    public function test_avisa_si_admin_programatica_no_numerico(): void
    {
        $r = ClavePresupuestalService::segmentar('ABCDEF14402000001313303AEBAA0125');

        $this->assertNotEmpty($r['avisos']);
        $this->assertStringContainsStringIgnoringCase('numérico', implode(' ', $r['avisos']));
    }
}
