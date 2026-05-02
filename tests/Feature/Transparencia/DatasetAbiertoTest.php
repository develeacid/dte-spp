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
}
