<?php

namespace Tests\Feature\Transparencia;

use App\Models\Transparencia\DatasetAbierto;
use Database\Seeders\Transparencia\DatasetsCatalogoSeeder;
use Database\Seeders\PoliticaClasificacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatasetsCatalogoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_10_plantillas_idempotente(): void
    {
        $this->seed(DatasetsCatalogoSeeder::class);
        $this->assertSame(10, DatasetAbierto::plantillas()->count());

        $this->seed(DatasetsCatalogoSeeder::class);
        $this->assertSame(10, DatasetAbierto::plantillas()->count());
    }

    public function test_dcat_metadata_base_presente_en_todas(): void
    {
        $this->seed(DatasetsCatalogoSeeder::class);

        DatasetAbierto::plantillas()->get()->each(function ($ds) {
            $this->assertArrayHasKey('dct:publisher', $ds->dcat_metadata);
            $this->assertArrayHasKey('dct:license', $ds->dcat_metadata);
            $this->assertArrayHasKey('dct:language', $ds->dcat_metadata);
            $this->assertArrayHasKey('dcat:contactPoint', $ds->dcat_metadata);
        });
    }

    public function test_no_sobrescribe_ds_00_politica(): void
    {
        $this->seed(PoliticaClasificacionSeeder::class);
        $ds00Hash = DatasetAbierto::where('dataset_clave', 'DS-00')->first()->hash_sha256;

        $this->seed(DatasetsCatalogoSeeder::class);

        $ds00After = DatasetAbierto::where('dataset_clave', 'DS-00')->first();
        $this->assertSame($ds00Hash, $ds00After->hash_sha256);
    }

    public function test_claves_son_DS_01_a_DS_06_y_DS_G01_a_DS_G04(): void
    {
        $this->seed(DatasetsCatalogoSeeder::class);

        $claves = DatasetAbierto::plantillas()->pluck('dataset_clave')->sort()->values()->all();
        $this->assertSame(
            ['DS-01', 'DS-02', 'DS-03', 'DS-04', 'DS-05', 'DS-06', 'DS-G01', 'DS-G02', 'DS-G03', 'DS-G04'],
            $claves
        );
    }
}
