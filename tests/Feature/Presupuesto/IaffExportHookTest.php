<?php

namespace Tests\Feature\Presupuesto;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class IaffExportHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_exportar_avance_trimestral_pdf_persiste_snapshot_iaff(): void
    {
        Bus::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Hook', 'clave' => 'PH-001',
            'team_id' => $user->currentTeam->id,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Cobertura',
            'formula_texto' => 'A', 'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100, 'activo_seguimiento' => true, 'orden' => 1,
        ]);
        $meta = MetaPeriodo::create([
            'indicador_id' => $indicador->id, 'periodo' => 1, 'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026, 'activo' => true,
        ]);
        Avance::create([
            'meta_periodo_id' => $meta->id, 'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value, 'capturado_por' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('evaluation.exportar.pdf', [
            'tipo' => 'avance-trimestral',
            'id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'trimestre' => 1,
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('iaff', [
            'programa_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'trimestre' => 1,
            'generado_por' => $user->id,
        ]);
    }
}
