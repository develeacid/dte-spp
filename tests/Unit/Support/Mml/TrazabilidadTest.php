<?php

namespace Tests\Unit\Support\Mml;

use App\Enums\TipoNivelMir;
use App\Support\Mml\Trazabilidad;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TrazabilidadTest extends TestCase
{
    public function test_arma_clave_para_fin(): void
    {
        $t = new Trazabilidad('EDU-002', TipoNivelMir::FIN, null, null);

        $this->assertSame('EDU-002-f', $t->clave());
        $this->assertSame('EDU-002', $t->programa());
        $this->assertSame('Fin', $t->tipoNivelLabel());
    }

    public function test_arma_clave_para_proposito(): void
    {
        $t = new Trazabilidad('EDU-002', TipoNivelMir::PROPOSITO, null, null);

        $this->assertSame('EDU-002-p', $t->clave());
    }

    public function test_arma_clave_para_componente(): void
    {
        $t = new Trazabilidad('ISM-001', TipoNivelMir::COMPONENTE, 3, null);

        $this->assertSame('ISM-001-c3', $t->clave());
        $this->assertSame('C3', $t->nivelCorto());
        $this->assertSame('Componente 3', $t->nivel());
    }

    public function test_arma_clave_para_actividad_con_componente_padre(): void
    {
        $t = new Trazabilidad('EDU-002', TipoNivelMir::ACTIVIDAD, 1, 2);

        $this->assertSame('EDU-002-c1-a2', $t->clave());
        $this->assertSame('A2 · C1', $t->nivelCorto());
        $this->assertSame('Actividad 2 del Componente 1', $t->nivel());
    }

    public function test_actividad_sin_componente_padre_cae_a_c_interrogante(): void
    {
        Log::shouldReceive('warning')->once();

        $t = new Trazabilidad('EDU-002', TipoNivelMir::ACTIVIDAD, null, 2);

        $this->assertSame('EDU-002-c?-a2', $t->clave());
    }

    public function test_to_array_expone_componentes_serializables(): void
    {
        $t = new Trazabilidad('EDU-002', TipoNivelMir::ACTIVIDAD, 1, 2);

        $this->assertSame([
            'clave' => 'EDU-002-c1-a2',
            'programa' => 'EDU-002',
            'tipo_nivel' => 'ACTIVIDAD',
            'componente_orden' => 1,
            'actividad_orden' => 2,
        ], $t->toArray());
    }
}
