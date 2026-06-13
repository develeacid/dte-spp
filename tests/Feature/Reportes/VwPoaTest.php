<?php

namespace Tests\Feature\Reportes;

use App\Models\CatalogoUnidadMedida;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\Presupuesto\MetaGastoTrimestral;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\Reportes\VwPoa;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VwPoaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rama_fisica_pivotea_metas_trimestrales_del_indicador(): void
    {
        $team = Team::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create(['team_id' => $team->id]);
        $nivel = MirNivel::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente',
            'team_id' => $team->id,
        ]);
        $unidad = CatalogoUnidadMedida::create(['clave' => 'PER', 'nombre' => 'Personas']);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Personas capacitadas',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => 'ascendente',
            'meta' => 400,
            'unidad_medida_id' => $unidad->id,
            'orden' => 1,
        ]);
        foreach ([1 => 100, 2 => 100, 3 => 100, 4 => 100] as $trimestre => $meta) {
            MetaPeriodo::create([
                'indicador_id' => $indicador->id,
                'periodo' => $trimestre,
                'meta_periodo' => $meta,
                'ejercicio_fiscal' => 2026,
                'activo' => true,
            ]);
        }

        $row = VwPoa::where('tipo', 'fisico')->where('concepto_id', $indicador->id)->first();

        $this->assertNotNull($row);
        $this->assertSame($programa->id, $row->programa_id);
        $this->assertSame(2026, (int) $row->ejercicio_fiscal);
        $this->assertSame('Personas capacitadas', $row->concepto);
        $this->assertSame('Personas', $row->unidad);
        $this->assertEqualsWithDelta(100, (float) $row->t1, 0.01);
        $this->assertEqualsWithDelta(100, (float) $row->t4, 0.01);
        $this->assertEqualsWithDelta(400, (float) $row->total, 0.01);
    }

    public function test_rama_financiera_pivotea_gasto_trimestral_de_la_partida(): void
    {
        $team = Team::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create(['team_id' => $team->id]);
        $partida = PartidaPresupuestal::create([
            'programa_presupuestario_id' => $programa->id,
            'clave_partida' => '21101',
            'descripcion' => 'Materiales y útiles de oficina',
            'monto_aprobado' => 50000,
            'monto_modificado' => 50000,
            'ejercicio_fiscal' => 2026,
            'team_id' => $programa->team_id,
        ]);
        foreach ([1 => 10000, 2 => 15000, 3 => 15000, 4 => 10000] as $trimestre => $monto) {
            MetaGastoTrimestral::create([
                'partida_presupuestal_id' => $partida->id,
                'trimestre' => $trimestre,
                'monto_programado' => $monto,
                'justificacion' => 'x',
            ]);
        }

        $row = VwPoa::where('tipo', 'financiero')->where('concepto_id', $partida->id)->first();

        $this->assertNotNull($row);
        $this->assertSame('21101', $row->concepto_clave);
        $this->assertSame('MXN', $row->unidad);
        $this->assertEqualsWithDelta(10000, (float) $row->t1, 0.01);
        $this->assertEqualsWithDelta(15000, (float) $row->t2, 0.01);
        $this->assertEqualsWithDelta(50000, (float) $row->total, 0.01);
    }

    public function test_filtrable_por_ejercicio_y_team(): void
    {
        $team = Team::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create(['team_id' => $team->id]);
        PartidaPresupuestal::create([
            'programa_presupuestario_id' => $programa->id,
            'clave_partida' => '21101', 'descripcion' => 'x',
            'monto_aprobado' => 1000, 'monto_modificado' => 1000,
            'ejercicio_fiscal' => 2025, 'team_id' => $programa->team_id,
        ]);

        $this->assertSame(1, VwPoa::where('ejercicio_fiscal', 2025)->where('team_id', $programa->team_id)->count());
        $this->assertSame(0, VwPoa::where('ejercicio_fiscal', 2024)->count());
    }
}
