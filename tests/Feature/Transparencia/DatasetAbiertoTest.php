<?php

namespace Tests\Feature\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Database\Seeders\PoliticaClasificacionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DatasetAbiertoTest extends TestCase
{
    use RefreshDatabase;

    public function test_aplica_unique_dataset_clave_periodo(): void
    {
        $this->assertTrue(Schema::hasTable('datasets_abiertos'));

        DB::table('datasets_abiertos')->insert([
            'dataset_clave' => 'DS-00',
            'nombre' => 'Test',
            'sistema_origen' => 'spp',
            'periodo' => null,
            'status' => 'borrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('datasets_abiertos')->insert([
            'dataset_clave' => 'DS-00',
            'nombre' => 'Duplicado',
            'sistema_origen' => 'spp',
            'periodo' => null,
            'status' => 'borrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_rechaza_status_fuera_del_enum(): void
    {
        try {
            DB::table('datasets_abiertos')->insert([
                'dataset_clave' => 'DS-00',
                'nombre' => 'Test',
                'sistema_origen' => 'spp',
                'status' => 'inventado',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Expected status CHECK constraint violation');
        } catch (QueryException $e) {
            $this->assertStringContainsString('datasets_abiertos_status_check', $e->getMessage());
        }
    }

    public function test_castea_status_a_enum(): void
    {
        $dataset = DatasetAbierto::factory()->create();

        $this->assertInstanceOf(
            EstadoDatasetAbierto::class,
            $dataset->status
        );
    }

    public function test_logea_actividad_via_logs_activity(): void
    {
        $dataset = DatasetAbierto::factory()->create([
            'nombre' => 'Original',
        ]);

        $dataset->update(['nombre' => 'Modificado']);

        $log = Activity::query()
            ->where('subject_type', DatasetAbierto::class)
            ->where('subject_id', $dataset->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Original', $log->properties['old']['nombre'] ?? null);
        $this->assertSame('Modificado', $log->properties['attributes']['nombre'] ?? null);
        $this->assertSame('DatasetAbierto updated', $log->description);
    }

    public function test_no_logea_cuando_solo_cambian_campos_fuera_del_whitelist(): void
    {
        $dataset = DatasetAbierto::factory()->create([
            'descripcion' => 'desc original',
        ]);

        $logsAntes = Activity::query()
            ->where('subject_type', DatasetAbierto::class)
            ->where('subject_id', $dataset->id)
            ->where('event', 'updated')
            ->count();

        $dataset->update(['descripcion' => 'desc nueva']);

        $logsDespues = Activity::query()
            ->where('subject_type', DatasetAbierto::class)
            ->where('subject_id', $dataset->id)
            ->where('event', 'updated')
            ->count();

        $this->assertSame($logsAntes, $logsDespues, 'Cambios fuera del whitelist no deben generar log');
    }

    public function test_relacion_aprobado_por_devuelve_user(): void
    {
        $user = User::factory()->create();
        $dataset = DatasetAbierto::factory()->create([
            'aprobado_por' => $user->id,
        ]);

        $this->assertTrue($dataset->aprobadoPor->is($user));
    }

    public function test_crea_registro_ds00_via_seeder(): void
    {
        $this->seed(PoliticaClasificacionSeeder::class);

        $dataset = DatasetAbierto::where('dataset_clave', 'DS-00')->first();

        $this->assertNotNull($dataset);
        $this->assertEquals(EstadoDatasetAbierto::BORRADOR, $dataset->status);
        $this->assertNotEmpty($dataset->hash_sha256);
        $this->assertEquals(64, strlen($dataset->hash_sha256));
        $this->assertEquals('docs/legal/clasificacion-informacion.md', $dataset->ruta_archivo);
        $this->assertEquals('spp', $dataset->sistema_origen);
        $this->assertNull($dataset->periodo);
        $this->assertNull($dataset->aprobado_por);
    }

    public function test_seeder_es_idempotente(): void
    {
        $this->seed(PoliticaClasificacionSeeder::class);
        $this->seed(PoliticaClasificacionSeeder::class);

        $this->assertEquals(
            1,
            DatasetAbierto::where('dataset_clave', 'DS-00')->count()
        );
    }
}
