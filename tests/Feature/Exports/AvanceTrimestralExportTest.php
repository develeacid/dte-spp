<?php

namespace Tests\Feature\Exports;

use App\Exports\Excel\AvanceTrimestralExcelExport;
use App\Exports\Excel\Sheets\EvidenciaPadronSheet;
use App\Exports\Pdf\AvanceTrimestralPdfExport;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AvanceTrimestralExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Stub la cola para que el observer de padron no intente conectar a geobase.
        Bus::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_avance_trimestral_pdf_contains_vobo(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $team = $programa->team;

        $html = view('exports.pdf.partials.vobo', [
            'titular' => $team->titular,
            'dependencia' => $team->name,
            'fecha' => now()->format('d/m/Y'),
        ])->render();

        $this->assertStringContainsString('Vo. Bo.', $html);
        $this->assertStringContainsString($team->titular, $html);
        $this->assertStringContainsString('Firma', $html);
    }

    public function test_avance_trimestral_pdf_generates_with_vobo(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $export = new AvanceTrimestralPdfExport($programa, 2025, 1);
        $pdfContent = $export->generate();

        $this->assertNotEmpty($pdfContent);
    }

    public function test_avance_trimestral_pdf_endpoint_requires_permission(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $user = User::factory()->create();
        $team = Team::where('clave_ur', 'SE-001')->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $response = $this->actingAs($user)
            ->get(route('evaluation.exportar.pdf', ['tipo' => 'avance-trimestral', 'id' => $programa->id, 'trimestre' => 1]));

        $response->assertStatus(403);
    }

    public function test_avance_trimestral_pdf_downloads_for_planeador(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('evaluation.exportar.pdf', ['tipo' => 'avance-trimestral', 'id' => $programa->id, 'trimestre' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_excel_includes_padron_sheet_when_program_is_linked_to_geobase(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $programa->update(['padron_geobase_activo' => true]);

        $export = new AvanceTrimestralExcelExport($programa, 2025, 1);
        $sheets = $export->sheets();

        $this->assertArrayHasKey('Evidencia de Padrón', $sheets);
        $this->assertInstanceOf(EvidenciaPadronSheet::class, $sheets['Evidencia de Padrón']);
    }

    public function test_excel_omits_padron_sheet_when_program_has_no_geobase_link(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $programa->update(['padron_geobase_activo' => false]);

        $export = new AvanceTrimestralExcelExport($programa, 2025, 1);
        $sheets = $export->sheets();

        $this->assertArrayNotHasKey('Evidencia de Padrón', $sheets);
    }

    public function test_pdf_includes_padron_evidence_section_when_geobase_linked(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $programa->update(['padron_geobase_activo' => true]);

        $componente = $programa->mirNiveles()->where('tipo_nivel', 'componente')->orderBy('orden')->firstOrFail();
        $indicador = $componente->indicadores()->firstOrFail();
        $avance = Avance::query()
            ->where('indicador_id', $indicador->id)
            ->whereHas('metaPeriodo', fn ($q) => $q->where('ejercicio_fiscal', 2025)->where('periodo', 1))
            ->firstOrFail();

        AvanceEvidencia::create([
            'avance_id' => $avance->id,
            'nombre_archivo' => 'snapshot-88-2025-Q1.csv',
            'ruta_archivo' => '',
            'mime_type' => 'text/csv',
            'tamano_bytes' => 0,
            'hash_archivo' => 'cafefade'.str_repeat('0', 56),
            'geobase_snapshot_id' => 88,
            'nombre_documento' => 'Snapshot Padrón',
            'area_generadora' => 'GeoBase (manual)',
            'fecha_documento' => '2025-03-31',
            'subido_por' => $avance->capturado_por ?? User::factory()->create()->id,
        ]);

        $html = (new AvanceTrimestralPdfExport($programa, 2025, 1))->generateHtml();

        $this->assertStringContainsString('Evidencia de Padrón', $html);
        $this->assertStringContainsString('88', $html);
        $this->assertStringContainsString('cafefade', $html);
    }

    public function test_pdf_omits_padron_section_when_no_geobase_link(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $programa->update(['padron_geobase_activo' => false]);

        $html = (new AvanceTrimestralPdfExport($programa, 2025, 1))->generateHtml();

        $this->assertStringNotContainsString('Evidencia de Padrón', $html);
    }

    public function test_padron_sheet_lists_components_with_their_snapshot_evidence(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $programa->update(['padron_geobase_activo' => true]);

        // Attach a snapshot-style evidence to one of the program's avances.
        $componente = $programa->mirNiveles()->where('tipo_nivel', 'componente')->orderBy('orden')->firstOrFail();
        $indicador = $componente->indicadores()->firstOrFail();
        $avance = Avance::query()
            ->where('indicador_id', $indicador->id)
            ->whereHas('metaPeriodo', fn ($q) => $q->where('ejercicio_fiscal', 2025)->where('periodo', 1))
            ->firstOrFail();

        AvanceEvidencia::create([
            'avance_id' => $avance->id,
            'nombre_archivo' => 'snapshot-77-2025-Q1.csv',
            'ruta_archivo' => '',
            'mime_type' => 'text/csv',
            'tamano_bytes' => 0,
            'hash_archivo' => 'deadbeef'.str_repeat('0', 56),
            'geobase_snapshot_id' => 77,
            'nombre_documento' => 'Snapshot Padrón',
            'area_generadora' => 'GeoBase (manual)',
            'fecha_documento' => '2025-03-31',
            'subido_por' => $avance->capturado_por ?? User::factory()->create()->id,
        ]);

        $sheet = new EvidenciaPadronSheet($programa, 2025, 1);
        $rows = $sheet->collection();

        $matched = $rows->firstWhere('snapshot_id', 77);
        $this->assertNotNull($matched, 'Expected the seeded snapshot to appear in the sheet.');
        $this->assertSame('GeoBase (manual)', $matched['origen']);
        $this->assertSame('2025-03-31', $matched['fecha']);
        $this->assertStringStartsWith('deadbeef', $matched['hash']);
    }
}
