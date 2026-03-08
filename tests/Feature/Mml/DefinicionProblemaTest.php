<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Livewire\Mml\DefinicionProblema;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DefinicionProblemaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Etapa 1');
    }

    public function test_guardar_problema_central(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', 'Alto índice de deserción escolar en el estado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arboles', [
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => 'problema',
        ]);

        $this->assertDatabaseHas('arbol_nodos', [
            'tipo_nodo' => 'problema_central',
            'descripcion' => 'Alto índice de deserción escolar en el estado',
        ]);
    }

    public function test_validacion_descripcion_requerida(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', '')
            ->call('guardar')
            ->assertHasErrors(['descripcion' => 'required']);
    }

    public function test_validacion_descripcion_minimo_caracteres(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', 'Corto')
            ->call('guardar')
            ->assertHasErrors(['descripcion' => 'min']);
    }

    public function test_carga_problema_existente(): void
    {
        $arbol = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Problema existente guardado',
        ]);

        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->assertSet('descripcion', 'Problema existente guardado');
    }

    public function test_actualiza_problema_existente_al_guardar(): void
    {
        $arbol = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
        $nodo = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Versión anterior',
        ]);

        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', 'Versión actualizada del problema')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'id' => $nodo->id,
            'descripcion' => 'Versión actualizada del problema',
        ]);
    }
}
