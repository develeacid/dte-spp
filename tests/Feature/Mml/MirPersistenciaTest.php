<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Enums\DimensionIndicador;
use App\Enums\EstadoPrograma;
use App\Enums\FrecuenciaMedicion;
use App\Enums\OrigenPrograma;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\User;
use App\Services\Mml\MirPersistenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MirPersistenciaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private MirPersistenciaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->service = new MirPersistenciaService();
    }

    public function test_creates_programa_importado(): void
    {
        $data = $this->minimalData();

        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id);

        $this->assertDatabaseHas('programa_presupuestarios', [
            'id' => $programa->id,
            'nombre' => 'Programa Test',
            'clave' => 'PT001',
            'origen' => OrigenPrograma::IMPORTADO->value,
            'estado' => EstadoPrograma::BORRADOR->value,
            'team_id' => $this->user->currentTeam->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_creates_niveles_with_hierarchy(): void
    {
        $data = new ImportedMirData(
            nombre: 'Prog',
            clave: 'P01',
            ejercicioFiscal: 2026,
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => 'Contribuir a...',
                    'supuestos' => 'Supuesto fin',
                    'orden' => 1,
                    'indicadores' => [$this->completeIndicator()],
                ],
                [
                    'tipo_nivel' => 'componente',
                    'resumen_narrativo' => 'Componente 1',
                    'supuestos' => 'Supuesto comp',
                    'orden' => 1,
                    'indicadores' => [$this->completeIndicator()],
                ],
                [
                    'tipo_nivel' => 'actividad',
                    'resumen_narrativo' => 'Actividad 1.1',
                    'supuestos' => 'Supuesto act',
                    'orden' => 1,
                    'componente_idx' => 1, // points to nivel index 1 (the componente)
                    'indicadores' => [$this->completeIndicator()],
                ],
            ],
        );

        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id);

        $niveles = MirNivel::where('programa_presupuestario_id', $programa->id)->get();
        $this->assertCount(3, $niveles);

        $componente = $niveles->firstWhere('tipo_nivel', TipoNivelMir::COMPONENTE);
        $actividad = $niveles->firstWhere('tipo_nivel', TipoNivelMir::ACTIVIDAD);

        $this->assertNotNull($componente);
        $this->assertNotNull($actividad);
        $this->assertEquals($componente->id, $actividad->componente_id);
    }

    public function test_creates_indicadores_and_medios(): void
    {
        $data = new ImportedMirData(
            nombre: 'Prog',
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => 'Fin',
                    'supuestos' => null,
                    'orden' => 1,
                    'indicadores' => [
                        [
                            'nombre' => 'Ind Test',
                            'formula_texto' => 'A/B*100',
                            'tipo' => 'estrategico',
                            'dimension' => 'eficacia',
                            'frecuencia' => 'anual',
                            'sentido' => 'ascendente',
                            'linea_base' => 50,
                            'meta' => 80,
                            'variables' => [
                                ['simbolo' => 'A', 'nombre' => 'Numerador'],
                                ['simbolo' => 'B', 'nombre' => 'Denominador'],
                            ],
                            'medios' => [
                                ['nombre' => 'Informe anual', 'fuente' => 'DGPP'],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id);
        $nivel = MirNivel::where('programa_presupuestario_id', $programa->id)->first();

        $this->assertCount(1, $nivel->indicadores);
        $ind = $nivel->indicadores->first();
        $this->assertEquals('Ind Test', $ind->nombre);
        $this->assertEquals('A/B*100', $ind->formula_texto);
        $this->assertCount(2, $ind->variables);
        $this->assertCount(1, $ind->mediosVerificacion);
        $this->assertEquals('Informe anual', $ind->mediosVerificacion->first()->nombre);
    }

    public function test_critical_gaps_disable_seguimiento(): void
    {
        $data = new ImportedMirData(
            nombre: 'Prog',
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => 'Fin',
                    'supuestos' => null,
                    'orden' => 1,
                    'indicadores' => [
                        $this->completeIndicator(), // idx 0 — no critical gap
                        [
                            'nombre' => 'Ind sin formula',
                            'formula_texto' => null,
                            'tipo' => 'estrategico',
                            'dimension' => 'eficacia',
                            'frecuencia' => 'anual',
                            'variables' => [],
                            'medios' => [['nombre' => 'Informe']],
                        ], // idx 1 — has critical gap
                    ],
                ],
            ],
        );

        $diagnostico = [
            [
                'nivel_idx' => 0,
                'indicador_idx' => 1,
                'campo' => 'formula_texto',
                'severidad' => 'critico',
                'mensaje' => 'Falta formula',
            ],
        ];

        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id, $diagnostico);
        $indicadores = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $programa->id))
            ->orderBy('orden')
            ->get();

        $this->assertTrue($indicadores[0]->activo_seguimiento);
        $this->assertFalse($indicadores[1]->activo_seguimiento);
    }

    public function test_maps_accented_enums(): void
    {
        $data = new ImportedMirData(
            nombre: 'Prog',
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => 'Fin',
                    'supuestos' => null,
                    'orden' => 1,
                    'indicadores' => [
                        [
                            'nombre' => 'Ind accented',
                            'formula_texto' => 'X/Y',
                            'tipo' => 'Estratégico',
                            'dimension' => 'Economía',
                            'frecuencia' => 'anual',
                            'sentido' => 'ascendente',
                            'variables' => [],
                            'medios' => [['nombre' => 'Doc']],
                        ],
                    ],
                ],
            ],
        );

        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id);
        $ind = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $programa->id))->first();

        $this->assertEquals(TipoIndicador::ESTRATEGICO, $ind->tipo);
        $this->assertEquals(DimensionIndicador::ECONOMIA, $ind->dimension);
    }

    private function minimalData(): ImportedMirData
    {
        return new ImportedMirData(
            nombre: 'Programa Test',
            clave: 'PT001',
            ejercicioFiscal: 2026,
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => 'Contribuir a mejorar la calidad de vida',
                    'supuestos' => 'Condiciones estables',
                    'orden' => 1,
                    'indicadores' => [$this->completeIndicator()],
                ],
            ],
        );
    }

    private function completeIndicator(): array
    {
        return [
            'nombre' => 'Indicador completo',
            'formula_texto' => 'A/B*100',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'anual',
            'sentido' => 'ascendente',
            'linea_base' => 0,
            'meta' => 100,
            'variables' => [
                ['simbolo' => 'A', 'nombre' => 'Numerador'],
            ],
            'medios' => [
                ['nombre' => 'Informe', 'fuente' => null],
            ],
        ];
    }
}
