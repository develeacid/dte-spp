<?php

namespace Tests\Feature\Evaluation\Exports;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Exports\Excel\AvanceTrimestralExcelExport;
use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IaffExcelFinancialSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (SystemRole::cases() as $rol) {
            Role::findOrCreate($rol->value, 'web');
        }
        foreach (SystemPermission::cases() as $p) {
            Permission::findOrCreate($p->value, 'web');
        }
        Role::findByName(SystemRole::ANALISTA_FINANCIERO->value)
            ->givePermissionTo([
                SystemPermission::EXPORTAR_REPORTES->value,
                SystemPermission::VER_DATOS_FINANCIEROS->value,
            ]);
        Role::findByName(SystemRole::OPERADOR->value)
            ->givePermissionTo([SystemPermission::EXPORTAR_REPORTES->value]);
    }

    public function test_includes_financial_sheet_for_user_with_permission(): void
    {
        $financiero = User::factory()->create();
        $financiero->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        $programa = ProgramaPresupuestario::factory()->create();
        $partida = PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '1101',
            'monto_aprobado' => 100000,
        ]);
        AvanceFinanciero::factory()->create([
            'partida_presupuestal_id' => $partida->id,
            'trimestre' => 1,
            'monto_pagado' => 20000,
        ]);

        Excel::store(
            new AvanceTrimestralExcelExport($programa, 2026, 1, $financiero),
            'test-iaff-financial.xlsx',
        );

        $spreadsheet = IOFactory::load(storage_path('app/private/test-iaff-financial.xlsx'));
        $sheetNames = $spreadsheet->getSheetNames();

        $this->assertContains('Avance Físico', $sheetNames);
        $this->assertContains('Financiero', $sheetNames);
    }

    public function test_omits_financial_sheet_for_user_without_permission(): void
    {
        $operador = User::factory()->create();
        $operador->assignRole(SystemRole::OPERADOR->value);

        $programa = ProgramaPresupuestario::factory()->create();

        Excel::store(
            new AvanceTrimestralExcelExport($programa, 2026, 1, $operador),
            'test-iaff-no-financial.xlsx',
        );

        $spreadsheet = IOFactory::load(storage_path('app/private/test-iaff-no-financial.xlsx'));
        $sheetNames = $spreadsheet->getSheetNames();

        $this->assertContains('Avance Físico', $sheetNames);
        $this->assertNotContains('Financiero', $sheetNames);
    }
}
