<?php

namespace Tests\Feature\Mml;

use App\Enums\SystemRole;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    #[Test]
    public function devuelve_png_con_status_200_y_content_type_correcto(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response($this->pngFixture, 200)]);
        Http::preventStrayRequests();

        $programa = $this->programaActivo();
        $planeador = $this->userPlaneador();

        $response = $this->actingAs($planeador)
            ->get("/mml/programas/{$programa->id}/cobertura/mapa.png");

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png');
        $this->assertSame($this->pngFixture, $response->getContent());
    }

    #[Test]
    public function envia_filtros_correctos_a_geobase_cuando_period_no_se_pasa(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response($this->pngFixture, 200)]);
        Http::preventStrayRequests();

        $programa = $this->programaActivo();

        $this->actingAs($this->userPlaneador())
            ->get("/mml/programas/{$programa->id}/cobertura/mapa.png");

        Http::assertSent(function ($request) use ($programa) {
            if (! str_contains($request->url(), '/imagen/consulta')) {
                return false;
            }
            $body = $request->data();

            return $body['group_by'] === ['municipio']
                && $body['aggregates'] === ['total_beneficiarios']
                && $body['filters']['program_id'] === $programa->id
                && ! isset($body['filters']['date_from']);
        });
    }

    #[Test]
    public function envia_date_range_cuando_period_se_pasa(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response($this->pngFixture, 200)]);
        Http::preventStrayRequests();

        $programa = $this->programaActivo();

        $this->actingAs($this->userPlaneador())
            ->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=2026-Q2");

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/imagen/consulta')) {
                return false;
            }
            $body = $request->data();

            return ($body['filters']['date_from'] ?? null) === '2026-04-01'
                && ($body['filters']['date_to'] ?? null) === '2026-06-30';
        });
    }

    #[Test]
    public function ignora_period_invalido_y_devuelve_all_time(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response($this->pngFixture, 200)]);
        Http::preventStrayRequests();

        $programa = $this->programaActivo();

        $this->actingAs($this->userPlaneador())
            ->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=invalid-format")
            ->assertOk();

        Http::assertSent(function ($request) {
            $body = $request->data();

            return ! isset($body['filters']['date_from']);
        });
    }

    #[Test]
    public function cachea_60s_dentro_de_misma_clave_programa_period(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response($this->pngFixture, 200)]);
        Http::preventStrayRequests();

        $programa = $this->programaActivo();
        $this->actingAs($this->userPlaneador());

        $r1 = $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=2026-Q2");
        $r2 = $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=2026-Q2");

        $r1->assertOk();
        $r2->assertOk();
        Http::assertSentCount(1);
    }

    #[Test]
    public function periodos_distintos_no_colisionan_en_cache(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response($this->pngFixture, 200)]);
        Http::preventStrayRequests();

        $programa = $this->programaActivo();
        $this->actingAs($this->userPlaneador());

        $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png");                  // all
        $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=2026-Q1");   // Q1
        $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=2026-Q2");   // Q2
        $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=2026-Q1");   // hit

        Http::assertSentCount(3);
    }

    #[Test]
    public function cache_keys_son_por_programa_no_globales(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response($this->pngFixture, 200)]);
        Http::preventStrayRequests();

        $programaA = $this->programaActivo();
        $programaB = $this->programaActivo();
        $this->actingAs($this->userPlaneador());

        $this->get("/mml/programas/{$programaA->id}/cobertura/mapa.png?period=2026-Q2");
        $this->get("/mml/programas/{$programaB->id}/cobertura/mapa.png?period=2026-Q2");

        Http::assertSentCount(2);
    }

    #[Test]
    public function periodos_invalidos_distintos_colapsan_al_mismo_cache_all_time(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response($this->pngFixture, 200)]);
        Http::preventStrayRequests();

        $programa = $this->programaActivo();
        $this->actingAs($this->userPlaneador());

        $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=xxx");
        $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=foo");
        $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png?period=bar");

        // Todos colapsan a la key all-time, así que solo 1 HTTP.
        Http::assertSentCount(1);
    }

    #[Test]
    public function devuelve_503_si_geobase_5xx(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response(['error' => 'down'], 500)]);
        Http::preventStrayRequests();
        Log::spy();

        $programa = $this->programaActivo();

        $this->actingAs($this->userPlaneador())
            ->get("/mml/programas/{$programa->id}/cobertura/mapa.png")
            ->assertStatus(503);

        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function devuelve_503_si_geobase_timeout(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });
        Http::preventStrayRequests();

        $programa = $this->programaActivo();

        $this->actingAs($this->userPlaneador())
            ->get("/mml/programas/{$programa->id}/cobertura/mapa.png")
            ->assertStatus(503);
    }

    #[Test]
    public function no_cachea_respuestas_503(): void
    {
        Http::fake(['*/imagen/consulta' => Http::response(['error' => 'down'], 500)]);
        Http::preventStrayRequests();

        $programa = $this->programaActivo();
        $this->actingAs($this->userPlaneador());

        $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png")->assertStatus(503);
        $this->get("/mml/programas/{$programa->id}/cobertura/mapa.png")->assertStatus(503);

        // GeoBaseClient retries 3x (services.geobase.retry_times). 2 logical calls × 3 retries
        // = 6 HTTP intentos. Si retry queda en 0 (regresión), count caería a 2 y el test fallaría.
        $this->assertGreaterThanOrEqual(4, count(Http::recorded()));
    }
}
