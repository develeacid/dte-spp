<?php

namespace Tests\Unit;

use App\Models\ProgramaPresupuestario;
use PHPUnit\Framework\TestCase;

class ClavePresupuestalAccessorTest extends TestCase
{
    private function programaCompleto(): ProgramaPresupuestario
    {
        // Ejemplo oficial SEFIP: Admin 101001 (Grupo 1, UR 01, UE 001),
        // Programática 14402000001 (Programa 144, Subprog 02, Proyecto 000, Actividad 001).
        return new ProgramaPresupuestario([
            'grupo' => 1,
            'unidad_responsable' => 1,
            'unidad_ejecutora' => 1,
            'programa_clave' => 144,
            'subprograma' => 2,
            'proyecto' => 0,
            'actividad' => 1,
        ]);
    }

    public function test_compone_clave_canonica_con_padding_por_segmento(): void
    {
        $programa = $this->programaCompleto();

        $this->assertSame('10100114402000001', $programa->clave_presupuestal_canonica);
    }

    public function test_clave_canonica_completa_true_cuando_estan_los_siete_campos(): void
    {
        $this->assertTrue($this->programaCompleto()->claveCanonicaCompleta());
    }

    public function test_accessor_null_si_falta_algun_campo(): void
    {
        $programa = $this->programaCompleto();
        $programa->actividad = null;

        $this->assertNull($programa->clave_presupuestal_canonica);
        $this->assertFalse($programa->claveCanonicaCompleta());
    }

    public function test_cero_es_valido_no_se_trata_como_nulo(): void
    {
        // proyecto = 0 es un segmento válido (no debe anular la clave).
        $programa = $this->programaCompleto();

        $this->assertTrue($programa->claveCanonicaCompleta());
        $this->assertStringContainsString('000', $programa->clave_presupuestal_canonica);
    }
}
