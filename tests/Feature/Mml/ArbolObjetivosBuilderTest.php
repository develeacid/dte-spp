<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Livewire\Mml\ArbolObjetivosBuilder;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArbolObjetivosBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private Arbol $arbolProblema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->arbolProblema = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
        $central = ArbolNodo::create([
            'arbol_id' => $this->arbolProblema->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Alto índice de deserción escolar',
        ]);
        ArbolNodo::create([
            'arbol_id' => $this->arbolProblema->id,
            'parent_id' => $central->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Falta de recursos económicos',
            'orden' => 1,
        ]);
        ArbolNodo::create([
            'arbol_id' => $this->arbolProblema->id,
            'parent_id' => $central->id,
            'tipo_nodo' => TipoNodo::EFECTO_DIRECTO->value,
            'descripcion' => 'Baja competitividad laboral',
            'orden' => 1,
        ]);
    }

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Etapa 3');
    }

    public function test_genera_arbol_objetivos_automaticamente(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $this->assertDatabaseHas('arboles', [
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => 'objetivos',
        ]);

        $arbolObj = Arbol::where('programa_presupuestario_id', $this->programa->id)
            ->where('tipo', 'objetivos')->first();

        $this->assertNotNull($arbolObj);
        $this->assertEquals(3, $arbolObj->nodos()->count());
    }

    public function test_nodos_vinculados_via_nodo_origen_id(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $arbolObj = Arbol::where('programa_presupuestario_id', $this->programa->id)
            ->where('tipo', 'objetivos')->first();

        $nodosCentral = $arbolObj->nodos()->where('tipo_nodo', 'objetivo_central')->first();

        $this->assertNotNull($nodosCentral->nodo_origen_id);
    }

    public function test_editar_nodo_transformado(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $arbolObj = Arbol::where('programa_presupuestario_id', $this->programa->id)
            ->where('tipo', 'objetivos')->first();
        $nodo = $arbolObj->nodos()->where('tipo_nodo', 'objetivo_central')->first();

        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa])
            ->call('editarNodo', $nodo->id)
            ->set('editDescripcion', 'Reducir el índice de deserción escolar')
            ->call('guardarEdicion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'id' => $nodo->id,
            'descripcion' => 'Reducir el índice de deserción escolar',
        ]);
    }

    public function test_no_regenera_si_arbol_objetivos_ya_existe(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $arbolObj = Arbol::where('programa_presupuestario_id', $this->programa->id)
            ->where('tipo', 'objetivos')->first();
        $countOriginal = $arbolObj->nodos()->count();

        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $this->assertEquals($countOriginal, $arbolObj->fresh()->nodos()->count());
    }
}
