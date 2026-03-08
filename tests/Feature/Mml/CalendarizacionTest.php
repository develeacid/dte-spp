<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Enums\EstadoPrograma;
use App\Enums\FrecuenciaMedicion;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Mml\CalendarizacionService;
use App\Services\Mml\MirPersistenciaService;
use App\Models\Mml\ImportacionReporte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarizacionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CalendarizacionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->service = new CalendarizacionService();
    }

    public function test_genera_12_periodos_para_mensual(): void
    {
        $programa = $this->crearProgramaConIndicador('mensual', 120.0);

        $resultado = $this->service->generar($programa);

        $this->assertCount(1, $resultado);
        $this->assertCount(12, $resultado[0]['periodos']);

        foreach ($resultado[0]['periodos'] as $periodo) {
            $this->assertEquals(10.0, $periodo['meta_periodo']);
        }
    }

    public function test_genera_4_periodos_para_trimestral(): void
    {
        $programa = $this->crearProgramaConIndicador('trimestral', 100.0);

        $resultado = $this->service->generar($programa);

        $this->assertCount(1, $resultado);
        $this->assertCount(4, $resultado[0]['periodos']);

        foreach ($resultado[0]['periodos'] as $periodo) {
            $this->assertEquals(25.0, $periodo['meta_periodo']);
        }
    }

    public function test_genera_1_periodo_para_anual(): void
    {
        $programa = $this->crearProgramaConIndicador('anual', 500.0);

        $resultado = $this->service->generar($programa);

        $this->assertCount(1, $resultado);
        $this->assertCount(1, $resultado[0]['periodos']);
        $this->assertEquals(500.0, $resultado[0]['periodos'][0]['meta_periodo']);
    }

    public function test_ignora_indicadores_sin_meta(): void
    {
        $programa = $this->crearProgramaConIndicador('trimestral', null);

        $resultado = $this->service->generar($programa);

        $this->assertCount(0, $resultado);
    }

    public function test_ignora_indicadores_inactivos(): void
    {
        $programa = $this->crearProgramaConIndicador('trimestral', 100.0, activoSeguimiento: false);

        $resultado = $this->service->generar($programa);

        $this->assertCount(0, $resultado);
    }

    public function test_confirmar_persiste_metas_periodo(): void
    {
        $programa = $this->crearProgramaConIndicador('trimestral', 100.0);
        $propuesta = $this->service->generar($programa);

        $this->service->confirmar($programa, $propuesta, 2026);

        $indicadorId = $propuesta[0]['indicador_id'];

        $this->assertDatabaseCount('metas_periodo', 4);

        foreach ([1, 2, 3, 4] as $periodo) {
            $this->assertDatabaseHas('metas_periodo', [
                'indicador_id' => $indicadorId,
                'periodo' => $periodo,
                'ejercicio_fiscal' => 2026,
                'activo' => true,
            ]);
        }

        // Verify correct values
        $metas = MetaPeriodo::where('indicador_id', $indicadorId)->get();
        foreach ($metas as $meta) {
            $this->assertEquals(25.0, (float) $meta->meta_periodo);
        }
    }

    /**
     * Helper: create a programa with a single indicator via MirPersistenciaService.
     */
    private function crearProgramaConIndicador(
        string $frecuencia,
        ?float $meta,
        bool $activoSeguimiento = true,
    ): ProgramaPresupuestario {
        $data = new ImportedMirData(
            nombre: 'Programa Calendarizacion Test',
            clave: 'PCT01',
            ejercicioFiscal: 2026,
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => 'Contribuir al bienestar',
                    'supuestos' => 'Estabilidad',
                    'orden' => 1,
                    'indicadores' => [
                        [
                            'nombre' => 'Indicador Test',
                            'formula_texto' => 'A/B*100',
                            'tipo' => 'estrategico',
                            'dimension' => 'eficacia',
                            'frecuencia' => $frecuencia,
                            'sentido' => 'ascendente',
                            'linea_base' => 0,
                            'meta' => $meta,
                            'variables' => [],
                            'medios' => [],
                        ],
                    ],
                ],
            ],
        );

        $reporte = ImportacionReporte::create([
            'team_id' => $this->user->currentTeam->id,
            'archivo_original' => 'test-calendarizacion.md',
            'formato' => 'md',
            'datos_parseados' => $data->toArray(),
            'diagnostico' => null,
            'estado' => 'procesado',
            'created_by' => $this->user->id,
        ]);

        $programa = app(MirPersistenciaService::class)->persistir(
            $data,
            $reporte->team_id,
            $reporte->created_by,
            $reporte->diagnostico,
        );

        // Update activo_seguimiento if needed
        if (!$activoSeguimiento) {
            Indicador::whereIn('mir_nivel_id', $programa->mirNiveles()->pluck('id'))
                ->update(['activo_seguimiento' => false]);
        }

        return $programa;
    }
}
