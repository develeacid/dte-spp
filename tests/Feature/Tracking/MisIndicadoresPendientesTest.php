<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\MisIndicadoresPendientes;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MisIndicadoresPendientesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Avance $avance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PC-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa de cobertura',
            'formula_texto' => '(A/B) * 100',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100, 'activo_seguimiento' => true, 'orden' => 1,
        ]);

        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id, 'periodo' => 1,
            'meta_periodo' => 25, 'ejercicio_fiscal' => 2026, 'activo' => true,
            'fecha_apertura' => now()->subDay()->toDateString(),
            'fecha_cierre' => now()->addDays(20)->toDateString(),
        ]);

        $this->avance = Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);
    }

    public function test_avance_pendiente_muestra_link_capturar(): void
    {
        Livewire::actingAs($this->user)
            ->test(MisIndicadoresPendientes::class)
            ->assertSee('Tasa de cobertura')
            ->assertSeeHtml(route('tracking.captura', $this->avance))
            ->assertSee('Capturar')
            ->assertDontSee('Captura no disponible aun');
    }

    public function test_dias_restantes_se_muestran_como_enteros_sin_decimales(): void
    {
        // Fijar "ahora" con fracción de día respecto a la fecha de cierre para que
        // Carbon 3 diffInDays(..., false) devuelva un float (p. ej. -311.03...).
        Carbon::setTestNow(Carbon::parse('2026-01-01 07:13:00'));

        $metaPeriodo = $this->avance->metaPeriodo;
        $metaPeriodo->update(['fecha_cierre' => now()->subDays(311)->toDateString()]);

        try {
            $html = Livewire::actingAs($this->user)
                ->test(MisIndicadoresPendientes::class)
                ->html();
        } finally {
            Carbon::setTestNow();
        }

        // No debe haber un patrón decimal (p. ej. "-311.03244658296 dias") en los días.
        $this->assertDoesNotMatchRegularExpression('/-?\d+\.\d+\s*días/u', $html);
        // Y debe mostrar el entero esperado con la tilde correcta.
        $this->assertStringContainsString('-311 días', $html);
    }

    public function test_avance_de_otro_usuario_no_aparece(): void
    {
        $otro = User::factory()->withPersonalTeam()->create();

        Livewire::actingAs($otro)
            ->test(MisIndicadoresPendientes::class)
            ->assertDontSee('Tasa de cobertura')
            ->assertSee('No tienes indicadores pendientes de captura.');
    }
}
