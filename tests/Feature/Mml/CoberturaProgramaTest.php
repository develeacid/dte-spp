<?php

namespace Tests\Feature\Mml;

use App\Enums\SystemRole;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\CoberturaPrograma;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CoberturaProgramaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PadronPermissionsSeeder::class);

        Queue::fake();
    }

    private function programaConComponente(bool $padronActivo = true): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => $padronActivo,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'Componente C1',
            'orden' => 1,
        ]);

        return $programa;
    }

    private function userPlaneador(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        return $user;
    }

    public function test_estado_inactivo_si_programa_sin_padron_activo(): void
    {
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'inactivo')
            ->assertSee('El padrón de este programa no está activo en GeoBase');
    }
}
