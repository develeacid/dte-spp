<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\CalendarizarMetas;
use App\Models\Mml\ImportacionReporte;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\Mml\RevisionMeta;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Mml\CalendarizacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevisionMetaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CalendarizacionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->service = new CalendarizacionService;
    }

    private function crearProgramaConIndicador(): array
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-REV',
            'team_id' => $this->user->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Componente trimestral', 'orden' => 1,
            'team_id' => $this->user->currentTeam->id,
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa trimestral',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => FrecuenciaMedicion::TRIMESTRAL->value, 'meta' => 100,
            'activo_seguimiento' => true, 'orden' => 1,
        ]);

        return [$programa, $indicador];
    }

    /**
     * Build a metasAjustadas payload mirroring generar()'s shape.
     */
    private function payload(int $indicadorId, array $valoresPorPeriodo): array
    {
        $periodos = [];
        foreach ($valoresPorPeriodo as $periodo => $valor) {
            $periodos[] = ['periodo' => $periodo, 'meta_periodo' => $valor];
        }

        return [['indicador_id' => $indicadorId, 'periodos' => $periodos]];
    }

    public function test_cambio_sin_justificacion_lanza_excepcion_y_no_modifica_nada(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador();

        // Primera calendarización: 25 por periodo.
        $this->service->confirmar($programa, $this->payload($indicador->id, [
            1 => 25, 2 => 25, 3 => 25, 4 => 25,
        ]), 2026);

        $this->assertDatabaseCount('metas_periodo', 4);

        // Intento de cambio (P1 = 30) SIN justificación → DomainException.
        try {
            $this->service->confirmar($programa, $this->payload($indicador->id, [
                1 => 30, 2 => 25, 3 => 25, 4 => 25,
            ]), 2026);
            $this->fail('Se esperaba DomainException');
        } catch (\DomainException $e) {
            $this->assertSame('Modificar metas ya calendarizadas requiere justificación.', $e->getMessage());
        }

        // El valor en BD permanece intacto y no se crearon revisiones.
        $p1 = MetaPeriodo::where('indicador_id', $indicador->id)->where('periodo', 1)->first();
        $this->assertEquals(25.0, (float) $p1->meta_periodo);
        $this->assertDatabaseCount('revisiones_meta', 0);
    }

    public function test_cambio_con_justificacion_crea_revision_y_actualiza_meta(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador();

        $this->service->confirmar($programa, $this->payload($indicador->id, [
            1 => 25, 2 => 25, 3 => 25, 4 => 25,
        ]), 2026);

        $this->service->confirmar($programa, $this->payload($indicador->id, [
            1 => 30, 2 => 25, 3 => 25, 4 => 25,
        ]), 2026, 'Ajuste por recorte presupuestal', $this->user->id);

        // La meta se actualizó.
        $p1 = MetaPeriodo::where('indicador_id', $indicador->id)->where('periodo', 1)->first();
        $this->assertEquals(30.0, (float) $p1->meta_periodo);

        // Una sola revisión, con los valores correctos.
        $this->assertDatabaseCount('revisiones_meta', 1);
        $revision = RevisionMeta::first();
        $this->assertEquals($p1->id, $revision->meta_periodo_id);
        $this->assertEquals(25.0, (float) $revision->valor_anterior);
        $this->assertEquals(30.0, (float) $revision->valor_nuevo);
        $this->assertSame('Ajuste por recorte presupuestal', $revision->justificacion);
        $this->assertEquals($this->user->id, $revision->user_id);
    }

    public function test_primera_calendarizacion_no_exige_justificacion(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador();

        $this->service->confirmar($programa, $this->payload($indicador->id, [
            1 => 25, 2 => 25, 3 => 25, 4 => 25,
        ]), 2026);

        $this->assertDatabaseCount('metas_periodo', 4);
        $this->assertDatabaseCount('revisiones_meta', 0);
    }

    public function test_reconfirmar_mismos_valores_no_exige_justificacion_ni_crea_revisiones(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador();

        $payload = $this->payload($indicador->id, [1 => 25, 2 => 25, 3 => 25, 4 => 25]);
        $this->service->confirmar($programa, $payload, 2026);

        // Re-confirmar idéntico, sin justificación → sin friction.
        $this->service->confirmar($programa, $payload, 2026);

        $this->assertDatabaseCount('metas_periodo', 4);
        $this->assertDatabaseCount('revisiones_meta', 0);
    }

    public function test_livewire_cambio_sin_justificacion_muestra_error(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador();

        // Calendarización previa.
        $this->service->confirmar($programa, $this->payload($indicador->id, [
            1 => 25, 2 => 25, 3 => 25, 4 => 25,
        ]), 2026);

        $reporte = ImportacionReporte::create([
            'team_id' => $this->user->currentTeam->id,
            'archivo_original' => 'test.md',
            'formato' => 'md',
            'datos_parseados' => (new ImportedMirData(
                nombre: $programa->nombre, clave: $programa->clave,
                ejercicioFiscal: 2026, niveles: [],
            ))->toArray(),
            'diagnostico' => null,
            'estado' => 'procesado',
            'created_by' => $this->user->id,
        ]);
        $reporte->programa()->associate($programa)->save();

        $this->actingAs($this->user);

        Livewire::test(CalendarizarMetas::class, ['importacion' => $reporte])
            ->call('ajustarMeta', $indicador->id, 1, 30)
            ->call('confirmar')
            ->assertHasErrors(['justificacion']);

        // Nada cambió en BD.
        $p1 = MetaPeriodo::where('indicador_id', $indicador->id)->where('periodo', 1)->first();
        $this->assertEquals(25.0, (float) $p1->meta_periodo);
        $this->assertDatabaseCount('revisiones_meta', 0);
    }

    public function test_livewire_cambio_con_justificacion_guarda_y_crea_revision(): void
    {
        [$programa, $indicador] = $this->crearProgramaConIndicador();

        $this->service->confirmar($programa, $this->payload($indicador->id, [
            1 => 25, 2 => 25, 3 => 25, 4 => 25,
        ]), 2026);

        $reporte = ImportacionReporte::create([
            'team_id' => $this->user->currentTeam->id,
            'archivo_original' => 'test.md',
            'formato' => 'md',
            'datos_parseados' => (new ImportedMirData(
                nombre: $programa->nombre, clave: $programa->clave,
                ejercicioFiscal: 2026, niveles: [],
            ))->toArray(),
            'diagnostico' => null,
            'estado' => 'procesado',
            'created_by' => $this->user->id,
        ]);
        $reporte->programa()->associate($programa)->save();

        $this->actingAs($this->user);

        Livewire::test(CalendarizarMetas::class, ['importacion' => $reporte])
            ->set('justificacion', 'Reasignación por demanda real')
            ->call('ajustarMeta', $indicador->id, 1, 30)
            ->call('confirmar')
            ->assertHasNoErrors();

        $p1 = MetaPeriodo::where('indicador_id', $indicador->id)->where('periodo', 1)->first();
        $this->assertEquals(30.0, (float) $p1->meta_periodo);
        $this->assertDatabaseCount('revisiones_meta', 1);
        $revision = RevisionMeta::first();
        $this->assertSame('Reasignación por demanda real', $revision->justificacion);
        $this->assertEquals($this->user->id, $revision->user_id);
    }
}
