<?php

namespace Tests\Unit\Tracking;

use App\Models\ProgramaPresupuestario;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrphanFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function createAvanceWithDependencies(): Avance
    {
        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-'.uniqid(),
        ]);

        $mirNivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Test narrativo',
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $mirNivel->id,
            'nombre' => 'Indicador Test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
        ]);

        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100.0000,
            'ejercicio_fiscal' => 2026,
        ]);

        return Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'resultado' => 50.0000,
            'semaforo_calculado' => 'verde',
            'estado' => 'en_captura',
            'capturado_por' => $user->id,
        ]);
    }

    public function test_deleting_evidencia_removes_file_from_storage(): void
    {
        Storage::fake('local');

        $avance = $this->createAvanceWithDependencies();
        $user = User::first();
        $path = "evidencias/{$avance->id}/test-file.pdf";
        Storage::disk('local')->put($path, 'dummy content');

        $evidencia = AvanceEvidencia::create([
            'avance_id' => $avance->id,
            'nombre_archivo' => 'test-file.pdf',
            'ruta_archivo' => $path,
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1024,
            'hash_archivo' => hash('sha256', 'dummy content'),
            'nombre_documento' => 'Documento de prueba',
            'area_generadora' => 'Area Test',
            'fecha_documento' => '2026-01-01',
            'subido_por' => $user->id,
        ]);

        Storage::disk('local')->assertExists($path);

        $evidencia->delete();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_avance_removes_evidencias_and_directory(): void
    {
        Storage::fake('local');

        $avance = $this->createAvanceWithDependencies();
        $user = User::first();
        $dir = "evidencias/{$avance->id}";
        $path1 = "{$dir}/file1.pdf";
        $path2 = "{$dir}/file2.pdf";

        Storage::disk('local')->put($path1, 'content1');
        Storage::disk('local')->put($path2, 'content2');

        AvanceEvidencia::create([
            'avance_id' => $avance->id,
            'nombre_archivo' => 'file1.pdf',
            'ruta_archivo' => $path1,
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1024,
            'hash_archivo' => hash('sha256', 'content1'),
            'nombre_documento' => 'Documento 1',
            'area_generadora' => 'Area Test',
            'fecha_documento' => '2026-01-01',
            'subido_por' => $user->id,
        ]);

        AvanceEvidencia::create([
            'avance_id' => $avance->id,
            'nombre_archivo' => 'file2.pdf',
            'ruta_archivo' => $path2,
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 2048,
            'hash_archivo' => hash('sha256', 'content2'),
            'nombre_documento' => 'Documento 2',
            'area_generadora' => 'Area Test',
            'fecha_documento' => '2026-02-01',
            'subido_por' => $user->id,
        ]);

        $avance->delete();

        Storage::disk('local')->assertMissing($path1);
        Storage::disk('local')->assertMissing($path2);
        $this->assertEmpty(Storage::disk('local')->files($dir));
    }
}
