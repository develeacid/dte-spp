<?php

namespace Tests\Feature\Mml;

use App\Livewire\Mml\ImportarPrograma;
use App\Models\Mml\ImportacionReporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ImportarProgramaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    public function test_component_renders_successfully(): void
    {
        Livewire::actingAs($this->user)
            ->test(ImportarPrograma::class)
            ->assertStatus(200)
            ->assertSee('Importar Programa');
    }

    public function test_upload_md_file_parses_and_shows_preview(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $file = UploadedFile::fake()->createWithContent('mir-test.md', $content);

        $component = Livewire::actingAs($this->user)
            ->test(ImportarPrograma::class)
            ->set('archivo', $file)
            ->assertSet('errorMensaje', null);

        $this->assertNotNull($component->get('preview'));
        $this->assertNotNull($component->get('diagnostico'));
        $this->assertNotNull($component->get('importacionId'));

        // Verify ImportacionReporte was persisted
        $this->assertDatabaseHas('importacion_reportes', [
            'archivo_original' => 'mir-test.md',
            'formato' => 'md',
            'estado' => 'pendiente',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_upload_csv_file_parses_successfully(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.csv'));
        $file = UploadedFile::fake()->createWithContent('mir-test.csv', $content);

        $component = Livewire::actingAs($this->user)
            ->test(ImportarPrograma::class)
            ->set('archivo', $file)
            ->assertSet('errorMensaje', null);

        $this->assertNotNull($component->get('preview'));

        $this->assertDatabaseHas('importacion_reportes', [
            'archivo_original' => 'mir-test.csv',
            'formato' => 'csv',
        ]);
    }

    public function test_upload_unsupported_extension_shows_error(): void
    {
        $file = UploadedFile::fake()->createWithContent('bad.txt', 'hello');

        Livewire::actingAs($this->user)
            ->test(ImportarPrograma::class)
            ->set('archivo', $file)
            ->assertSet('errorMensaje', 'Formato no soportado. Use archivos .md, .csv o .xlsx.');
    }
}
