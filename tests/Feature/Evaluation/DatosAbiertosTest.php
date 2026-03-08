<?php

namespace Tests\Feature\Evaluation;

use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Evaluation\DatosAbiertosService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class DatosAbiertosTest extends TestCase
{
    use RefreshDatabase;

    private User $planeador;

    private ProgramaPresupuestario $programa;

    private EvaluacionPrograma $evaluacion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole('planeador');

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Educación básica',
            'clave' => 'E001',
            'team_id' => $this->planeador->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);

        $this->evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 85.5000,
            'indicadores_evaluados' => 8,
            'indicadores_no_evaluados' => 2,
            'conteo_semaforos' => ['verde' => 5, 'amarillo' => 2, 'rojo' => 1, 'sin_dato' => 0],
            'desglose_niveles' => [],
            'configuracion_calculo' => [],
        ]);
    }

    public function test_csv_export_es_utf8_con_columnas_correctas(): void
    {
        $service = app(DatosAbiertosService::class);
        $csv = $service->exportarCsv(2026);

        // Verify UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        // Verify header columns
        $lines = explode("\n", $csv);
        $header = str_replace("\xEF\xBB\xBF", '', $lines[0]);
        $this->assertEquals(
            'programa_clave,programa_nombre,unidad_responsable,indice_eficacia,semaforos_verde,semaforos_amarillo,semaforos_rojo,semaforos_sin_dato,indicadores_evaluados,indicadores_no_evaluados,fecha_calculo',
            $header
        );

        // Verify data row exists with correct values
        $this->assertStringContainsString('E001', $csv);
        $this->assertStringContainsString('85.5', $csv);
    }

    public function test_json_export_tiene_metadata_y_estructura_data(): void
    {
        $service = app(DatosAbiertosService::class);
        $result = $service->exportarJson(2026);

        // Verify metadata
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals('1.0', $result['metadata']['version']);
        $this->assertEquals(2026, $result['metadata']['ejercicio_fiscal']);
        $this->assertEquals(1, $result['metadata']['total_registros']);
        $this->assertArrayHasKey('fecha_generacion', $result['metadata']);

        // Verify data
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);

        $registro = $result['data'][0];
        $this->assertEquals('E001', $registro['programa_clave']);
        $this->assertEquals('Educación básica', $registro['programa_nombre']);
        $this->assertEquals(85.5, $registro['indice_eficacia']);
        $this->assertEquals(5, $registro['semaforos_verde']);
        $this->assertEquals(8, $registro['indicadores_evaluados']);
    }

    public function test_zip_contiene_tres_archivos(): void
    {
        $service = app(DatosAbiertosService::class);
        $zipPath = $service->generarZip(2026);

        $this->assertFileExists($zipPath);

        $zip = new ZipArchive;
        $zip->open($zipPath);

        $this->assertEquals(3, $zip->numFiles);
        $this->assertNotFalse($zip->locateName('datos_2026.csv'));
        $this->assertNotFalse($zip->locateName('datos_2026.json'));
        $this->assertNotFalse($zip->locateName('diccionario_datos.csv'));

        $zip->close();

        // Clean up
        @unlink($zipPath);
    }

    public function test_diccionario_documenta_todas_las_columnas(): void
    {
        $service = app(DatosAbiertosService::class);
        $diccionario = $service->generarDiccionario();

        // BOM present
        $this->assertStringStartsWith("\xEF\xBB\xBF", $diccionario);

        // Header
        $this->assertStringContainsString('campo,tipo,descripcion,ejemplo', $diccionario);

        // All fields documented
        $campos = [
            'programa_clave', 'programa_nombre', 'unidad_responsable',
            'indice_eficacia', 'semaforos_verde', 'semaforos_amarillo',
            'semaforos_rojo', 'semaforos_sin_dato', 'indicadores_evaluados',
            'indicadores_no_evaluados', 'fecha_calculo',
        ];

        foreach ($campos as $campo) {
            $this->assertStringContainsString($campo, $diccionario, "Campo '{$campo}' no documentado en diccionario");
        }

        // Verify 11 data rows (header + 11 fields)
        $lines = array_filter(explode("\n", $diccionario), fn ($l) => trim($l) !== '');
        $this->assertCount(12, $lines); // 1 header + 11 fields
    }

    public function test_403_sin_permiso_exportar_reportes(): void
    {
        $sinRol = User::factory()->withPersonalTeam()->create();

        $this->actingAs($sinRol);

        $this->get(route('evaluation.datos-abiertos.csv', ['ejercicio' => 2026]))
            ->assertStatus(403);

        $this->get(route('evaluation.datos-abiertos.json', ['ejercicio' => 2026]))
            ->assertStatus(403);

        $this->get(route('evaluation.datos-abiertos.diccionario'))
            ->assertStatus(403);

        $this->get(route('evaluation.datos-abiertos.zip', ['ejercicio' => 2026]))
            ->assertStatus(403);
    }

    public function test_controller_csv_descarga_correctamente(): void
    {
        $this->actingAs($this->planeador);

        $response = $this->get(route('evaluation.datos-abiertos.csv', ['ejercicio' => 2026]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->getContent());
    }

    public function test_controller_json_descarga_correctamente(): void
    {
        $this->actingAs($this->planeador);

        $response = $this->get(route('evaluation.datos-abiertos.json', ['ejercicio' => 2026]));

        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('data', $data);
    }
}
