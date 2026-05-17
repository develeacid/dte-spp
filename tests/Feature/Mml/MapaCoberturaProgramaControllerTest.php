<?php

namespace Tests\Feature\Mml;

use App\Enums\SystemRole;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MapaCoberturaProgramaControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $pngFixture;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PadronPermissionsSeeder::class);

        Queue::fake();
        Cache::flush();

        // 1×1 transparent PNG (~70 bytes)
        $this->pngFixture = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVR4nGNgAAIAAAUAAen63NgAAAAASUVORK5CYII=');
    }

    private function programaActivo(): ProgramaPresupuestario
    {
        return ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
    }

    private function userPlaneador(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        return $user;
    }

    #[Test]
    public function devuelve_403_si_usuario_sin_permiso_ver_padron(): void
    {
        Http::preventStrayRequests();

        // RESPONSABLE_DATOS_ABIERTOS es el único rol que NO recibe ver_padron
        // por PadronPermissionsSeeder (planeador/operador/analistas sí lo tienen),
        // mismo patrón que RouteCoberturaTest::test_rol_sin_ver_padron_recibe_403.
        // El rol se crea inline porque vive en TransparenciaPermissionsSeeder y
        // no queremos modificar el setUp() compartido por tests posteriores.
        Role::findOrCreate(SystemRole::RESPONSABLE_DATOS_ABIERTOS->value, 'web');

        $programa = $this->programaActivo();
        $userSinPermiso = User::factory()->withPersonalTeam()->create();
        $userSinPermiso->assignRole(SystemRole::RESPONSABLE_DATOS_ABIERTOS->value);

        $this->actingAs($userSinPermiso)
            ->get("/mml/programas/{$programa->id}/cobertura/mapa.png")
            ->assertForbidden();
    }
}
