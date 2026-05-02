<?php

namespace Tests\Feature\Presupuesto;

use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\MetaGastoTrimestral;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelosPresupuestoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    private PartidaPresupuestal $partida;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);

        $this->partida = PartidaPresupuestal::create([
            'programa_presupuestario_id' => $this->programa->id,
            'clave_partida' => '1000',
            'descripcion' => 'Servicios Personales',
            'monto_aprobado' => 1000000.00,
            'ejercicio_fiscal' => 2026,
            'team_id' => $this->user->currentTeam->id,
            'registrado_por' => $this->user->id,
        ]);
    }

    // --- PartidaPresupuestal ---

    public function test_partida_pertenece_a_programa(): void
    {
        $this->assertEquals($this->programa->id, $this->partida->programa->id);
    }

    public function test_partida_pertenece_a_team(): void
    {
        $this->assertEquals($this->user->currentTeam->id, $this->partida->team->id);
    }

    public function test_partida_tiene_registrador(): void
    {
        $this->assertEquals($this->user->id, $this->partida->registrador->id);
    }

    public function test_partida_monto_efectivo_usa_aprobado_sin_modificado(): void
    {
        $this->assertEquals(1000000.00, $this->partida->monto_efectivo);
    }

    public function test_partida_monto_efectivo_usa_modificado_cuando_existe(): void
    {
        $this->partida->update(['monto_modificado' => 1200000.00]);

        $this->assertEquals(1200000.00, $this->partida->fresh()->monto_efectivo);
    }

    public function test_partida_porcentaje_ejercido_sin_avances(): void
    {
        $this->assertEquals(0, $this->partida->porcentaje_ejercido);
    }

    public function test_partida_porcentaje_ejercido_con_avances(): void
    {
        AvanceFinanciero::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_comprometido' => 300000,
            'monto_devengado' => 280000,
            'monto_pagado' => 250000,
            'registrado_por' => $this->user->id,
        ]);

        // 250000 / 1000000 * 100 = 25%
        $this->assertEquals(25.00, $this->partida->fresh()->porcentaje_ejercido);
    }

    public function test_partida_scope_para_team(): void
    {
        $result = PartidaPresupuestal::paraTeam($this->user->currentTeam->id)->get();

        $this->assertCount(1, $result);
        $this->assertEquals($this->partida->id, $result->first()->id);
    }

    public function test_partida_scope_para_ejercicio(): void
    {
        $result = PartidaPresupuestal::paraEjercicio(2026)->get();
        $this->assertCount(1, $result);

        $result = PartidaPresupuestal::paraEjercicio(2025)->get();
        $this->assertCount(0, $result);
    }

    public function test_partida_unique_constraint(): void
    {
        $this->expectException(QueryException::class);

        PartidaPresupuestal::create([
            'programa_presupuestario_id' => $this->programa->id,
            'clave_partida' => '1000', // Misma clave
            'descripcion' => 'Duplicada',
            'monto_aprobado' => 500000,
            'ejercicio_fiscal' => 2026, // Mismo ejercicio
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_programa_tiene_partidas_presupuestales(): void
    {
        $this->assertCount(1, $this->programa->partidasPresupuestales);
        $this->assertEquals($this->partida->id, $this->programa->partidasPresupuestales->first()->id);
    }

    public function test_partida_casts_montos_correctamente(): void
    {
        $partida = $this->partida->fresh();

        $this->assertIsString($partida->monto_aprobado); // decimal:2 cast returns string
        $this->assertEquals('1000000.00', $partida->monto_aprobado);
    }

    // --- AvanceFinanciero ---

    public function test_avance_financiero_pertenece_a_partida(): void
    {
        $avance = AvanceFinanciero::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_comprometido' => 300000,
            'monto_devengado' => 280000,
            'monto_pagado' => 250000,
            'registrado_por' => $this->user->id,
        ]);

        $this->assertEquals($this->partida->id, $avance->partida->id);
        $this->assertEquals($this->user->id, $avance->registrador->id);
    }

    public function test_avance_financiero_unique_por_trimestre(): void
    {
        AvanceFinanciero::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_comprometido' => 300000,
            'monto_devengado' => 280000,
            'monto_pagado' => 250000,
            'registrado_por' => $this->user->id,
        ]);

        $this->expectException(QueryException::class);

        AvanceFinanciero::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1, // Mismo trimestre
            'monto_comprometido' => 100000,
            'monto_devengado' => 90000,
            'monto_pagado' => 80000,
            'registrado_por' => $this->user->id,
        ]);
    }

    public function test_avance_check_pagado_le_devengado(): void
    {
        $this->expectException(QueryException::class);

        AvanceFinanciero::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_comprometido' => 300000,
            'monto_devengado' => 200000,
            'monto_pagado' => 250000, // pagado > devengado → violates CHECK
            'registrado_por' => $this->user->id,
        ]);
    }

    public function test_avance_check_devengado_le_comprometido(): void
    {
        $this->expectException(QueryException::class);

        AvanceFinanciero::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_comprometido' => 200000,
            'monto_devengado' => 300000, // devengado > comprometido → violates CHECK
            'monto_pagado' => 100000,
            'registrado_por' => $this->user->id,
        ]);
    }

    public function test_avance_check_trimestre_rango(): void
    {
        $this->expectException(QueryException::class);

        AvanceFinanciero::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 5, // Fuera de rango
            'monto_comprometido' => 100000,
            'monto_devengado' => 90000,
            'monto_pagado' => 80000,
            'registrado_por' => $this->user->id,
        ]);
    }

    public function test_partida_tiene_avances_financieros(): void
    {
        AvanceFinanciero::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_comprometido' => 300000,
            'monto_devengado' => 280000,
            'monto_pagado' => 250000,
            'registrado_por' => $this->user->id,
        ]);

        $this->assertCount(1, $this->partida->fresh()->avancesFinancieros);
    }

    // --- MetaGastoTrimestral ---

    public function test_meta_gasto_pertenece_a_partida(): void
    {
        $meta = MetaGastoTrimestral::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_programado' => 200000,
        ]);

        $this->assertEquals($this->partida->id, $meta->partida->id);
    }

    public function test_meta_gasto_check_trimestre_rango(): void
    {
        $this->expectException(QueryException::class);

        MetaGastoTrimestral::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 0, // Fuera de rango
            'monto_programado' => 200000,
        ]);
    }

    public function test_meta_gasto_check_monto_positivo(): void
    {
        $this->expectException(QueryException::class);

        MetaGastoTrimestral::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_programado' => -100, // Negativo → violates CHECK
        ]);
    }

    public function test_partida_tiene_metas_gasto(): void
    {
        MetaGastoTrimestral::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_programado' => 200000,
        ]);

        $this->assertCount(1, $this->partida->fresh()->metasGasto);
    }

    public function test_meta_gasto_unique_por_trimestre(): void
    {
        MetaGastoTrimestral::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_programado' => 200000,
        ]);

        $this->expectException(QueryException::class);

        MetaGastoTrimestral::create([
            'partida_presupuestal_id' => $this->partida->id,
            'trimestre' => 1,
            'monto_programado' => 300000,
        ]);
    }
}
