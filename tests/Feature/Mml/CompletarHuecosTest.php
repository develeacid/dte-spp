<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Livewire\Mml\CompletarHuecos;
use App\Models\Mml\ImportacionReporte;
use App\Models\Mml\Indicador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompletarHuecosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    public function test_persists_on_mount_when_no_programa(): void
    {
        $reporte = $this->createReporte();

        $this->assertNull($reporte->programa_presupuestario_id);

        Livewire::actingAs($this->user)
            ->test(CompletarHuecos::class, ['importacion' => $reporte->id]);

        $reporte->refresh();
        $this->assertNotNull($reporte->programa_presupuestario_id);
        $this->assertDatabaseHas('programa_presupuestarios', [
            'id' => $reporte->programa_presupuestario_id,
            'origen' => 'importado',
            'estado' => 'borrador',
        ]);
    }

    public function test_corregir_campo_updates_and_reactivates(): void
    {
        $reporte = $this->createReporte([
            [
                'nivel_idx' => 0,
                'indicador_idx' => 0,
                'campo' => 'formula_texto',
                'severidad' => 'critico',
                'mensaje' => 'Falta formula',
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CompletarHuecos::class, ['importacion' => $reporte->id]);

        // Find the indicator that was created with activo_seguimiento = false
        $reporte->refresh();
        $indicador = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $reporte->programa_presupuestario_id))
            ->first();

        $this->assertFalse($indicador->activo_seguimiento);

        // Correct the formula field
        $component->call('corregirCampo', $indicador->id, 'formula_texto', 'A/B*100');

        $indicador->refresh();
        $this->assertEquals('A/B*100', $indicador->formula_texto);
        // The indicator in the fixture has tipo, dimension, frecuencia set, plus now formula — all critical filled
        $this->assertTrue($indicador->activo_seguimiento);
    }

    public function test_finalizar_changes_estado(): void
    {
        // Register the vincular route (will be implemented in a future task)
        \Illuminate\Support\Facades\Route::get('/mml/importar/{importacion}/vincular', fn () => '')
            ->name('mml.importar.vincular')
            ->middleware(['web']);

        $reporte = $this->createReporte();

        Livewire::actingAs($this->user)
            ->test(CompletarHuecos::class, ['importacion' => $reporte->id])
            ->call('finalizar')
            ->assertRedirect(route('mml.importar.vincular', ['importacion' => $reporte->id]));

        $reporte->refresh();
        $this->assertEquals('procesado', $reporte->estado);
    }

    /**
     * Helper to create a reporte with standard test data.
     */
    private function createReporte(?array $diagnostico = null): ImportacionReporte
    {
        $data = new ImportedMirData(
            nombre: 'Programa Test',
            clave: 'PT01',
            ejercicioFiscal: 2026,
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => 'Contribuir a mejorar',
                    'supuestos' => 'Estabilidad',
                    'orden' => 1,
                    'indicadores' => [
                        [
                            'nombre' => 'Indicador 1',
                            'formula_texto' => $diagnostico ? null : 'A/B',
                            'tipo' => 'estrategico',
                            'dimension' => 'eficacia',
                            'frecuencia' => 'anual',
                            'sentido' => 'ascendente',
                            'linea_base' => 0,
                            'meta' => 100,
                            'variables' => [],
                            'medios' => [['nombre' => 'Informe', 'fuente' => null]],
                        ],
                    ],
                ],
            ],
        );

        return ImportacionReporte::create([
            'team_id' => $this->user->currentTeam->id,
            'archivo_original' => 'test.md',
            'formato' => 'md',
            'datos_parseados' => $data->toArray(),
            'diagnostico' => $diagnostico,
            'estado' => 'pendiente',
            'created_by' => $this->user->id,
        ]);
    }
}
