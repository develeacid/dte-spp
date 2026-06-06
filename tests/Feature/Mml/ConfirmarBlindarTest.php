<?php

namespace Tests\Feature\Mml;

use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoArbol;
use App\Enums\TipoNivelMir;
use App\Enums\TipoNodo;
use App\Livewire\Mml\DefinicionProblema;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Mml\CalendarizacionService;
use App\Services\Tracking\CalendarioService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Blindaje de dos comportamientos "❓" del Informe de Brechas V2 que no se
 * pudieron confirmar leyendo código:
 *
 *  - C-016 (V2-B11): un solo problema central por árbol del problema (y, por
 *    simetría, un solo objetivo central por árbol de objetivos).
 *  - C-107 (V2-B14): los indicadores ANUALES (y BIANUAL/SEXENAL) generan un
 *    único periodo con apertura en enero del ejercicio siguiente, por lo que
 *    NO hay nada que capturar/semaforizar en trimestres intermedios (Q1..Q3).
 */
class ConfirmarBlindarTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    private function crearPrograma(string $clave = 'PT-001'): ProgramaPresupuestario
    {
        return ProgramaPresupuestario::create([
            'nombre' => 'Test',
            'clave' => $clave,
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    private function crearArbolProblema(): Arbol
    {
        return Arbol::create([
            'programa_presupuestario_id' => $this->crearPrograma()->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
    }

    // =====================================================================
    // C-016 — un solo problema central / objetivo central por árbol
    // =====================================================================

    public function test_solo_existe_un_arbol_problema_por_programa(): void
    {
        $programa = $this->crearPrograma('PT-ARB');

        Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);

        // El UNIQUE(programa_presupuestario_id, tipo) preexistente impide un
        // segundo árbol del problema para el mismo programa.
        $this->expectException(QueryException::class);

        Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
    }

    public function test_no_se_puede_crear_segundo_problema_central_via_insert_directo(): void
    {
        $arbol = $this->crearArbolProblema();

        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Primer problema central del árbol.',
            'orden' => 0,
        ]);

        // El índice único parcial arbol_nodos_unico_problema_central bloquea un
        // segundo nodo raíz aunque se inserte directamente saltándose la UI.
        $this->expectException(QueryException::class);

        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Segundo problema central que NO debe existir.',
            'orden' => 1,
        ]);
    }

    public function test_no_se_puede_crear_segundo_objetivo_central_via_insert_directo(): void
    {
        $arbol = Arbol::create([
            'programa_presupuestario_id' => $this->crearPrograma('PT-OBJ')->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);

        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::OBJETIVO_CENTRAL->value,
            'descripcion' => 'Primer objetivo central del árbol.',
            'orden' => 0,
        ]);

        $this->expectException(QueryException::class);

        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::OBJETIVO_CENTRAL->value,
            'descripcion' => 'Segundo objetivo central que NO debe existir.',
            'orden' => 1,
        ]);
    }

    public function test_si_se_permiten_multiples_causas_y_efectos_en_el_mismo_arbol(): void
    {
        $arbol = $this->crearArbolProblema();

        $central = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Problema central del árbol de prueba.',
            'orden' => 0,
        ]);

        // El índice parcial NO debe afectar a las causas/efectos: pueden ser N.
        $causa1 = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'parent_id' => $central->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Primera causa directa del problema.',
            'orden' => 1,
        ]);
        $causa2 = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'parent_id' => $central->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Segunda causa directa del problema.',
            'orden' => 2,
        ]);

        $this->assertNotEquals($causa1->id, $causa2->id);
        $this->assertEquals(
            2,
            ArbolNodo::where('arbol_id', $arbol->id)
                ->where('tipo_nodo', TipoNodo::CAUSA_DIRECTA->value)
                ->count()
        );
    }

    public function test_dos_arboles_distintos_pueden_tener_cada_uno_su_problema_central(): void
    {
        $arbolA = $this->crearArbolProblema();
        $arbolB = Arbol::create([
            'programa_presupuestario_id' => $this->crearPrograma('PT-B')->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);

        $nodoA = ArbolNodo::create([
            'arbol_id' => $arbolA->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Problema central del árbol A.',
            'orden' => 0,
        ]);
        $nodoB = ArbolNodo::create([
            'arbol_id' => $arbolB->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Problema central del árbol B.',
            'orden' => 0,
        ]);

        $this->assertNotEquals($nodoA->id, $nodoB->id);
        $this->assertEquals(
            2,
            ArbolNodo::where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)->count()
        );
    }

    public function test_definicion_problema_guardar_es_idempotente_para_problema_central(): void
    {
        $programa = $this->crearPrograma('PT-DEF');
        $this->actingAs($this->user);

        Livewire::test(DefinicionProblema::class, ['programa' => $programa])
            ->set('descripcion', 'Insuficiente acceso a servicios de salud en la región norte.')
            ->call('guardar');

        Livewire::test(DefinicionProblema::class, ['programa' => $programa])
            ->set('descripcion', 'Versión actualizada del problema central del programa.')
            ->call('guardar');

        // Tras dos guardados sigue habiendo exactamente UN problema central.
        $arbol = $programa->arboles()->where('tipo', TipoArbol::PROBLEMA->value)->first();
        $this->assertEquals(
            1,
            ArbolNodo::where('arbol_id', $arbol->id)
                ->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)
                ->count()
        );
    }

    // =====================================================================
    // C-107 — anuales/bianual/sexenal sin semáforo en trimestres intermedios
    // =====================================================================

    /**
     * @return array<string, array{0: FrecuenciaMedicion}>
     */
    public static function frecuenciasUnPeriodoProvider(): array
    {
        return [
            'anual' => [FrecuenciaMedicion::ANUAL],
            'bianual' => [FrecuenciaMedicion::BIANUAL],
            'sexenal' => [FrecuenciaMedicion::SEXENAL],
        ];
    }

    #[DataProvider('frecuenciasUnPeriodoProvider')]
    public function test_numero_periodos_es_uno_para_frecuencias_largas(FrecuenciaMedicion $frecuencia): void
    {
        $this->assertSame(1, (new CalendarizacionService)->numeroPeriodos($frecuencia));
    }

    #[DataProvider('frecuenciasUnPeriodoProvider')]
    public function test_calcular_fechas_genera_un_periodo_con_apertura_en_enero_siguiente(FrecuenciaMedicion $frecuencia): void
    {
        $fechas = (new CalendarioService)->calcularFechas(2026, $frecuencia);

        // Exactamente 1 periodo: no hay periodo 2, 3 ni 4.
        $this->assertCount(1, $fechas);
        $this->assertEquals(1, $fechas[0]['periodo']);

        // mesApertura=13 → enero del ejercicio+1. La captura abre cuando el
        // año ya cerró, nunca en trimestres intermedios.
        $this->assertEquals('2027-01-01', $fechas[0]['fecha_apertura']);
        $this->assertEquals('2027-01-15', $fechas[0]['fecha_cierre']);
    }

    public function test_indicador_anual_calendarizado_no_tiene_metas_en_trimestres_intermedios(): void
    {
        $programa = $this->crearPrograma('PT-ANU');
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Propósito anual',
            'orden' => 1,
        ]);
        $indicador = Indicador::factory()->create([
            'mir_nivel_id' => $nivel->id,
            'frecuencia' => FrecuenciaMedicion::ANUAL->value,
            'meta' => 500,
            'activo_seguimiento' => true,
        ]);

        $service = new CalendarizacionService;
        $service->confirmar($programa, $service->generar($programa), 2026);

        // Solo existe el periodo 1: nada que capturar/semaforizar en Q1..Q4
        // intermedios.
        $this->assertEquals(
            1,
            MetaPeriodo::where('indicador_id', $indicador->id)->count()
        );
        $this->assertDatabaseHas('metas_periodo', [
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'ejercicio_fiscal' => 2026,
        ]);

        // En Q2/Q3/Q4 (periodos 2,3,4) NO hay MetaPeriodo: en esos trimestres
        // no hay semáforo posible para un indicador anual.
        $this->assertEquals(
            0,
            MetaPeriodo::where('indicador_id', $indicador->id)
                ->whereIn('periodo', [2, 3, 4])
                ->count()
        );
    }

    public function test_indicador_trimestral_si_tiene_meta_en_cada_trimestre(): void
    {
        // Control negativo: un trimestral SÍ produce 4 periodos. Garantiza que
        // el caso anual es un comportamiento específico, no un bug global.
        $programa = $this->crearPrograma('PT-TRI');
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Componente trimestral',
            'orden' => 1,
        ]);
        $indicador = Indicador::factory()->create([
            'mir_nivel_id' => $nivel->id,
            'frecuencia' => FrecuenciaMedicion::TRIMESTRAL->value,
            'meta' => 100,
            'activo_seguimiento' => true,
        ]);

        $service = new CalendarizacionService;
        $service->confirmar($programa, $service->generar($programa), 2026);

        $this->assertEquals(
            4,
            MetaPeriodo::where('indicador_id', $indicador->id)
                ->whereIn('periodo', [1, 2, 3, 4])
                ->count()
        );
    }
}
