<?php

namespace Tests\Feature\Cascade;

use App\Models\Presupuesto\ClasificacionFuncional;
use Database\Seeders\Cascade\ClasificacionFuncionalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClasificacionFuncionalSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_carga_el_catalogo_conac_completo(): void
    {
        $this->seed(ClasificacionFuncionalSeeder::class);

        $this->assertSame(4, ClasificacionFuncional::finalidades()->count());
        $this->assertSame(28, ClasificacionFuncional::funciones()->count());
        $this->assertSame(111, ClasificacionFuncional::subfunciones()->count());
    }

    public function test_jerarquia_padre_hijo_correcta(): void
    {
        $this->seed(ClasificacionFuncionalSeeder::class);

        // Subfunción 1.1.1 Legislación → padre función 1.1; función → padre finalidad 1.
        $subfuncion = ClasificacionFuncional::where('nivel', 'subfuncion')->where('clave', '111')->firstOrFail();
        $funcion = $subfuncion->padre;
        $this->assertSame('funcion', $funcion->nivel);
        $this->assertSame('11', $funcion->clave);

        $finalidad = $funcion->padre;
        $this->assertSame('finalidad', $finalidad->nivel);
        $this->assertSame('1', $finalidad->clave);
        $this->assertSame('Gobierno', $finalidad->nombre);

        // Las finalidades no tienen padre.
        $this->assertNull($finalidad->padre_id);
    }

    public function test_seeder_es_idempotente(): void
    {
        $this->seed(ClasificacionFuncionalSeeder::class);
        $this->seed(ClasificacionFuncionalSeeder::class);

        $this->assertSame(143, ClasificacionFuncional::count());
    }
}
