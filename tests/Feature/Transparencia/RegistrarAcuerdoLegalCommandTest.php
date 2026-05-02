<?php

namespace Tests\Feature\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarAcuerdoLegalCommandTest extends TestCase
{
    use RefreshDatabase;

    private function archivoTemporal(string $contenido, string $nombre = 'test.md'): string
    {
        $ruta = storage_path('app/legal/'.$nombre);
        @mkdir(dirname($ruta), 0755, true);
        file_put_contents($ruta, $contenido);

        return $ruta;
    }

    protected function tearDown(): void
    {
        $dir = storage_path('app/legal');
        if (is_dir($dir)) {
            foreach (glob($dir.'/*') as $f) {
                @unlink($f);
            }
        }
        parent::tearDown();
    }

    public function test_falla_si_ruta_no_existe(): void
    {
        $this->artisan('legal:registrar-acuerdo', ['ruta' => '/tmp/no-existe-'.uniqid().'.md'])
            ->expectsOutputToContain('no existe')
            ->assertExitCode(1);
    }

    public function test_actualiza_ds00_con_hash_y_ruta(): void
    {
        DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-00',
            'periodo' => null,
            'status' => 'borrador',
            'hash_sha256' => str_repeat('0', 64),
        ]);

        $ruta = $this->archivoTemporal('contenido del acuerdo', 'acuerdo-firmado-2026-Q2.pdf');

        $this->artisan('legal:registrar-acuerdo', ['ruta' => $ruta])
            ->assertExitCode(0);

        $ds00 = DatasetAbierto::where('dataset_clave', 'DS-00')->first();
        $this->assertEquals(hash_file('sha256', $ruta), $ds00->hash_sha256);
        $this->assertStringEndsWith('acuerdo-firmado-2026-Q2.pdf', $ds00->ruta_archivo);
    }

    public function test_marca_publicado_si_ruta_es_pdf_firmado(): void
    {
        DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-00',
            'periodo' => null,
            'status' => 'borrador',
        ]);

        $ruta = $this->archivoTemporal('PDF binario', 'acuerdo-firmado-2026.pdf');

        $this->artisan('legal:registrar-acuerdo', ['ruta' => $ruta])->assertExitCode(0);

        $ds00 = DatasetAbierto::where('dataset_clave', 'DS-00')->first();
        $this->assertEquals(EstadoDatasetAbierto::PUBLICADO, $ds00->status);
        $this->assertNotNull($ds00->publicado_en);
    }

    public function test_mantiene_borrador_si_ruta_es_markdown_en_repo(): void
    {
        DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-00',
            'periodo' => null,
            'status' => 'borrador',
        ]);

        $ruta = base_path('docs/legal/clasificacion-informacion.md');

        $this->artisan('legal:registrar-acuerdo', ['ruta' => $ruta])->assertExitCode(0);

        $ds00 = DatasetAbierto::where('dataset_clave', 'DS-00')->first();
        $this->assertEquals(EstadoDatasetAbierto::BORRADOR, $ds00->status);
        $this->assertNull($ds00->publicado_en);
    }

    public function test_es_idempotente_si_hash_no_cambia(): void
    {
        $ruta = $this->archivoTemporal('mismo contenido', 'acuerdo-firmado-x.pdf');
        $hash = hash_file('sha256', $ruta);

        DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-00',
            'periodo' => null,
            'status' => 'publicado',
            'publicado_en' => now()->subDay(),
            'hash_sha256' => $hash,
            'ruta_archivo' => 'storage/app/legal/acuerdo-firmado-x.pdf',
        ]);

        $publicadoEnAntes = DatasetAbierto::where('dataset_clave', 'DS-00')->first()->publicado_en;

        $this->artisan('legal:registrar-acuerdo', ['ruta' => $ruta])
            ->expectsOutputToContain('Sin cambios')
            ->assertExitCode(0);

        $publicadoEnDespues = DatasetAbierto::where('dataset_clave', 'DS-00')->first()->publicado_en;
        $this->assertEquals($publicadoEnAntes->toIso8601String(), $publicadoEnDespues->toIso8601String());
    }

    public function test_recalcula_hash_si_archivo_cambia(): void
    {
        DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-00',
            'periodo' => null,
            'status' => 'borrador',
            'hash_sha256' => str_repeat('a', 64),
        ]);

        $ruta = $this->archivoTemporal('v1', 'acuerdo-firmado-y.pdf');
        $this->artisan('legal:registrar-acuerdo', ['ruta' => $ruta])->assertExitCode(0);

        $hashV1 = DatasetAbierto::where('dataset_clave', 'DS-00')->first()->hash_sha256;

        file_put_contents($ruta, 'v2 modificado');

        $this->artisan('legal:registrar-acuerdo', ['ruta' => $ruta])->assertExitCode(0);

        $hashV2 = DatasetAbierto::where('dataset_clave', 'DS-00')->first()->hash_sha256;
        $this->assertNotEquals($hashV1, $hashV2);
        $this->assertEquals(hash_file('sha256', $ruta), $hashV2);
    }

    public function test_falla_con_status_flag_invalido(): void
    {
        $ruta = $this->archivoTemporal('contenido', 'cualquier.pdf');

        $this->artisan('legal:registrar-acuerdo', ['ruta' => $ruta, '--status' => 'inventado'])
            ->expectsOutputToContain('--status inválido')
            ->assertExitCode(1);
    }
}
