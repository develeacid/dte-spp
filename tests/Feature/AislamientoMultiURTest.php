<?php

namespace Tests\Feature;

use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AislamientoMultiURTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que existan roles (RolesAndPermissionsSeeder define: admin, planeador, operador)
        $this->seed(RolesAndPermissionsSeeder::class);

        // Registrar la ruta de prueba UNA SOLA VEZ en setUp para evitar problemas
        // de aislamiento entre tests cuando se definen rutas con Route::get() dentro
        // de cada método de test individual.
        Route::get('/test-programa/{programa}', function (ProgramaPresupuestario $programa) {
            return 'OK';
        })->middleware(['web', 'auth', 'ur.aislamiento']);
    }

    public function test_usuario_sin_relacion_es_bloqueado(): void
    {
        $user = User::factory()->create();
        $user->assignRole('planeador'); // Rol base (existe en RolesAndPermissionsSeeder)

        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->current_team_id = $team->id;
        $user->save();

        $programa = ProgramaPresupuestario::create(['nombre' => 'Prog Test', 'clave' => 'P-001']);

        $this->actingAs($user)
            ->get("/test-programa/{$programa->id}")
            ->assertForbidden();
    }

    public function test_coordinadora_accede(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->current_team_id = $team->id;
        $user->save();

        $programa = ProgramaPresupuestario::create(['nombre' => 'Prog Test', 'clave' => 'P-002']);

        // Crear relación con rol Coordinadora
        $programa->equipos()->attach($team->id, ['rol' => 'coordinadora']);

        $this->actingAs($user)
            ->get("/test-programa/{$programa->id}")
            ->assertOk();
    }
}
