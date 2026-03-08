<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArbolTest extends TestCase
{
    use RefreshDatabase;

    private function crearProgramaConArbol(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $user->currentTeam->id,
        ]);
        $arbol = Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);

        return [$programa, $arbol, $user];
    }

    public function test_crear_arbol_de_problemas(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $this->assertDatabaseHas('arboles', [
            'programa_presupuestario_id' => $programa->id,
            'tipo' => 'problema',
        ]);
    }

    public function test_arbol_pertenece_a_programa(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $this->assertInstanceOf(ProgramaPresupuestario::class, $arbol->programa);
        $this->assertEquals($programa->id, $arbol->programa->id);
    }

    public function test_unique_constraint_tipo_por_programa(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
    }

    public function test_crear_nodo_problema_central(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $nodo = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Alto índice de deserción escolar',
        ]);

        $this->assertDatabaseHas('arbol_nodos', [
            'arbol_id' => $arbol->id,
            'tipo_nodo' => 'problema_central',
            'parent_id' => null,
        ]);
    }

    public function test_relacion_recursiva_parent_children(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $central = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Problema central',
        ]);

        $causa = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'parent_id' => $central->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Causa directa 1',
            'orden' => 1,
        ]);

        $this->assertCount(1, $central->children);
        $this->assertEquals($central->id, $causa->parent->id);
    }

    public function test_vinculo_nodo_origen_problema_a_objetivo(): void
    {
        [$programa, $arbolProblema] = $this->crearProgramaConArbol();

        $nodoProblem = ArbolNodo::create([
            'arbol_id' => $arbolProblema->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Alto índice de deserción',
        ]);

        $arbolObj = Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);

        $nodoObj = ArbolNodo::create([
            'arbol_id' => $arbolObj->id,
            'tipo_nodo' => TipoNodo::OBJETIVO_CENTRAL->value,
            'descripcion' => 'Reducir el índice de deserción',
            'nodo_origen_id' => $nodoProblem->id,
        ]);

        $this->assertEquals($nodoProblem->id, $nodoObj->nodoOrigen->id);
    }

    public function test_cascade_delete_arbol_elimina_nodos(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Problema',
        ]);

        $arbol->delete();

        $this->assertDatabaseMissing('arbol_nodos', ['arbol_id' => $arbol->id]);
    }

    public function test_arbol_tiene_nodos(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Central',
        ]);

        $this->assertCount(1, $arbol->nodos);
    }

    public function test_cast_tipo_nodo_a_enum(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $nodo = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Una causa',
        ]);

        $nodo->refresh();
        $this->assertInstanceOf(TipoNodo::class, $nodo->tipo_nodo);
    }
}
