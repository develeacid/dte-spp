<?php

namespace Tests\Feature\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Livewire\Mml\ArbolProblemaBuilder;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArbolProblemaBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    private Arbol $arbol;

    private ArbolNodo $problemaCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $this->arbol = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
        $this->problemaCentral = ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Alto índice de deserción escolar',
        ]);
    }

    public function test_componente_se_renderiza_con_problema_central(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Alto índice de deserción escolar');
    }

    public function test_agregar_causa_directa(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('agregarNodo', $this->problemaCentral->id, 'causa_directa')
            ->set('nuevoNodoDescripcion', 'Falta de recursos económicos en las familias')
            ->call('guardarNuevoNodo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => 'causa_directa',
            'descripcion' => 'Falta de recursos económicos en las familias',
        ]);
    }

    public function test_agregar_causa_indirecta_bajo_causa_directa(): void
    {
        $causaDirecta = ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Causa directa',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('agregarNodo', $causaDirecta->id, 'causa_indirecta')
            ->set('nuevoNodoDescripcion', 'Causa indirecta de prueba')
            ->call('guardarNuevoNodo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'parent_id' => $causaDirecta->id,
            'tipo_nodo' => 'causa_indirecta',
        ]);
    }

    public function test_agregar_efecto_directo(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('agregarNodo', $this->problemaCentral->id, 'efecto_directo')
            ->set('nuevoNodoDescripcion', 'Baja competitividad laboral')
            ->call('guardarNuevoNodo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => 'efecto_directo',
        ]);
    }

    public function test_editar_nodo(): void
    {
        $causa = ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Descripción original',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('editarNodo', $causa->id)
            ->set('editNodoDescripcion', 'Descripción actualizada')
            ->call('actualizarNodo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'id' => $causa->id,
            'descripcion' => 'Descripción actualizada',
        ]);
    }

    public function test_eliminar_nodo(): void
    {
        $causa = ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'A eliminar',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('eliminarNodo', $causa->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('arbol_nodos', ['id' => $causa->id]);
    }

    public function test_sugerir_efectos_agrega_como_efecto_directo(): void
    {
        $this->mock(LlmServiceInterface::class, function ($mock) {
            $mock->shouldReceive('isDegraded')->andReturn(false);
            $mock->shouldReceive('suggest')->once()->andReturn('1. Efecto sugerido por IA');
        });

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('sugerirConIa', 'efecto')
            ->call('agregarSugerencia', 'Efecto sugerido por IA', $this->problemaCentral->id, 'efecto_directo');

        $this->assertDatabaseHas('arbol_nodos', [
            'arbol_id' => $this->arbol->id,
            'tipo_nodo' => 'efecto_directo',
            'descripcion' => 'Efecto sugerido por IA',
        ]);
    }

    public function test_sugerir_causas_agrega_como_causa_directa(): void
    {
        $this->mock(LlmServiceInterface::class, function ($mock) {
            $mock->shouldReceive('isDegraded')->andReturn(false);
            $mock->shouldReceive('suggest')->once()->andReturn('1. Causa sugerida por IA');
        });

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('sugerirConIa', 'causa')
            ->call('agregarSugerencia', 'Causa sugerida por IA', $this->problemaCentral->id, 'causa_directa');

        $this->assertDatabaseHas('arbol_nodos', [
            'arbol_id' => $this->arbol->id,
            'tipo_nodo' => 'causa_directa',
            'descripcion' => 'Causa sugerida por IA',
        ]);
    }

    public function test_generar_arbol_ejemplo_produce_preview_sin_persistir(): void
    {
        $this->mock(LlmServiceInterface::class, function ($mock) {
            $mock->shouldReceive('isDegraded')->andReturn(false);
            $mock->shouldReceive('suggest')->once()->andReturn(json_encode([
                'causas_directas' => [
                    ['descripcion' => 'Causa directa 1', 'indirectas' => ['Indirecta 1A', 'Indirecta 1B']],
                    ['descripcion' => 'Causa directa 2', 'indirectas' => ['Indirecta 2A', 'Indirecta 2B']],
                ],
                'efectos_directos' => ['Efecto directo 1', 'Efecto directo 2'],
            ]));
        });

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('generarArbolEjemplo')
            ->assertSet('arbolEjemploPreview', fn ($val) => ! empty($val))
            ->assertSet('mostrarPreviewArbol', true);

        // Verify nothing was persisted to database
        $this->assertEquals(1, ArbolNodo::where('arbol_id', $this->arbol->id)->count());
    }

    public function test_confirmar_arbol_ejemplo_persiste_nodos(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->set('arbolEjemploPreview', [
                'causas_directas' => [
                    ['descripcion' => 'Causa directa 1', 'indirectas' => ['Indirecta 1A', 'Indirecta 1B']],
                    ['descripcion' => 'Causa directa 2', 'indirectas' => ['Indirecta 2A', 'Indirecta 2B']],
                ],
                'efectos_directos' => ['Efecto directo 1', 'Efecto directo 2'],
            ])
            ->set('mostrarPreviewArbol', true)
            ->call('confirmarArbolEjemplo');

        // 1 problema_central + 2 causas + 4 indirectas + 2 efectos = 9 nodes total
        $this->assertEquals(9, ArbolNodo::where('arbol_id', $this->arbol->id)->count());
    }

    public function test_sugerir_causas_indirectas_genera_sugerencias_para_causa_directa(): void
    {
        $causaDirecta = ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Falta de capacitación',
            'orden' => 1,
        ]);

        $this->mock(LlmServiceInterface::class, function ($mock) {
            $mock->shouldReceive('isDegraded')->andReturn(false);
            $mock->shouldReceive('suggest')->once()->andReturn(
                "1. Presupuesto insuficiente para formación\n2. Ausencia de programas de desarrollo profesional"
            );
        });

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('sugerirCausasIndirectas', $causaDirecta->id)
            ->assertSet('sugerenciasIa', fn ($val) => count($val) === 2)
            ->assertSet('tipoSugerencia', 'causa_indirecta')
            ->assertSet('parentIdSugerencia', $causaDirecta->id);
    }

    public function test_no_puede_eliminar_problema_central(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('eliminarNodo', $this->problemaCentral->id)
            ->assertDispatched('notify', function ($name, $params) {
                return str_contains($params['message'] ?? $params[0] ?? '', 'no se puede eliminar')
                    || str_contains($params['message'] ?? $params[0] ?? '', 'central');
            });

        $this->assertDatabaseHas('arbol_nodos', ['id' => $this->problemaCentral->id]);
    }
}
