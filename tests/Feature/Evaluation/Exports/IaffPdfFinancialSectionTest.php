<?php

namespace Tests\Feature\Evaluation\Exports;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Exports\Pdf\AvanceTrimestralPdfExport;
use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IaffPdfFinancialSectionTest extends TestCase
{
    use RefreshDatabase;

    private ProgramaPresupuestario $programa;

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

        $this->programa = ProgramaPresupuestario::factory()->create();
        $partida = PartidaPresupuestal::factory()->create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026,
            'clave_partida' => '1101',
            'descripcion' => 'Sueldos base',
            'monto_aprobado' => 100000,
        ]);
        AvanceFinanciero::factory()->create([
            'partida_presupuestal_id' => $partida->id,
            'trimestre' => 1,
            'monto_pagado' => 25000,
        ]);
    }

    public function test_includes_financial_section_for_user_with_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        $html = (new AvanceTrimestralPdfExport($this->programa, 2026, 1, $user))->generateHtml();

        $this->assertStringContainsString('Componente Financiero', $html);
        $this->assertStringContainsString('1101', $html);
        $this->assertStringContainsString('Sueldos base', $html);
    }

    public function test_omits_financial_section_for_user_without_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::OPERADOR->value);

        $html = (new AvanceTrimestralPdfExport($this->programa, 2026, 1, $user))->generateHtml();

        $this->assertStringNotContainsString('Componente Financiero', $html);
    }

    public function test_includes_totals_row_in_financial_section(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        $html = (new AvanceTrimestralPdfExport($this->programa, 2026, 1, $user))->generateHtml();

        // TOTAL row marker
        $this->assertMatchesRegularExpression('/TOTAL.*100,?000/s', $html);
    }
}
