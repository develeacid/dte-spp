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
}
