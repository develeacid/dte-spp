<?php

namespace Tests\Feature\Mml;

use App\Enums\InvolucradoCategoria;
use App\Livewire\Mml\DefinicionProblema;
use App\Models\Mml\Involucrado;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DefinicionProblemaInvolucradosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Involucrados',
            'clave' => 'PT-INV-'.uniqid(),
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    private function otroPrograma(): ProgramaPresupuestario
    {
        $otro = User::factory()->withPersonalTeam()->create();

        return ProgramaPresupuestario::create([
            'nombre' => 'Otro', 'clave' => 'PT-OTRO-'.uniqid(),
            'team_id' => $otro->currentTeam->id,
        ]);
    }

    private function editor()
    {
        return Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa]);
    }

    public function test_relacion_involucrados_es_hasmany(): void
    {
        $this->assertInstanceOf(HasMany::class, $this->programa->involucrados());
    }

    public function test_categoria_castea_a_enum(): void
    {
        $inv = Involucrado::create([
            'programa_presupuestario_id' => $this->programa->id,
            'categoria' => InvolucradoCategoria::OPOSITOR->value,
            'nombre' => 'Actor X',
            'orden' => 1,
        ]);

        $this->assertSame(InvolucradoCategoria::OPOSITOR, $inv->fresh()->categoria);
    }

    public function test_agregar_involucrado_crea_fila(): void
    {
        $this->editor()->call('agregarInvolucrado');

        $this->assertSame(1, $this->programa->involucrados()->count());
    }

    public function test_guardar_involucrado_persiste(): void
    {
        $inv = Involucrado::create([
            'programa_presupuestario_id' => $this->programa->id,
            'categoria' => InvolucradoCategoria::EJECUTOR->value,
            'nombre' => '', 'orden' => 1,
        ]);

        $this->editor()->call('guardarInvolucrado', $inv->id, [
            'categoria' => 'aliado',
            'nombre' => 'Universidad Estatal',
            'interes_o_rol' => 'Capacitación técnica',
            'riesgo_asociado' => 'Cambio de administración',
        ]);

        $inv->refresh();
        $this->assertSame(InvolucradoCategoria::ALIADO, $inv->categoria);
        $this->assertSame('Universidad Estatal', $inv->nombre);
        $this->assertSame('Capacitación técnica', $inv->interes_o_rol);
    }

    public function test_guardar_involucrado_rechaza_categoria_invalida(): void
    {
        $inv = Involucrado::create([
            'programa_presupuestario_id' => $this->programa->id,
            'categoria' => InvolucradoCategoria::EJECUTOR->value,
            'nombre' => 'X', 'orden' => 1,
        ]);

        $this->editor()->call('guardarInvolucrado', $inv->id, [
            'categoria' => 'inexistente',
            'nombre' => 'Y',
        ])->assertHasErrors('categoria');
    }

    public function test_guardar_involucrado_de_otro_programa_es_noop(): void
    {
        $ajeno = Involucrado::create([
            'programa_presupuestario_id' => $this->otroPrograma()->id,
            'categoria' => InvolucradoCategoria::EJECUTOR->value,
            'nombre' => 'Original', 'orden' => 1,
        ]);

        $this->editor()->call('guardarInvolucrado', $ajeno->id, [
            'categoria' => 'opositor', 'nombre' => 'HACKEADO',
        ]);

        $this->assertSame('Original', $ajeno->fresh()->nombre);
    }

    public function test_eliminar_involucrado(): void
    {
        $inv = Involucrado::create([
            'programa_presupuestario_id' => $this->programa->id,
            'categoria' => InvolucradoCategoria::EJECUTOR->value,
            'nombre' => 'X', 'orden' => 1,
        ]);

        $this->editor()->call('eliminarInvolucrado', $inv->id);

        $this->assertNull(Involucrado::find($inv->id));
    }
}
