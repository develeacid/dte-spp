<?php

namespace Tests\Feature\Evaluation\Externa;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\Fase0PrerequisitosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regresión: Fase0PrerequisitosSeeder DEBE llamar a
 * EvaluacionExternaPermissionsSeeder. Sin ese registro, tras
 * `migrate:fresh --seed` nadie recibe ver/gestionar_evaluacion_externa
 * → sidebar invisible y 403 universal.
 *
 * A diferencia de IndexTest, este test NO siembra el seeder standalone
 * en setUp() — depende exclusivamente del wiring de Fase0.
 */
class Fase0PermissionsRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fase0_registra_permisos_de_evaluacion_externa(): void
    {
        $this->seed(Fase0PrerequisitosSeeder::class);

        $this->assertTrue(
            Permission::where('name', SystemPermission::VER_EVALUACION_EXTERNA->value)->exists(),
            'El permiso ver_evaluacion_externa debe existir tras Fase0.'
        );

        $planeador = Role::findByName(SystemRole::PLANEADOR->value, 'web');

        $this->assertTrue(
            $planeador->hasPermissionTo(SystemPermission::VER_EVALUACION_EXTERNA->value),
            'El rol planeador debe tener ver_evaluacion_externa tras Fase0.'
        );
    }

    public function test_planeador_puede_acceder_al_index_tras_fase0(): void
    {
        $this->seed(Fase0PrerequisitosSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $this->actingAs($user)->get('/evaluacion/externas')->assertOk();
    }
}
