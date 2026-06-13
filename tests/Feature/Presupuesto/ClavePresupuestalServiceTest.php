<?php

namespace Tests\Feature\Presupuesto;

use App\Models\Presupuesto\ClasificacionFuncional;
use App\Models\ProgramaPresupuestario;
use App\Services\Presupuesto\ClavePresupuestalService;
use Database\Seeders\Cascade\ClasificacionFuncionalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClavePresupuestalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ClasificacionFuncionalSeeder::class);
    }

    private function idDe(string $nivel, string $clave): int
    {
        return ClasificacionFuncional::where('nivel', $nivel)->where('clave', $clave)->value('id');
    }

    public function test_clave_funcional_jerarquica_valida_no_genera_errores(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'grupo' => 1, 'unidad_responsable' => 1, 'unidad_ejecutora' => 1,
            'programa_clave' => 144, 'subprograma' => 2, 'proyecto' => 0, 'actividad' => 1,
            'finalidad_id' => $this->idDe('finalidad', '1'),
            'funcion_id' => $this->idDe('funcion', '11'),
            'subfuncion_id' => $this->idDe('subfuncion', '111'),
        ]);

        $this->assertSame([], ClavePresupuestalService::validar($programa));
    }

    public function test_rechaza_subfuncion_que_no_pertenece_a_la_funcion(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'finalidad_id' => $this->idDe('finalidad', '1'),
            'funcion_id' => $this->idDe('funcion', '11'),        // Legislación
            'subfuncion_id' => $this->idDe('subfuncion', '121'), // Impartición de Justicia (de función 12)
        ]);

        $errores = ClavePresupuestalService::validar($programa);

        $this->assertNotEmpty($errores);
        $this->assertStringContainsStringIgnoringCase('subfunción', implode(' ', $errores));
    }

    public function test_rechaza_funcion_que_no_pertenece_a_la_finalidad(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'finalidad_id' => $this->idDe('finalidad', '1'), // Gobierno
            'funcion_id' => $this->idDe('funcion', '21'),     // Protección Ambiental (de finalidad 2)
        ]);

        $errores = ClavePresupuestalService::validar($programa);

        $this->assertNotEmpty($errores);
        $this->assertStringContainsStringIgnoringCase('función', implode(' ', $errores));
    }

    public function test_rechaza_segmento_fuera_de_rango(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'unidad_responsable' => 100, // máximo 99 (2 dígitos)
        ]);

        $errores = ClavePresupuestalService::validar($programa);

        $this->assertNotEmpty($errores);
        $this->assertStringContainsStringIgnoringCase('Unidad Responsable', implode(' ', $errores));
    }
}
