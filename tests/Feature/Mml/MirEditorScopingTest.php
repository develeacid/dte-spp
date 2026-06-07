<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Defensa-en-profundidad: cada método mutador del MirEditor debe scopear las
 * entidades al programa montado. Un request Livewire crafteado con el id de una
 * entidad de OTRO programa/team debe ser un no-op silencioso (consistente con
 * guardarMeta), nunca mutar la BD ajena.
 */
class MirEditorScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** Programa montado en el editor (propio del usuario). */
    private ProgramaPresupuestario $programaA;

    /** Programa ajeno (otro team). */
    private ProgramaPresupuestario $programaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($this->user);

        $this->programaA = $this->crearPrograma($this->user->currentTeam->id);

        $otroUser = User::factory()->withPersonalTeam()->create();
        $this->programaB = $this->crearPrograma($otroUser->currentTeam->id);
    }

    private function crearPrograma(int $teamId): ProgramaPresupuestario
    {
        return ProgramaPresupuestario::create([
            'nombre' => 'Programa '.uniqid(),
            'clave' => 'PT-SCOPE-'.uniqid(),
            'team_id' => $teamId,
            'ejercicio_fiscal' => 2026,
        ]);
    }

    /**
     * Crea el árbol mínimo (componente + actividad + indicador + variable + MV)
     * colgando de un programa dado.
     *
     * @return array{nivel: MirNivel, actividad: MirNivel, indicador: Indicador, variable: IndicadorVariable, medio: MedioVerificacion}
     */
    private function arbol(ProgramaPresupuestario $programa): array
    {
        $nivel = MirNivel::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'orden' => 1,
        ]);

        $actividad = MirNivel::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $nivel->id,
            'orden' => 1,
        ]);

        $indicador = Indicador::factory()->create(['mir_nivel_id' => $nivel->id]);

        $variable = IndicadorVariable::create([
            'indicador_id' => $indicador->id,
            'simbolo' => 'A',
            'nombre' => 'Variable original',
            'orden' => 1,
        ]);

        $medio = MedioVerificacion::create([
            'indicador_id' => $indicador->id,
            'nombre' => 'Medio original',
            'orden' => 1,
        ]);

        return compact('nivel', 'actividad', 'indicador', 'variable', 'medio');
    }

    private function editor()
    {
        return Livewire::test(MirEditor::class, ['programa' => $this->programaA]);
    }

    // ---------------------------------------------------------------------
    // NIVELES
    // ---------------------------------------------------------------------

    public function test_guardar_nivel_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $original = $b['nivel']->resumen_narrativo;

        $this->editor()->call('guardarNivel', $b['nivel']->id, 'resumen_narrativo', 'HACKEADO');

        $this->assertSame($original, $b['nivel']->fresh()->resumen_narrativo);
    }

    public function test_eliminar_nivel_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('eliminarNivel', $b['nivel']->id);

        $this->assertNotNull(MirNivel::find($b['nivel']->id));
    }

    public function test_agregar_actividad_a_componente_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $antes = MirNivel::where('componente_id', $b['nivel']->id)->count();

        $this->editor()->call('agregarActividad', $b['nivel']->id);

        $this->assertSame($antes, MirNivel::where('componente_id', $b['nivel']->id)->count());
    }

    public function test_agregar_indicador_a_nivel_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $antes = $b['nivel']->indicadores()->count();

        $this->editor()->call('agregarIndicador', $b['nivel']->id);

        $this->assertSame($antes, $b['nivel']->indicadores()->count());
    }

    public function test_validar_sintaxis_de_nivel_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('validarSintaxis', $b['nivel']->id);

        $this->assertNull($b['nivel']->fresh()->sintaxis_validada_at);
    }

    public function test_aceptar_sugerencia_de_nivel_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $b['nivel']->update(['sintaxis_sugerencia' => 'Sugerencia ajena']);
        $original = $b['nivel']->resumen_narrativo;

        $this->editor()->call('aceptarSugerencia', $b['nivel']->id);

        $this->assertSame($original, $b['nivel']->fresh()->resumen_narrativo);
    }

    public function test_seleccionar_alineacion_de_nivel_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('seleccionarAlineacion', $b['nivel']->id, 'PedObjetivoEstrategico', 999);

        $this->assertNull($b['nivel']->fresh()->ped_objetivo_estrategico_id);
    }

    public function test_buscar_alineacion_de_nivel_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $component = $this->editor()->call('buscarAlineacion', $b['nivel']->id);

        $component->assertSet('nivelAlineacionActivo', null);
    }

    public function test_asignar_ur_coadyuvante_a_nivel_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('asignarUrCoadyuvante', $b['nivel']->id, $this->user->currentTeam->id);

        $this->assertNull($b['nivel']->fresh()->team_id);
    }

    // ---------------------------------------------------------------------
    // INDICADORES
    // ---------------------------------------------------------------------

    public function test_guardar_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $original = $b['indicador']->nombre;

        $this->editor()->call('guardarIndicador', $b['indicador']->id, [
            'nombre' => 'HACKEADO',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
        ]);

        $this->assertSame($original, $b['indicador']->fresh()->nombre);
    }

    public function test_eliminar_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('eliminarIndicador', $b['indicador']->id);

        $this->assertNotNull(Indicador::find($b['indicador']->id));
    }

    public function test_sync_anexos_de_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('syncAnexosTransversales', $b['indicador']->id, [1, 2]);

        $this->assertSame(0, $b['indicador']->anexosTransversales()->count());
    }

    public function test_guardar_formula_texto_de_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $original = $b['indicador']->formula_texto;

        $this->editor()->call('guardarFormulaTexto', $b['indicador']->id, 'HACKEADO');

        $this->assertSame($original, $b['indicador']->fresh()->formula_texto);
    }

    public function test_guardar_linea_base_anio_de_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('guardarLineaBaseAnio', $b['indicador']->id, '2020');

        $this->assertNull($b['indicador']->fresh()->linea_base_anio);
    }

    public function test_guardar_semaforo_de_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('guardarSemaforo', $b['indicador']->id, [
            'rango_verde_min' => 1,
            'rango_verde_max' => 100,
        ]);

        $this->assertNull($b['indicador']->fresh()->rango_verde_min);
    }

    public function test_extraer_variables_de_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $antes = $b['indicador']->variables()->count();

        $this->editor()->call('extraerVariables', $b['indicador']->id);

        // No debe haber borrado las variables existentes ni tocado nada.
        $this->assertSame($antes, $b['indicador']->variables()->count());
    }

    public function test_validar_cremaa_de_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('validarCremaa', $b['indicador']->id);

        $this->assertNull($b['indicador']->cremaaValidacion()->first());
    }

    public function test_sugerir_formula_de_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $original = $b['indicador']->formula_texto;

        $this->editor()->call('sugerirFormula', $b['indicador']->id);

        $this->assertSame($original, $b['indicador']->fresh()->formula_texto);
    }

    public function test_agregar_medio_verificacion_a_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $antes = MedioVerificacion::where('indicador_id', $b['indicador']->id)->count();

        $this->editor()->call('agregarMedioVerificacion', $b['indicador']->id);

        $this->assertSame($antes, MedioVerificacion::where('indicador_id', $b['indicador']->id)->count());
    }

    public function test_agregar_variable_a_indicador_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $antes = IndicadorVariable::where('indicador_id', $b['indicador']->id)->count();

        $this->editor()->call('agregarVariable', $b['indicador']->id);

        $this->assertSame($antes, IndicadorVariable::where('indicador_id', $b['indicador']->id)->count());
    }

    // ---------------------------------------------------------------------
    // VARIABLES
    // ---------------------------------------------------------------------

    public function test_guardar_variable_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $original = $b['variable']->nombre;

        $this->editor()->call('guardarVariable', $b['variable']->id, [
            'simbolo' => 'X',
            'nombre' => 'HACKEADO',
        ]);

        $this->assertSame($original, $b['variable']->fresh()->nombre);
    }

    public function test_eliminar_variable_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('eliminarVariable', $b['variable']->id);

        $this->assertNotNull(IndicadorVariable::find($b['variable']->id));
    }

    // ---------------------------------------------------------------------
    // MEDIOS DE VERIFICACIÓN
    // ---------------------------------------------------------------------

    public function test_guardar_medio_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);
        $original = $b['medio']->nombre;

        $this->editor()->call('guardarMedioVerificacion', $b['medio']->id, [
            'nombre' => 'HACKEADO',
        ]);

        $this->assertSame($original, $b['medio']->fresh()->nombre);
    }

    public function test_eliminar_medio_de_otro_programa_es_noop(): void
    {
        $b = $this->arbol($this->programaB);

        $this->editor()->call('eliminarMedioVerificacion', $b['medio']->id);

        $this->assertNotNull(MedioVerificacion::find($b['medio']->id));
    }

    // ---------------------------------------------------------------------
    // REGRESIÓN: el id del programa montado (A) SÍ funciona
    // ---------------------------------------------------------------------

    public function test_guardar_nivel_del_programa_propio_funciona(): void
    {
        $a = $this->arbol($this->programaA);

        $this->editor()->call('guardarNivel', $a['nivel']->id, 'resumen_narrativo', 'NUEVO TEXTO');

        $this->assertSame('NUEVO TEXTO', $a['nivel']->fresh()->resumen_narrativo);
    }

    public function test_guardar_indicador_del_programa_propio_funciona(): void
    {
        $a = $this->arbol($this->programaA);

        $this->editor()->call('guardarIndicador', $a['indicador']->id, [
            'nombre' => 'Indicador renombrado',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
        ]);

        $this->assertSame('Indicador renombrado', $a['indicador']->fresh()->nombre);
    }

    public function test_guardar_variable_del_programa_propio_funciona(): void
    {
        $a = $this->arbol($this->programaA);

        $this->editor()->call('guardarVariable', $a['variable']->id, [
            'simbolo' => 'Z',
            'nombre' => 'Variable renombrada',
        ]);

        $this->assertSame('Variable renombrada', $a['variable']->fresh()->nombre);
    }

    public function test_guardar_medio_del_programa_propio_funciona(): void
    {
        $a = $this->arbol($this->programaA);

        $this->editor()->call('guardarMedioVerificacion', $a['medio']->id, [
            'nombre' => 'Medio renombrado',
        ]);

        $this->assertSame('Medio renombrado', $a['medio']->fresh()->nombre);
    }

    /**
     * Owned-path de un creator: con un nivel PROPIO, agregarIndicador SÍ crea
     * (count +1). Cierra el gap de los no-op tests, que pasarían igual si un
     * guard always-return rompiera silenciosamente los creators.
     */
    public function test_agregar_indicador_a_nivel_del_programa_propio_crea(): void
    {
        $a = $this->arbol($this->programaA);
        $antes = $a['nivel']->indicadores()->count();

        $this->editor()->call('agregarIndicador', $a['nivel']->id);

        $this->assertSame($antes + 1, $a['nivel']->fresh()->indicadores()->count());
    }
}
