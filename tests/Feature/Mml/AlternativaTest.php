<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Models\Mml\Alternativa;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlternativaTest extends TestCase
{
    use RefreshDatabase;

    private function crearContextoMml(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test', 'clave' => 'PT-001',
            'team_id' => $user->currentTeam->id,
        ]);
        $arbolObj = Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);
        $medio1 = ArbolNodo::create([
            'arbol_id' => $arbolObj->id,
            'tipo_nodo' => TipoNodo::MEDIO_DIRECTO->value,
            'descripcion' => 'Medio directo 1',
        ]);
        $medio2 = ArbolNodo::create([
            'arbol_id' => $arbolObj->id,
            'tipo_nodo' => TipoNodo::MEDIO_INDIRECTO->value,
            'descripcion' => 'Medio indirecto 1',
        ]);

        return [$programa, $arbolObj, $medio1, $medio2];
    }

    public function test_crear_alternativa(): void
    {
        [$programa] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alternativa A',
        ]);

        $this->assertDatabaseHas('alternativas', [
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alternativa A',
            'seleccionada' => false,
        ]);
    }

    public function test_alternativa_pertenece_a_programa(): void
    {
        [$programa] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alt A',
        ]);

        $this->assertInstanceOf(ProgramaPresupuestario::class, $alternativa->programa);
    }

    public function test_asignar_nodos_a_alternativa(): void
    {
        [$programa, $arbol, $medio1, $medio2] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alt A',
        ]);

        $alternativa->nodos()->attach([$medio1->id, $medio2->id]);

        $this->assertCount(2, $alternativa->nodos);
    }

    public function test_seleccionar_alternativa_con_justificacion(): void
    {
        [$programa] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alt A',
            'seleccionada' => true,
            'justificacion_seleccion' => 'Mayor viabilidad técnica e institucional',
        ]);

        $this->assertTrue($alternativa->seleccionada);
        $this->assertEquals('Mayor viabilidad técnica e institucional', $alternativa->justificacion_seleccion);
    }

    public function test_cascade_delete_programa_elimina_alternativas(): void
    {
        [$programa, $arbol, $medio1] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alt A',
        ]);
        $alternativa->nodos()->attach($medio1->id);

        $programa->forceDelete();

        $this->assertDatabaseMissing('alternativas', ['id' => $alternativa->id]);
        $this->assertDatabaseMissing('alternativa_nodo', ['alternativa_id' => $alternativa->id]);
    }
}
