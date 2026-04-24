<?php

namespace Tests\Unit\Exports\Excel;

use App\Exports\Excel\Anexo11ExcelExport;
use App\Services\Evaluation\Anexo11ReportData;
use Tests\TestCase;

class Anexo11ExcelExportTest extends TestCase
{
    public function test_exports_four_sheets_with_expected_titles(): void
    {
        $data = new Anexo11ReportData(
            programaId: 1,
            programaNombre: 'Programa Prueba',
            totalBeneficiarios: 100,
            porGenero: ['masculino' => 40, 'femenino' => 58, 'otro' => '<5'],
            porGrupoEdad: ['infantes' => 10, 'ninios' => 30, 'adolescentes' => 20, 'jovenes' => 20, 'adultos' => 15, 'adultos_mayores' => 5],
            porPueblo: ['zapoteco' => 80, 'chinanteco' => '<5'],
            porTipoDiscapacidad: ['motriz' => 11, 'visual' => '<5', 'auditiva' => '<5', 'intelectual' => 0, 'psicosocial' => 0, 'multiple' => 0, 'ninguna' => 135],
            refreshedAt: '2026-04-24T12:00:00Z',
        );

        $book = new Anexo11ExcelExport($data);
        $sheets = $book->sheets();

        $this->assertCount(4, $sheets);
        $this->assertArrayHasKey('Sexo', $sheets);
        $this->assertArrayHasKey('Grupo de edad', $sheets);
        $this->assertArrayHasKey('Pueblo', $sheets);
        $this->assertArrayHasKey('Discapacidad', $sheets);
    }

    public function test_sexo_sheet_has_expected_row_data(): void
    {
        $data = new Anexo11ReportData(
            programaId: 1,
            programaNombre: 'Programa Prueba',
            totalBeneficiarios: 100,
            porGenero: ['masculino' => 40, 'femenino' => 58, 'otro' => '<5'],
            porGrupoEdad: [],
            porPueblo: [],
            porTipoDiscapacidad: [],
            refreshedAt: '2026-04-24T12:00:00Z',
        );

        $sheet = (new Anexo11ExcelExport($data))->sheets()['Sexo'];
        $rows = $sheet->array();

        $this->assertContains(['Masculino', 40], $rows);
        $this->assertContains(['Femenino', 58], $rows);
        $this->assertContains(['Otro', '<5'], $rows);
    }
}
