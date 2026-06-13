<?php

namespace Tests\Feature\Mml;

use App\Enums\EstadoCierreFiscal;
use App\Livewire\Mml\CierreFiscalPanel;
use App\Models\Presupuesto\CierreFiscal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CierreFiscalPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $planeador;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole('planeador');
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PC-001',
            'team_id' => $this->planeador->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);
    }

    public function test_avanzar_mueve_a_consolidacion(): void
    {
        Livewire::actingAs($this->planeador)
            ->test(CierreFiscalPanel::class, ['programa' => $this->programa])
            ->call('avanzar');

        $cierre = CierreFiscal::where('programa_id', $this->programa->id)->first();
        $this->assertSame(EstadoCierreFiscal::CONSOLIDACION, $cierre->estado);
    }

    public function test_avanzar_a_firma_sin_iaff_muestra_error(): void
    {
        $cierre = CierreFiscal::create([
            'programa_id' => $this->programa->id, 'ejercicio_fiscal' => 2026,
            'estado' => EstadoCierreFiscal::CONSOLIDACION,
        ]);

        Livewire::actingAs($this->planeador)
            ->test(CierreFiscalPanel::class, ['programa' => $this->programa])
            ->call('avanzar');

        // El gate impide avanzar a FIRMA sin IAFF Q4 firmado: el estado no cambia.
        $this->assertSame(EstadoCierreFiscal::CONSOLIDACION, $cierre->fresh()->estado);
    }

    public function test_operador_no_accede_a_la_ruta(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $this->actingAs($operador)
            ->get(route('mml.cierre-fiscal', $this->programa))
            ->assertForbidden();
    }

    public function test_planeador_accede_a_la_ruta(): void
    {
        $this->actingAs($this->planeador)
            ->get(route('mml.cierre-fiscal', $this->programa))
            ->assertOk();
    }
}
