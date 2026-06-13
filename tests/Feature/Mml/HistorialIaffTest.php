<?php

namespace Tests\Feature\Mml;

use App\Livewire\Mml\HistorialIaff;
use App\Models\Presupuesto\Iaff;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HistorialIaffTest extends TestCase
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
        ]);
    }

    private function iaff(array $overrides = []): Iaff
    {
        return Iaff::create(array_merge([
            'programa_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026, 'trimestre' => 1,
            'snapshot_payload' => [], 'hash_sha256' => str_repeat('a', 64),
            'generado_en' => now(), 'generado_por' => $this->planeador->id,
        ], $overrides));
    }

    public function test_lista_los_iaff_del_programa(): void
    {
        $this->iaff(['trimestre' => 1]);
        $this->iaff(['trimestre' => 2]);

        Livewire::actingAs($this->planeador)
            ->test(HistorialIaff::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('IAFF');
    }

    public function test_firmar_congela_el_iaff(): void
    {
        $iaff = $this->iaff();

        Livewire::actingAs($this->planeador)
            ->test(HistorialIaff::class, ['programa' => $this->programa])
            ->call('firmar', $iaff->id);

        $this->assertNotNull($iaff->fresh()->firmado_en);
    }

    public function test_operador_no_puede_acceder_a_la_ruta(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $this->actingAs($operador)
            ->get(route('mml.iaff', $this->programa))
            ->assertForbidden();
    }

    public function test_planeador_accede_a_la_ruta(): void
    {
        $this->actingAs($this->planeador)
            ->get(route('mml.iaff', $this->programa))
            ->assertOk();
    }
}
