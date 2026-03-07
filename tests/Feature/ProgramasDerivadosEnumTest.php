<?php

namespace Tests\Feature;

use App\Enums\TipoProgramaDerivado;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProgramasDerivadosEnumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Cascade\PedSeeder::class);
    }

    public function test_enum_php_contiene_los_cuatro_tipos(): void
    {
        $cases = TipoProgramaDerivado::cases();

        $this->assertCount(4, $cases);
        $this->assertEquals('sectorial', TipoProgramaDerivado::SECTORIAL->value);
        $this->assertEquals('especial', TipoProgramaDerivado::ESPECIAL->value);
        $this->assertEquals('institucional', TipoProgramaDerivado::INSTITUCIONAL->value);
        $this->assertEquals('regional', TipoProgramaDerivado::REGIONAL->value);
    }

    public function test_modelo_castea_enum_correctamente(): void
    {
        $plan = PedPlan::first();

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Test',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        $this->assertInstanceOf(TipoProgramaDerivado::class, $programa->tipo);
        $this->assertEquals(TipoProgramaDerivado::SECTORIAL, $programa->tipo);
    }

    public function test_no_permite_valor_invalido_en_enum(): void
    {
        $this->expectException(QueryException::class);

        $plan = PedPlan::first();

        // Intentar insertar valor no válido en el ENUM
        DB::table('programas_derivados')->insert([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Test Invalid',
            'tipo' => 'valor_invalido',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_scope_sectoriales_filtra_correctamente(): void
    {
        $plan = PedPlan::first();

        ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Sectorial 1',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Especial 1',
            'tipo' => TipoProgramaDerivado::ESPECIAL,
        ]);

        $sectoriales = ProgramaDerivado::sectoriales()->get();

        $this->assertCount(1, $sectoriales);
        $this->assertEquals('Sectorial 1', $sectoriales->first()->nombre);
    }

    public function test_prefijo_clave_por_tipo(): void
    {
        $plan = PedPlan::first();

        $sectorial = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Test Sectorial',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        $especial = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Test Especial',
            'tipo' => TipoProgramaDerivado::ESPECIAL,
        ]);

        $this->assertEquals('OS', $sectorial->prefijoClave());
        $this->assertEquals('OE', $especial->prefijoClave());
    }
}
