<?php

namespace Tests\Feature\Mml;

use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\Mml\RevisionMeta;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MetaJustificacionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    private function crearProgramaConIndicador(?float $meta = null, array $extra = []): array
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-META-'.uniqid(),
            'team_id' => $this->user->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Componente', 'orden' => 1,
            'team_id' => $this->user->currentTeam->id,
        ]);
        $indicador = Indicador::create(array_merge([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Indicador',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => FrecuenciaMedicion::TRIMESTRAL->value, 'meta' => $meta,
            'activo_seguimiento' => true, 'orden' => 1,
        ], $extra));

        return [$programa, $indicador];
    }

    public function test_primera_definicion_sin_justificacion_guarda_sin_revision(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador(meta: null);

        $this->actingAs($this->user);

        Livewire::test(MirEditor::class, ['programa' => $programa])
            ->call('guardarMeta', $indicador->id, 100)
            ->assertHasNoErrors();

        $this->assertEquals(100.0, (float) $indicador->fresh()->meta);
        $this->assertDatabaseCount('revisiones_meta', 0);
    }

    public function test_cambio_sin_justificacion_da_error_y_no_guarda(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador(meta: 100);

        $this->actingAs($this->user);

        Livewire::test(MirEditor::class, ['programa' => $programa])
            ->call('guardarMeta', $indicador->id, 80)
            ->assertHasErrors("meta_{$indicador->id}");

        $this->assertEquals(100.0, (float) $indicador->fresh()->meta);
        $this->assertDatabaseCount('revisiones_meta', 0);
    }

    public function test_cambio_con_justificacion_actualiza_meta_y_crea_revision(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador(meta: 100);

        $this->actingAs($this->user);

        Livewire::test(MirEditor::class, ['programa' => $programa])
            ->call('guardarMeta', $indicador->id, 80, 'Ajuste por recorte presupuestal')
            ->assertHasNoErrors();

        $this->assertEquals(80.0, (float) $indicador->fresh()->meta);
        $this->assertDatabaseCount('revisiones_meta', 1);

        $revision = RevisionMeta::first();
        $this->assertEquals(100.0, (float) $revision->valor_anterior);
        $this->assertEquals(80.0, (float) $revision->valor_nuevo);
        $this->assertSame('Ajuste por recorte presupuestal', $revision->justificacion);
        $this->assertEquals($this->user->id, $revision->user_id);
        $this->assertEquals($indicador->id, $revision->indicador_id);
        $this->assertNull($revision->meta_periodo_id);
    }

    public function test_mismo_valor_no_exige_justificacion_ni_crea_revision(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador(meta: 100);

        $this->actingAs($this->user);

        Livewire::test(MirEditor::class, ['programa' => $programa])
            ->call('guardarMeta', $indicador->id, 100)
            ->assertHasNoErrors();

        $this->assertEquals(100.0, (float) $indicador->fresh()->meta);
        $this->assertDatabaseCount('revisiones_meta', 0);
    }

    public function test_indicador_de_otro_programa_no_se_modifica(): void
    {
        [$programaA] = $this->crearProgramaConIndicador(meta: 100);
        [, $indicadorB] = $this->crearProgramaConIndicador(meta: 100);

        $this->actingAs($this->user);

        Livewire::test(MirEditor::class, ['programa' => $programaA])
            ->call('guardarMeta', $indicadorB->id, 80, 'Justificación larga válida');

        // El indicador del otro programa permanece intacto (scoping).
        $this->assertEquals(100.0, (float) $indicadorB->fresh()->meta);
        $this->assertDatabaseCount('revisiones_meta', 0);
    }

    public function test_constraint_xor_rechaza_ambos_fks(): void
    {
        [, $indicador] = $this->crearProgramaConIndicador(meta: 100);

        // Crear un meta_periodo para tener un id válido.
        $metaPeriodoId = DB::table('metas_periodo')->insertGetId([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'ejercicio_fiscal' => 2026,
            'meta_periodo' => 25,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('revisiones_meta')->insert([
            'meta_periodo_id' => $metaPeriodoId,
            'indicador_id' => $indicador->id,
            'valor_anterior' => 100,
            'valor_nuevo' => 80,
            'justificacion' => 'x',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_constraint_xor_rechaza_ningun_fk(): void
    {
        $this->expectException(QueryException::class);
        DB::table('revisiones_meta')->insert([
            'meta_periodo_id' => null,
            'indicador_id' => null,
            'valor_anterior' => 100,
            'valor_nuevo' => 80,
            'justificacion' => 'x',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_advertencia_b3_meta_fuera_de_rango_verde_guarda_con_warning(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador(meta: null, extra: [
            'rango_verde_min' => 90,
            'rango_verde_max' => 110,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(MirEditor::class, ['programa' => $programa])
            ->call('guardarMeta', $indicador->id, 50)
            ->assertHasNoErrors();

        // La meta se guarda aunque quede fuera del rango verde.
        $this->assertEquals(50.0, (float) $indicador->fresh()->meta);

        // Hay una advertencia presente.
        $component->assertSet('metaWarning', fn ($v) => $v !== null && str_contains($v, '90'));
    }
}
