<?php

namespace Tests\Feature\Transparencia;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

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
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('datasets_abiertos_status_check', $e->getMessage());
        }
    }

    public function test_castea_status_a_enum(): void
    {
        $dataset = \App\Models\Transparencia\DatasetAbierto::factory()->create();

        $this->assertInstanceOf(
            \App\Enums\EstadoDatasetAbierto::class,
            $dataset->status
        );
    }

    public function test_logea_actividad_via_logs_activity(): void
    {
        $dataset = \App\Models\Transparencia\DatasetAbierto::factory()->create([
            'nombre' => 'Original',
        ]);

        $dataset->update(['nombre' => 'Modificado']);

        $log = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', \App\Models\Transparencia\DatasetAbierto::class)
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
        $dataset = \App\Models\Transparencia\DatasetAbierto::factory()->create([
            'descripcion' => 'desc original',
        ]);

        $logsAntes = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', \App\Models\Transparencia\DatasetAbierto::class)
            ->where('subject_id', $dataset->id)
            ->where('event', 'updated')
            ->count();

        $dataset->update(['descripcion' => 'desc nueva']);

        $logsDespues = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', \App\Models\Transparencia\DatasetAbierto::class)
            ->where('subject_id', $dataset->id)
            ->where('event', 'updated')
            ->count();

        $this->assertSame($logsAntes, $logsDespues, 'Cambios fuera del whitelist no deben generar log');
    }

    public function test_relacion_aprobado_por_devuelve_user(): void
    {
        $user = \App\Models\User::factory()->create();
        $dataset = \App\Models\Transparencia\DatasetAbierto::factory()->create([
            'aprobado_por' => $user->id,
        ]);

        $this->assertTrue($dataset->aprobadoPor->is($user));
    }
}
