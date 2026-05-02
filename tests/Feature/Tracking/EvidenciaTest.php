<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\EvidenciaAvance;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EvidenciaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Avance $avance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'meta' => 100,
            'activo_seguimiento' => true, 'orden' => 1,
        ]);

        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1, 'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026, 'activo' => true,
        ]);

        $this->avance = Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);
    }

    public function test_upload_crea_evidencia_con_hash(): void
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        Livewire::test(EvidenciaAvance::class, ['avance' => $this->avance])
            ->set('archivo', $file)
            ->set('nombre_documento', 'Reporte trimestral')
            ->set('area_generadora', 'Direccion General')
            ->set('fecha_documento', '2026-03-01')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('avance_evidencias', [
            'avance_id' => $this->avance->id,
            'nombre_documento' => 'Reporte trimestral',
            'area_generadora' => 'Direccion General',
        ]);

        $evidencia = AvanceEvidencia::first();
        $this->assertNotNull($evidencia->hash_archivo);
        $this->assertEquals(64, strlen($evidencia->hash_archivo));
    }

    public function test_download_con_permiso(): void
    {
        Storage::fake('local');

        $revisor = User::factory()->withPersonalTeam()->create();
        $revisor->givePermissionTo('revisar_avance');

        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $path = $file->store("evidencias/{$this->avance->id}", 'local');

        $evidencia = AvanceEvidencia::create([
            'avance_id' => $this->avance->id,
            'nombre_archivo' => 'test.pdf',
            'ruta_archivo' => $path,
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1024,
            'hash_archivo' => str_repeat('a', 64),
            'nombre_documento' => 'Reporte',
            'subido_por' => $this->user->id,
        ]);

        $response = $this->actingAs($revisor)
            ->get(route('tracking.evidencia.download', $evidencia));

        $response->assertOk();
    }

    public function test_download_sin_permiso_denegado(): void
    {
        Storage::fake('local');

        $otro = User::factory()->withPersonalTeam()->create();

        $evidencia = AvanceEvidencia::create([
            'avance_id' => $this->avance->id,
            'nombre_archivo' => 'test.pdf',
            'ruta_archivo' => 'evidencias/1/test.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1024,
            'hash_archivo' => str_repeat('a', 64),
            'nombre_documento' => 'Reporte',
            'subido_por' => $this->user->id,
        ]);

        $response = $this->actingAs($otro)
            ->get(route('tracking.evidencia.download', $evidencia));

        $response->assertForbidden();
    }

    public function test_capturador_puede_descargar(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $path = $file->store("evidencias/{$this->avance->id}", 'local');

        $evidencia = AvanceEvidencia::create([
            'avance_id' => $this->avance->id,
            'nombre_archivo' => 'test.pdf',
            'ruta_archivo' => $path,
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1024,
            'hash_archivo' => str_repeat('a', 64),
            'nombre_documento' => 'Reporte',
            'subido_por' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tracking.evidencia.download', $evidencia));

        $response->assertOk();
    }

    public function test_eliminar_solo_si_no_congelado(): void
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $path = $file->store("evidencias/{$this->avance->id}", 'local');

        $evidencia = AvanceEvidencia::create([
            'avance_id' => $this->avance->id,
            'nombre_archivo' => 'test.pdf',
            'ruta_archivo' => $path,
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1024,
            'hash_archivo' => str_repeat('a', 64),
            'nombre_documento' => 'Reporte',
            'subido_por' => $this->user->id,
        ]);

        // Congelar el avance
        $this->avance->update(['congelado_at' => now()]);
        $this->avance->refresh();

        Livewire::test(EvidenciaAvance::class, ['avance' => $this->avance])
            ->call('eliminar', $evidencia->id)
            ->assertStatus(403);
    }

    public function test_archivo_en_disco_privado(): void
    {
        Storage::fake('local');

        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        Livewire::test(EvidenciaAvance::class, ['avance' => $this->avance])
            ->set('archivo', $file)
            ->set('nombre_documento', 'Reporte')
            ->call('guardar')
            ->assertHasNoErrors();

        $evidencia = AvanceEvidencia::first();
        Storage::disk('local')->assertExists($evidencia->ruta_archivo);
        $this->assertStringStartsWith("evidencias/{$this->avance->id}/", $evidencia->ruta_archivo);
    }
}
