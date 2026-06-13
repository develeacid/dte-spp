<?php

namespace Tests\Unit;

use App\Models\Mml\PoblacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoblacionProgramaModelTest extends TestCase
{
    use RefreshDatabase;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $user->currentTeam->id,
        ]);
    }

    public function test_puede_crear_poblacion_programa(): void
    {
        $poblacion = PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Niños',
            'referencia_cantidad' => 100000,
            'potencial_cantidad' => 20000,
            'objetivo_cantidad' => 5000,
            'anio_ejercicio' => 2026,
        ]);

        $this->assertDatabaseHas('poblaciones_programa', [
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Niños',
            'referencia_cantidad' => 100000,
        ]);
    }

    public function test_check_constraint_rechaza_objetivo_mayor_que_potencial(): void
    {
        $this->expectException(QueryException::class);

        PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Familias',
            'referencia_cantidad' => 10000,
            'potencial_cantidad' => 5000,
            'objetivo_cantidad' => 6000,
            'anio_ejercicio' => 2026,
        ]);
    }

    public function test_check_constraint_rechaza_potencial_mayor_que_referencia(): void
    {
        $this->expectException(QueryException::class);

        PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Familias',
            'referencia_cantidad' => 5000,
            'potencial_cantidad' => 6000,
            'objetivo_cantidad' => 3000,
            'anio_ejercicio' => 2026,
        ]);
    }

    public function test_check_constraint_rechaza_cantidades_cero(): void
    {
        $this->expectException(QueryException::class);

        PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Familias',
            'referencia_cantidad' => 10000,
            'potencial_cantidad' => 5000,
            'objetivo_cantidad' => 0,
            'anio_ejercicio' => 2026,
        ]);
    }

    public function test_unique_constraint_por_programa_y_anio(): void
    {
        PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Niños',
            'referencia_cantidad' => 100000,
            'potencial_cantidad' => 20000,
            'objetivo_cantidad' => 5000,
            'anio_ejercicio' => 2026,
        ]);

        $this->expectException(QueryException::class);

        PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Familias',
            'referencia_cantidad' => 50000,
            'potencial_cantidad' => 10000,
            'objetivo_cantidad' => 3000,
            'anio_ejercicio' => 2026,
        ]);
    }

    public function test_relacion_programa(): void
    {
        $poblacion = PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'MIPYMES',
            'referencia_cantidad' => 50000,
            'potencial_cantidad' => 10000,
            'objetivo_cantidad' => 2000,
            'anio_ejercicio' => 2026,
        ]);

        $this->assertEquals($this->programa->id, $poblacion->programa->id);
        $this->assertNotNull($this->programa->fresh()->poblacion);
    }

    private function poblacionConObjetivo(int $objetivo): PoblacionPrograma
    {
        return PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Personas',
            'referencia_cantidad' => 100000,
            'potencial_cantidad' => 50000,
            'objetivo_cantidad' => $objetivo,
            'anio_ejercicio' => 2026,
        ]);
    }

    public function test_cobertura_y_brecha_null_si_no_hay_atendida(): void
    {
        $p = $this->poblacionConObjetivo(5000);

        $this->assertNull($p->cobertura_atendida);
        $this->assertNull($p->brecha_atendida);
    }

    public function test_subcobertura_atendida_menor_que_objetivo(): void
    {
        $p = $this->poblacionConObjetivo(5000);
        $p->update(['atendida_cantidad' => 4000]);

        $this->assertEquals(80.0, $p->cobertura_atendida);
        $this->assertEquals(1000, $p->brecha_atendida); // positivo = subcobertura
    }

    public function test_sobrecobertura_atendida_mayor_que_objetivo(): void
    {
        $p = $this->poblacionConObjetivo(5000);
        $p->update(['atendida_cantidad' => 6000]);

        $this->assertEquals(120.0, $p->cobertura_atendida);
        $this->assertEquals(-1000, $p->brecha_atendida); // negativo = sobrecobertura
    }

    public function test_atendida_puede_superar_objetivo_sin_violar_check(): void
    {
        // El CHECK chk_embudo_logico NO incluye atendida.
        $p = $this->poblacionConObjetivo(5000);
        $p->update(['atendida_cantidad' => 999999, 'atendida_sync_at' => now()]);

        $this->assertDatabaseHas('poblaciones_programa', [
            'id' => $p->id,
            'atendida_cantidad' => 999999,
        ]);
    }
}
