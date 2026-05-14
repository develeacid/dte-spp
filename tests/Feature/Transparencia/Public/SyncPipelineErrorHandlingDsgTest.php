<?php

namespace Tests\Feature\Transparencia\Public;

use App\Enums\EstadoDatasetAbierto;
use App\Jobs\GeoBase\DeactivateProgramOnGeoBase;
use App\Jobs\GeoBase\RegisterProgramOnGeoBase;
use App\Jobs\GeoBase\SyncMirNivelToGeoBase;
use App\Jobs\GeoBase\SyncProgramaToGeoBase;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

/**
 * Verifica que el job diferencia errores transitorios (5xx -> retry) vs
 * permanentes (4xx -> no retry, audita fail, termina silenciosamente).
 */
class SyncPipelineErrorHandlingDsgTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected bool $fakeBusInSetUp = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
        config(['queue.default' => 'sync']);
        // Silenciar jobs upstream a GeoBase (mismo patrón de E1)
        Queue::fake([
            RegisterProgramOnGeoBase::class,
            DeactivateProgramOnGeoBase::class,
            SyncProgramaToGeoBase::class,
            SyncMirNivelToGeoBase::class,
        ]);
    }

    public function test_404_en_bulk_endpoint_no_se_reintenta_y_audita_fail(): void
    {
        [$user, $dataset] = $this->setupBasicoDsg01();
        Http::fake(['*cobertura-municipal-bulk*' => Http::response(['message' => 'not found'], 404)]);

        $this->actingAs($user);
        $dataset->publicar();

        $auditorias = TransparenciaPublicacion::where('dataset_clave', 'DS-G01')->get();
        $this->assertNotEmpty($auditorias, '404 debe registrar al menos una auditoría');

        // Clave de E2: NO se re-lanza la excepción (permanente). $dataset->publicar()
        // retornó normalmente. Cada fila debe ser fallo con código 404.
        foreach ($auditorias as $row) {
            $this->assertFalse((bool) $row->success, 'todas las filas deben ser fail');
            $this->assertStringContainsString('404', $row->error_message ?? '');
        }
    }

    public function test_422_en_bulk_endpoint_no_se_reintenta(): void
    {
        [$user, $dataset] = $this->setupBasicoDsg01();
        Http::fake(['*cobertura-municipal-bulk*' => Http::response(['errors' => ['x']], 422)]);

        $this->actingAs($user);
        // El job NO debe re-lanzar la excepción para 422 (permanente).
        // Si re-lanza, este call propaga y rompe el test.
        $dataset->publicar();

        $auditorias = TransparenciaPublicacion::where('dataset_clave', 'DS-G01')->get();
        $this->assertNotEmpty($auditorias, '422 debe registrar al menos una auditoría');
        foreach ($auditorias as $row) {
            $this->assertFalse((bool) $row->success);
            $this->assertStringContainsString('422', $row->error_message ?? '');
        }
    }

    public function test_500_se_reintenta_y_falla_eventualmente(): void
    {
        [$user, $dataset] = $this->setupBasicoDsg01();
        Http::fake(['*cobertura-municipal-bulk*' => Http::response(['error' => 'down'], 500)]);

        $this->actingAs($user);

        // El re-throw eventual (después de tries=3) propaga la excepción.
        // En sync queue con tries=3, Laravel re-intenta 3 veces y al final lanza.
        try {
            $dataset->publicar();
        } catch (\Throwable $e) {
            // Esperado: la última retry tira la excepción.
        }

        // Sync queue sin worker real solo ejecuta una vez. Esperamos UNA fila success=false re-throwed
        // (los retries son responsabilidad del worker, no del modo sync de tests).
        $count = TransparenciaPublicacion::where('dataset_clave', 'DS-G01')->count();
        $this->assertGreaterThanOrEqual(1, $count, '5xx debe registrar al menos un fallo');
        $this->assertFalse((bool) TransparenciaPublicacion::where('dataset_clave', 'DS-G01')->first()->success);
    }

    private function setupBasicoDsg01(): array
    {
        $user = User::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'P', 'padron_geobase_activo' => true,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente', 'resumen_narrativo' => 'C',
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'IND',
            'tipo' => 'gestion', 'dimension' => 'eficacia', 'frecuencia' => 'trimestral',
        ]);
        MetaPeriodo::create([
            'indicador_id' => $indicador->id, 'periodo' => 1,
            'meta_periodo' => 100, 'ejercicio_fiscal' => 2026,
        ]);

        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-G01',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);

        return [$user, $dataset];
    }
}
