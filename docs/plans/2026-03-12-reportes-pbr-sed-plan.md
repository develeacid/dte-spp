# Reportes PbR-SED Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implementar 5 reportes clasificados por nivel de riesgo legal (Estratégico, Táctico, Transparencia, Operativo) + documento arquitectónico de integración futura para presupuesto y padrón.

**Architecture:** Extender la infraestructura existente de exports (DomPDF + Laravel Excel) y Livewire. Los reportes R1-R3 son PDF/Excel vía `ExportController`. R4-R5 son vistas Livewire con exportación. Todos comparten un partial Blade reutilizable para Vo.Bo.

**Tech Stack:** Laravel 12, Livewire 3, DomPDF (barryvdh/laravel-dompdf), Laravel Excel (maatwebsite/excel), Spatie Permission, PHPUnit

---

## Task 1: Vo.Bo. Partial + Avance Trimestral Mejorado (R1)

Agregar bloque Vo.Bo. reutilizable y aplicarlo al PDF de Avance Trimestral existente.

**Files:**
- Create: `resources/views/exports/pdf/partials/vobo.blade.php`
- Modify: `resources/views/exports/pdf/avance-trimestral.blade.php`
- Modify: `app/Exports/Pdf/AvanceTrimestralPdfExport.php`
- Test: `tests/Feature/Exports/AvanceTrimestralExportTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Exports/AvanceTrimestralExportTest.php`:

```php
<?php

namespace Tests\Feature\Exports;

use App\Enums\SystemRole;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvanceTrimestralExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_avance_trimestral_pdf_contains_vobo(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $export = new \App\Exports\Pdf\AvanceTrimestralPdfExport($programa, 2025, 1);
        $pdfContent = $export->generate();

        $this->assertNotEmpty($pdfContent);
        // DomPDF output is binary, so we test by rendering the view directly
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

    public function test_avance_trimestral_pdf_endpoint_requires_permission(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        // User without permission
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
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=AvanceTrimestralExportTest`
Expected: FAIL — vobo partial does not exist yet.

**Step 3: Create the Vo.Bo. partial**

Create `resources/views/exports/pdf/partials/vobo.blade.php`:

```html
<div class="vobo" style="margin-top: 60px; page-break-inside: avoid;">
    <table style="width: 100%; border: none;">
        <tr>
            <td style="width: 50%; border: none; text-align: center; vertical-align: bottom; padding-top: 40px;">
                <div style="border-top: 1px solid #333; width: 250px; margin: 0 auto; padding-top: 4px;">
                    <strong>{{ $titular }}</strong><br>
                    <span style="font-size: 9px;">Titular de {{ $dependencia }}</span>
                </div>
            </td>
            <td style="width: 50%; border: none; text-align: center; vertical-align: bottom; padding-top: 40px;">
                <div>
                    <p style="font-size: 9px; color: #666;">Fecha: {{ $fecha }}</p>
                </div>
            </td>
        </tr>
    </table>
    <div style="text-align: center; margin-top: 10px; font-size: 10px; font-weight: bold;">
        Vo. Bo.
    </div>
    <div style="text-align: center; font-size: 8px; color: #999; margin-top: 4px;">
        Firma autógrafa en el documento original
    </div>
</div>
```

**Step 4: Modify AvanceTrimestralPdfExport to pass team data**

In `app/Exports/Pdf/AvanceTrimestralPdfExport.php`, update the `generate()` method to load the team and pass Vo.Bo. data:

```php
public function generate(): string
{
    $niveles = $this->programa->mirNiveles()
        ->with([
            'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
            'indicadores.metasPeriodo' => fn ($q) => $q->where('ejercicio_fiscal', $this->ejercicioFiscal)
                ->where('periodo', $this->trimestre),
            'indicadores.avances' => fn ($q) => $q->whereHas('metaPeriodo', fn ($mp) => $mp->where('ejercicio_fiscal', $this->ejercicioFiscal)
                ->where('periodo', $this->trimestre)),
        ])
        ->orderByRaw("CASE tipo_nivel WHEN 'fin' THEN 1 WHEN 'proposito' THEN 2 WHEN 'componente' THEN 3 WHEN 'actividad' THEN 4 END")
        ->orderBy('orden')
        ->get();

    $encabezado = config('evaluation.exports.encabezado');
    $team = $this->programa->team;

    $pdf = Pdf::loadView('exports.pdf.avance-trimestral', [
        'programa' => $this->programa,
        'niveles' => $niveles,
        'ejercicioFiscal' => $this->ejercicioFiscal,
        'trimestre' => $this->trimestre,
        'encabezado' => $encabezado,
        'generadoEn' => now()->format('d/m/Y H:i'),
        'titular' => $team->titular,
        'dependencia' => $team->name,
        'fecha' => now()->format('d/m/Y'),
    ]);

    $pdf->setPaper('letter', 'landscape');

    return $pdf->output();
}
```

**Step 5: Add Vo.Bo. include to the Blade template**

At the end of `resources/views/exports/pdf/avance-trimestral.blade.php`, before `</body>`, add:

```html
    @include('exports.pdf.partials.vobo', [
        'titular' => $titular,
        'dependencia' => $dependencia,
        'fecha' => $fecha,
    ])
```

**Step 6: Run tests to verify they pass**

Run: `./vendor/bin/sail artisan test --filter=AvanceTrimestralExportTest`
Expected: All 3 tests PASS.

**Step 7: Commit**

```bash
git add resources/views/exports/pdf/partials/vobo.blade.php \
      resources/views/exports/pdf/avance-trimestral.blade.php \
      app/Exports/Pdf/AvanceTrimestralPdfExport.php \
      tests/Feature/Exports/AvanceTrimestralExportTest.php
git commit -m "feat(reports): add Vo.Bo. partial and apply to Avance Trimestral PDF

Resolves R1 of PbR-SED reports plan. Creates reusable Vo.Bo. block
with titular name, signature line, and date. Applied to the existing
Avance Trimestral PDF export."
```

---

## Task 2: FMyE — Ficha de Monitoreo y Evaluación (R2)

Nuevo reporte PDF de 1-2 páginas por programa: datos generales, alineación PED→PND→ODS, resumen MIR con semáforos, índice de eficacia, semáforo histórico, y Vo.Bo.

**Files:**
- Create: `app/Exports/Pdf/FmyePdfExport.php`
- Create: `resources/views/exports/pdf/fmye.blade.php`
- Modify: `app/Http/Controllers/Evaluation/ExportController.php`
- Test: `tests/Feature/Exports/FmyeExportTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Exports/FmyeExportTest.php`:

```php
<?php

namespace Tests\Feature\Exports;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\Cascade\PedSeeder;
use Database\Seeders\Mml\PndSeeder;
use Database\Seeders\Mml\OdsSeeder;
use Database\Seeders\Cascade\ProgramasDerivadosSeeder;
use Database\Seeders\Cascade\AlineacionesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FmyeExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(OdsSeeder::class);
        $this->seed(PndSeeder::class);
        $this->seed(PedSeeder::class);
        $this->seed(ProgramasDerivadosSeeder::class);
        $this->seed(AlineacionesSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_fmye_pdf_generates_successfully(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $export = new \App\Exports\Pdf\FmyePdfExport($programa, 2025);
        $pdfContent = $export->generate();

        $this->assertNotEmpty($pdfContent);
    }

    public function test_fmye_pdf_contains_all_sections(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $html = view('exports.pdf.fmye', [
            'programa' => $programa,
            'team' => $programa->team,
            'ejercicioFiscal' => 2025,
            'encabezado' => config('evaluation.exports.encabezado'),
            'generadoEn' => now()->format('d/m/Y H:i'),
            'niveles' => $programa->mirNiveles()->orderBy('tipo_nivel')->orderBy('orden')->get(),
            'alineacion' => [],
            'evaluacion' => null,
            'semaforoHistorico' => [],
            'titular' => $programa->team->titular,
            'dependencia' => $programa->team->name,
            'fecha' => now()->format('d/m/Y'),
        ])->render();

        $this->assertStringContainsString('Ficha de Monitoreo y Evaluación', $html);
        $this->assertStringContainsString($programa->clave, $html);
        $this->assertStringContainsString('Vo. Bo.', $html);
        $this->assertStringContainsString('Alineación Estratégica', $html);
        $this->assertStringContainsString('Índice de Eficacia', $html);
    }

    public function test_fmye_endpoint_works_for_planeador(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('evaluation.exportar.pdf', ['tipo' => 'fmye', 'id' => $programa->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_fmye_endpoint_forbidden_without_permission(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $user = User::factory()->create();
        $user->forceFill(['current_team_id' => $programa->team_id])->save();

        $response = $this->actingAs($user)
            ->get(route('evaluation.exportar.pdf', ['tipo' => 'fmye', 'id' => $programa->id]));

        $response->assertStatus(403);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=FmyeExportTest`
Expected: FAIL — FmyePdfExport class does not exist.

**Step 3: Create FmyePdfExport class**

Create `app/Exports/Pdf/FmyePdfExport.php`:

```php
<?php

namespace App\Exports\Pdf;

use App\Enums\EstadoAvance;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\ProgramaPresupuestario;
use Barryvdh\DomPDF\Facade\Pdf;

class FmyePdfExport
{
    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
    ) {}

    public function generate(): string
    {
        $team = $this->programa->team;

        $niveles = $this->programa->mirNiveles()
            ->with([
                'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
                'indicadores.avances' => fn ($q) => $q->whereHas('metaPeriodo', fn ($mp) => $mp->where('ejercicio_fiscal', $this->ejercicioFiscal))
                    ->where('estado', EstadoAvance::APROBADO),
            ])
            ->orderByRaw("CASE tipo_nivel WHEN 'fin' THEN 1 WHEN 'proposito' THEN 2 WHEN 'componente' THEN 3 WHEN 'actividad' THEN 4 END")
            ->orderBy('orden')
            ->get();

        $alineacion = $this->obtenerAlineacion();

        $evaluacion = EvaluacionPrograma::where('programa_presupuestario_id', $this->programa->id)
            ->where('ejercicio_fiscal', $this->ejercicioFiscal)
            ->first();

        $semaforoHistorico = $this->obtenerSemaforoHistorico();

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.fmye', [
            'programa' => $this->programa,
            'team' => $team,
            'ejercicioFiscal' => $this->ejercicioFiscal,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
            'niveles' => $niveles,
            'alineacion' => $alineacion,
            'evaluacion' => $evaluacion,
            'semaforoHistorico' => $semaforoHistorico,
            'titular' => $team->titular,
            'dependencia' => $team->name,
            'fecha' => now()->format('d/m/Y'),
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->output();
    }

    /**
     * Obtiene la cascada de alineación: PED Objetivo → PND Objetivo → ODS Meta.
     * Busca a través de los programas derivados asociados al team.
     */
    private function obtenerAlineacion(): array
    {
        $alineacion = [];

        $team = $this->programa->team;

        // Get PED objectives linked to PND objectives linked to ODS metas
        // through the programa derivado → lineas de accion → estrategia → objetivo chain
        $pedObjetivos = \App\Models\PedObjetivoEstrategico::whereHas('estrategias.lineasAccion.programasDerivadosObjetivos')
            ->with([
                'pndObjetivos.odsMetas',
                'tema.eje',
            ])
            ->get();

        foreach ($pedObjetivos as $pedObj) {
            $entry = [
                'ped' => "Eje {$pedObj->tema->eje->clave}: {$pedObj->tema->eje->nombre} → Obj. {$pedObj->clave}: {$pedObj->nombre}",
                'pnd' => [],
                'ods' => [],
            ];

            foreach ($pedObj->pndObjetivos as $pndObj) {
                $entry['pnd'][] = "Obj. {$pndObj->clave}: {$pndObj->descripcion}";
                foreach ($pndObj->odsMetas as $odsMeta) {
                    $entry['ods'][] = "Meta {$odsMeta->clave}: {$odsMeta->descripcion}";
                }
            }

            $alineacion[] = $entry;
        }

        return $alineacion;
    }

    /**
     * Semáforo histórico: para cada trimestre del ejercicio, cuenta verde/amarillo/rojo
     * de los indicadores del programa.
     */
    private function obtenerSemaforoHistorico(): array
    {
        $historico = [];

        for ($t = 1; $t <= 4; $t++) {
            $avances = \App\Models\Tracking\Avance::whereHas('indicador.mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->programa->id))
                ->whereHas('metaPeriodo', fn ($q) => $q->where('ejercicio_fiscal', $this->ejercicioFiscal)->where('periodo', $t))
                ->where('estado', EstadoAvance::APROBADO)
                ->get();

            if ($avances->isEmpty()) {
                continue;
            }

            $historico["T{$t}"] = [
                'verde' => $avances->where('semaforo_calculado', 'verde')->count(),
                'amarillo' => $avances->where('semaforo_calculado', 'amarillo')->count(),
                'rojo' => $avances->where('semaforo_calculado', 'rojo')->count(),
            ];
        }

        return $historico;
    }
}
```

**Step 4: Create FMyE Blade template**

Create `resources/views/exports/pdf/fmye.blade.php`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FMyE - {{ $programa->clave }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        .section-title { font-size: 11px; font-weight: bold; background-color: #2d3748; color: white; padding: 4px 8px; margin: 12px 0 6px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #333; padding: 3px 5px; text-align: left; vertical-align: top; font-size: 9px; }
        th { background-color: #edf2f7; font-size: 8px; }
        .datos-generales td { border: none; padding: 2px 8px; }
        .datos-generales .label { font-weight: bold; width: 30%; color: #4a5568; }
        .semaforo-verde { background-color: #c6f6d5; }
        .semaforo-amarillo { background-color: #fefcbf; }
        .semaforo-rojo { background-color: #fed7d7; }
        .eficacia-box { text-align: center; font-size: 24px; font-weight: bold; padding: 10px; margin: 8px 0; border: 2px solid #2d3748; }
        .nivel-fin { background-color: #ebf4ff; }
        .nivel-proposito { background-color: #e6fffa; }
        .nivel-componente { background-color: #fffbeb; }
        .nivel-actividad { background-color: #f5f3ff; }
    </style>
</head>
<body>
    {{-- ENCABEZADO --}}
    <div class="header">
        <h2>{{ $encabezado['institucion'] }}</h2>
        <h3>{{ $encabezado['dependencia'] }}</h3>
        <h3>Ficha de Monitoreo y Evaluación (FMyE)</h3>
        <p><strong>{{ $programa->clave }}</strong> — {{ $programa->nombre }}</p>
        <p>Ejercicio Fiscal: {{ $ejercicioFiscal }}</p>
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    {{-- SECCIÓN 1: DATOS GENERALES --}}
    <div class="section-title">1. Datos Generales</div>
    <table class="datos-generales">
        <tr><td class="label">Clave del Programa:</td><td>{{ $programa->clave }}</td></tr>
        <tr><td class="label">Nombre del Programa:</td><td>{{ $programa->nombre }}</td></tr>
        <tr><td class="label">Unidad Responsable:</td><td>{{ $team->name }} ({{ $team->clave_ur }})</td></tr>
        <tr><td class="label">Titular:</td><td>{{ $team->titular }}</td></tr>
        <tr><td class="label">Ejercicio Fiscal:</td><td>{{ $ejercicioFiscal }}</td></tr>
    </table>

    {{-- SECCIÓN 2: ALINEACIÓN ESTRATÉGICA --}}
    <div class="section-title">2. Alineación Estratégica</div>
    @if(count($alineacion) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">PED (Objetivo Estratégico)</th>
                    <th style="width: 30%;">PND (Objetivo)</th>
                    <th style="width: 30%;">ODS (Meta)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alineacion as $row)
                    <tr>
                        <td>{{ $row['ped'] }}</td>
                        <td>@foreach($row['pnd'] as $pnd){{ $pnd }}@if(!$loop->last)<br>@endif @endforeach</td>
                        <td>@foreach($row['ods'] as $ods){{ $ods }}@if(!$loop->last)<br>@endif @endforeach</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color: #999; font-style: italic;">Sin alineaciones registradas.</p>
    @endif

    {{-- SECCIÓN 3: RESUMEN MIR --}}
    <div class="section-title">3. Resumen MIR</div>
    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Nivel</th>
                <th style="width: 45%;">Resumen Narrativo</th>
                <th style="width: 25%;">Indicador</th>
                <th style="width: 18%;">Semáforo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($niveles as $nivel)
                @php $rowClass = 'nivel-' . $nivel->tipo_nivel->value; @endphp
                <tr class="{{ $rowClass }}">
                    <td><strong>{{ $nivel->tipo_nivel->label() }}</strong></td>
                    <td>{{ $nivel->resumen_narrativo }}</td>
                    <td>
                        @foreach($nivel->indicadores as $ind)
                            <div>{{ $ind->nombre }}</div>
                            @if(!$loop->last)<hr style="margin: 2px 0;">@endif
                        @endforeach
                    </td>
                    <td>
                        @foreach($nivel->indicadores as $ind)
                            @php $ultimoAvance = $ind->avances->last(); @endphp
                            <div class="semaforo-{{ $ultimoAvance?->semaforo_calculado ?? 'gris' }}">
                                {{ ucfirst($ultimoAvance?->semaforo_calculado ?? 'Sin dato') }}
                            </div>
                            @if(!$loop->last)<hr style="margin: 2px 0;">@endif
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SECCIÓN 4: ÍNDICE DE EFICACIA --}}
    <div class="section-title">4. Índice de Eficacia</div>
    @if($evaluacion)
        <div class="eficacia-box">
            {{ number_format($evaluacion->indice_eficacia, 2) }}%
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nivel</th>
                    <th>Peso</th>
                    <th>Promedio Eficacia</th>
                    <th>Evaluados</th>
                    <th>No Evaluados</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evaluacion->desglose_niveles as $nivel => $data)
                    <tr>
                        <td>{{ ucfirst($nivel) }}</td>
                        <td>{{ ($data['peso'] ?? 0) * 100 }}%</td>
                        <td>{{ $data['promedio'] !== null ? number_format($data['promedio'], 2) . '%' : 'N/A' }}</td>
                        <td>{{ $data['indicadores_evaluados'] ?? 0 }}</td>
                        <td>{{ $data['indicadores_no_evaluados'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table>
            <tr>
                <td class="semaforo-verde" style="text-align: center;">Verde: {{ $evaluacion->conteo_semaforos['verde'] ?? 0 }}</td>
                <td class="semaforo-amarillo" style="text-align: center;">Amarillo: {{ $evaluacion->conteo_semaforos['amarillo'] ?? 0 }}</td>
                <td class="semaforo-rojo" style="text-align: center;">Rojo: {{ $evaluacion->conteo_semaforos['rojo'] ?? 0 }}</td>
                <td style="text-align: center; background: #edf2f7;">Sin dato: {{ $evaluacion->conteo_semaforos['sin_dato'] ?? 0 }}</td>
            </tr>
        </table>
    @else
        <p style="color: #999; font-style: italic;">Evaluación no calculada para este ejercicio.</p>
    @endif

    {{-- SECCIÓN 5: SEMÁFORO HISTÓRICO --}}
    <div class="section-title">5. Semáforo Histórico por Trimestre</div>
    @if(count($semaforoHistorico) > 0)
        <table>
            <thead>
                <tr>
                    <th>Trimestre</th>
                    <th>Verde</th>
                    <th>Amarillo</th>
                    <th>Rojo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($semaforoHistorico as $trimestre => $conteo)
                    <tr>
                        <td><strong>{{ $trimestre }}</strong></td>
                        <td class="semaforo-verde" style="text-align: center;">{{ $conteo['verde'] }}</td>
                        <td class="semaforo-amarillo" style="text-align: center;">{{ $conteo['amarillo'] }}</td>
                        <td class="semaforo-rojo" style="text-align: center;">{{ $conteo['rojo'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color: #999; font-style: italic;">Sin datos históricos de semáforo.</p>
    @endif

    {{-- SECCIÓN 6: Vo.Bo. --}}
    @include('exports.pdf.partials.vobo', [
        'titular' => $titular,
        'dependencia' => $dependencia,
        'fecha' => $fecha,
    ])
</body>
</html>
```

**Step 5: Register FMyE in ExportController**

In `app/Http/Controllers/Evaluation/ExportController.php`:

1. Add import at the top:
```php
use App\Exports\Pdf\FmyePdfExport;
```

2. In the `pdf()` method's match statement, add before `default`:
```php
'fmye' => $this->fmyePdf($request, $id),
```

3. Add the private method:
```php
private function fmyePdf(Request $request, ?int $id): string
{
    $programa = ProgramaPresupuestario::findOrFail($id);

    return (new FmyePdfExport(
        $programa,
        (int) $request->input('ejercicio_fiscal', date('Y')),
    ))->generate();
}
```

**Step 6: Run tests to verify they pass**

Run: `./vendor/bin/sail artisan test --filter=FmyeExportTest`
Expected: All 4 tests PASS.

**Step 7: Commit**

```bash
git add app/Exports/Pdf/FmyePdfExport.php \
      resources/views/exports/pdf/fmye.blade.php \
      app/Http/Controllers/Evaluation/ExportController.php \
      tests/Feature/Exports/FmyeExportTest.php
git commit -m "feat(reports): add FMyE (Ficha de Monitoreo y Evaluación) PDF export

New R2 tactical report: 1-2 page summary per program with datos generales,
alineación PED→PND→ODS, resumen MIR with semáforos, índice de eficacia,
semáforo histórico, and Vo.Bo. block."
```

---

## Task 3: MIR Aprobada — Formato Ciudadano (R3)

Mejorar el PDF de MIR existente con encabezado formal, fecha de aprobación, y pie de página de transparencia. Crear ruta pública sin permiso `exportar_reportes`.

**Files:**
- Modify: `resources/views/exports/pdf/mir.blade.php`
- Modify: `app/Exports/Pdf/MirPdfExport.php`
- Modify: `routes/web/evaluation.php`
- Test: `tests/Feature/Exports/MirAprobadaExportTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Exports/MirAprobadaExportTest.php`:

```php
<?php

namespace Tests\Feature\Exports;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MirAprobadaExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_mir_pdf_contains_transparency_footer(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $export = new \App\Exports\Pdf\MirPdfExport($programa, 2025);

        // Test the view renders with transparency text
        $html = view('exports.pdf.mir', [
            'programa' => $programa,
            'niveles' => $programa->mirNiveles()->with(['indicadores.mediosVerificacion', 'indicadores.variables'])->get(),
            'ejercicioFiscal' => 2025,
            'encabezado' => config('evaluation.exports.encabezado'),
            'generadoEn' => now()->format('d/m/Y H:i'),
        ])->render();

        $this->assertStringContainsString('Art. 70 LGTAIP', $html);
    }

    public function test_mir_publica_route_accessible_by_any_authenticated_user(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        // User with NO special permissions (just authenticated)
        $user = User::factory()->create();
        $user->forceFill(['current_team_id' => $programa->team_id])->save();

        $response = $this->actingAs($user)
            ->get(route('evaluation.mir-publica', ['id' => $programa->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_mir_publica_route_requires_authentication(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $response = $this->get(route('evaluation.mir-publica', ['id' => $programa->id]));

        $response->assertRedirect(); // Redirects to login
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=MirAprobadaExportTest`
Expected: FAIL — route `evaluation.mir-publica` does not exist.

**Step 3: Add transparency footer to MIR Blade**

In `resources/views/exports/pdf/mir.blade.php`, before `</body>`, add:

```html
    <div style="margin-top: 20px; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #ccc; padding-top: 6px;">
        @if($programa->planeacion_completada_at)
            Fecha de aprobación: {{ $programa->planeacion_completada_at->format('d/m/Y') }}<br>
        @endif
        Documento público conforme al Art. 70 LGTAIP — Información disponible para cualquier persona.
    </div>
```

**Step 4: Add public route for MIR**

In `routes/web/evaluation.php`, inside the main group (but OUTSIDE the `can:exportar_reportes` middleware), add:

```php
Route::get('/mir-publica/{id}', function (\Illuminate\Http\Request $request, int $id) {
    $programa = \App\Models\ProgramaPresupuestario::findOrFail($id);
    $contenido = (new \App\Exports\Pdf\MirPdfExport(
        $programa,
        (int) $request->input('ejercicio_fiscal', date('Y')),
    ))->generate();

    $filename = "mir-{$programa->clave}-" . now()->format('Ymd') . '.pdf';

    return new \Illuminate\Http\Response($contenido, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"{$filename}\"",
    ]);
})->name('evaluation.mir-publica');
```

Note: `inline` instead of `attachment` so it opens in browser (transparency/citizen use case).

**Step 5: Run tests to verify they pass**

Run: `./vendor/bin/sail artisan test --filter=MirAprobadaExportTest`
Expected: All 3 tests PASS.

**Step 6: Commit**

```bash
git add resources/views/exports/pdf/mir.blade.php \
      routes/web/evaluation.php \
      tests/Feature/Exports/MirAprobadaExportTest.php
git commit -m "feat(reports): add MIR Aprobada public route with transparency footer

R3 transparency report: adds Art. 70 LGTAIP footer and planeacion_completada_at
date to MIR PDF. New route /evaluacion/mir-publica/{id} accessible by any
authenticated user without exportar_reportes permission."
```

---

## Task 4: Sábana de Captura — Livewire + Permisos (R4)

Nueva vista Livewire que muestra el estado de todas las metas/periodos con filtros por UR, programa, trimestre y estado. Incluye exportación PDF/Excel.

**Files:**
- Modify: `app/Enums/SystemPermission.php`
- Modify: `database/seeders/RolesAndPermissionsSeeder.php`
- Create: `app/Livewire/Tracking/SabanaCaptura.php`
- Create: `resources/views/livewire/tracking/sabana-captura.blade.php`
- Create: `app/Exports/Pdf/SabanaCapturaPdfExport.php`
- Create: `resources/views/exports/pdf/sabana-captura.blade.php`
- Create: `app/Exports/Excel/SabanaCapturaExcelExport.php`
- Modify: `routes/web/tracking.php`
- Test: `tests/Feature/Exports/SabanaCapturaTest.php`

**Step 1: Add new permission enum**

In `app/Enums/SystemPermission.php`, add before the closing brace:

```php
case VER_SABANA_CAPTURA = 'ver_sabana_captura';
case VER_CONCENTRADO_CAPTURA = 'ver_concentrado_captura';
```

**Step 2: Update RolesAndPermissionsSeeder**

In `database/seeders/RolesAndPermissionsSeeder.php`:

Add to planeador permissions array:
```php
SystemPermission::VER_SABANA_CAPTURA->value,
SystemPermission::VER_CONCENTRADO_CAPTURA->value,
```

Add to operador permissions array:
```php
SystemPermission::VER_SABANA_CAPTURA->value,
SystemPermission::VER_CONCENTRADO_CAPTURA->value,
```

**Step 3: Write the failing test**

Create `tests/Feature/Exports/SabanaCapturaTest.php`:

```php
<?php

namespace Tests\Feature\Exports;

use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SabanaCapturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_sabana_captura_accessible_by_operador(): void
    {
        $user = User::where('email', 'ele.operador@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('tracking.sabana-captura'));

        $response->assertStatus(200);
    }

    public function test_sabana_captura_accessible_by_planeador(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('tracking.sabana-captura'));

        $response->assertStatus(200);
    }

    public function test_sabana_captura_forbidden_without_permission(): void
    {
        $user = User::factory()->create();
        $team = \App\Models\Team::where('clave_ur', 'SE-001')->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $response = $this->actingAs($user)
            ->get(route('tracking.sabana-captura'));

        $response->assertStatus(403);
    }

    public function test_sabana_captura_renders_livewire_component(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Tracking\SabanaCaptura::class)
            ->assertStatus(200)
            ->assertSee('Sábana de Captura');
    }

    public function test_sabana_captura_filters_by_trimestre(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Tracking\SabanaCaptura::class)
            ->set('filtroTrimestre', 1)
            ->assertStatus(200);
    }

    public function test_admin_sees_all_teams_in_sabana(): void
    {
        $user = User::where('email', 'ele.admin@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Tracking\SabanaCaptura::class)
            ->assertStatus(200);
    }
}
```

**Step 4: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=SabanaCapturaTest`
Expected: FAIL — route/component don't exist.

**Step 5: Create SabanaCaptura Livewire component**

Create `app/Livewire/Tracking/SabanaCaptura.php`:

```php
<?php

namespace App\Livewire\Tracking;

use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SabanaCaptura extends Component
{
    public ?int $filtroPrograma = null;
    public ?int $filtroTrimestre = null;
    public ?string $filtroEstado = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ver_sabana_captura'), 403);
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        $programasQuery = $isAdmin
            ? ProgramaPresupuestario::query()
            : ProgramaPresupuestario::paraTeam($user->currentTeam->id);

        $programas = $programasQuery->orderBy('nombre')->get();

        $metasQuery = MetaPeriodo::query()
            ->with(['indicador.mirNivel.programa.team', 'avance.capturador'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin, $user) {
                if (! $isAdmin) {
                    $q->where('team_id', $user->currentTeam->id);
                }
            })
            ->where('activo', true);

        if ($this->filtroPrograma) {
            $metasQuery->whereHas('indicador.mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma));
        }

        if ($this->filtroTrimestre) {
            $metasQuery->where('periodo', $this->filtroTrimestre);
        }

        $metas = $metasQuery->orderBy('fecha_cierre')->get();

        // Classify each meta
        $filas = $metas->map(function ($meta) {
            $avance = $meta->avance;
            $estado = match (true) {
                $avance !== null => $avance->estado->value,
                $meta->fecha_cierre < now() => 'vencido',
                default => 'pendiente',
            };

            if ($this->filtroEstado && $estado !== $this->filtroEstado) {
                return null;
            }

            $diasRestantes = now()->diffInDays($meta->fecha_cierre, false);

            return [
                'programa_clave' => $meta->indicador->mirNivel->programa->clave ?? '—',
                'programa_nombre' => $meta->indicador->mirNivel->programa->nombre ?? '—',
                'indicador' => $meta->indicador->nombre,
                'periodo' => $meta->periodo,
                'meta_periodo' => $meta->meta_periodo,
                'estado' => $estado,
                'operador' => $avance?->capturador?->name ?? '—',
                'dias' => (int) $diasRestantes,
                'fecha_cierre' => $meta->fecha_cierre->format('d/m/Y'),
                'semaforo' => $avance?->semaforo_calculado,
            ];
        })->filter()->values();

        return view('livewire.tracking.sabana-captura', [
            'programas' => $programas,
            'filas' => $filas,
        ]);
    }

    public function exportarPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $export = new \App\Exports\Pdf\SabanaCapturaPdfExport(
            auth()->user(),
            $this->filtroPrograma,
            $this->filtroTrimestre,
            $this->filtroEstado,
        );

        $contenido = $export->generate();
        $filename = 'sabana-captura-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(fn () => print($contenido), $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportarExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $export = new \App\Exports\Excel\SabanaCapturaExcelExport(
            auth()->user(),
            $this->filtroPrograma,
            $this->filtroTrimestre,
            $this->filtroEstado,
        );

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'sabana-captura-' . now()->format('Ymd-His') . '.xlsx');
    }
}
```

**Step 6: Create SabanaCaptura Blade view**

Create `resources/views/livewire/tracking/sabana-captura.blade.php`:

```html
<x-page.container>
    <x-slot name="header">
        <x-page.header title="Sábana de Captura">
            <x-slot name="actions">
                <button wire:click="exportarPdf" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                    PDF
                </button>
                <button wire:click="exportarExcel" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                    Excel
                </button>
            </x-slot>
        </x-page.header>
    </x-slot>

    {{-- Filtros --}}
    <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Programa</label>
            <select wire:model.live="filtroPrograma" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Todos</option>
                @foreach($programas as $prog)
                    <option value="{{ $prog->id }}">{{ $prog->clave }} — {{ $prog->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Trimestre</label>
            <select wire:model.live="filtroTrimestre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Todos</option>
                @for($t = 1; $t <= 4; $t++)
                    <option value="{{ $t }}">T{{ $t }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Estado</label>
            <select wire:model.live="filtroEstado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="en_captura">En Captura</option>
                <option value="en_revision">En Revisión</option>
                <option value="aprobado">Aprobado</option>
                <option value="observado">Observado</option>
                <option value="vencido">Vencido</option>
            </select>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Programa</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Indicador</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">T</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Meta</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operador</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Cierre</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Días</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($filas as $fila)
                    <tr class="@if($fila['estado'] === 'vencido') bg-red-50 @elseif($fila['estado'] === 'aprobado') bg-green-50 @endif">
                        <td class="px-4 py-2 text-sm">{{ $fila['programa_clave'] }}</td>
                        <td class="px-4 py-2 text-sm">{{ $fila['indicador'] }}</td>
                        <td class="px-4 py-2 text-sm text-center">T{{ $fila['periodo'] }}</td>
                        <td class="px-4 py-2 text-sm text-right">{{ $fila['meta_periodo'] }}</td>
                        <td class="px-4 py-2 text-sm text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                @switch($fila['estado'])
                                    @case('aprobado') bg-green-100 text-green-800 @break
                                    @case('en_revision') bg-blue-100 text-blue-800 @break
                                    @case('en_captura') bg-yellow-100 text-yellow-800 @break
                                    @case('observado') bg-orange-100 text-orange-800 @break
                                    @case('vencido') bg-red-100 text-red-800 @break
                                    @default bg-gray-100 text-gray-800
                                @endswitch
                            ">{{ ucfirst(str_replace('_', ' ', $fila['estado'])) }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm">{{ $fila['operador'] }}</td>
                        <td class="px-4 py-2 text-sm text-center">{{ $fila['fecha_cierre'] }}</td>
                        <td class="px-4 py-2 text-sm text-center font-medium @if($fila['dias'] < 0) text-red-600 @elseif($fila['dias'] <= 7) text-orange-600 @else text-gray-600 @endif">
                            {{ $fila['dias'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">No se encontraron registros con los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-page.container>
```

**Step 7: Create SabanaCapturaPdfExport**

Create `app/Exports/Pdf/SabanaCapturaPdfExport.php`:

```php
<?php

namespace App\Exports\Pdf;

use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class SabanaCapturaPdfExport
{
    public function __construct(
        private User $user,
        private ?int $filtroPrograma = null,
        private ?int $filtroTrimestre = null,
        private ?string $filtroEstado = null,
    ) {}

    public function generate(): string
    {
        $isAdmin = $this->user->hasRole('admin');

        $metasQuery = MetaPeriodo::query()
            ->with(['indicador.mirNivel.programa', 'avance.capturador'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin) {
                if (! $isAdmin) {
                    $q->where('team_id', $this->user->currentTeam->id);
                }
            })
            ->where('activo', true);

        if ($this->filtroPrograma) {
            $metasQuery->whereHas('indicador.mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma));
        }
        if ($this->filtroTrimestre) {
            $metasQuery->where('periodo', $this->filtroTrimestre);
        }

        $metas = $metasQuery->orderBy('fecha_cierre')->get();

        $filas = $metas->map(function ($meta) {
            $avance = $meta->avance;
            $estado = match (true) {
                $avance !== null => $avance->estado->value,
                $meta->fecha_cierre < now() => 'vencido',
                default => 'pendiente',
            };

            if ($this->filtroEstado && $estado !== $this->filtroEstado) {
                return null;
            }

            return [
                'programa_clave' => $meta->indicador->mirNivel->programa->clave ?? '—',
                'indicador' => $meta->indicador->nombre,
                'periodo' => $meta->periodo,
                'meta_periodo' => $meta->meta_periodo,
                'estado' => $estado,
                'operador' => $avance?->capturador?->name ?? '—',
                'fecha_cierre' => $meta->fecha_cierre->format('d/m/Y'),
            ];
        })->filter()->values();

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.sabana-captura', [
            'filas' => $filas,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
            'filtros' => [
                'trimestre' => $this->filtroTrimestre,
                'estado' => $this->filtroEstado,
            ],
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->output();
    }
}
```

**Step 8: Create Sábana PDF Blade template**

Create `resources/views/exports/pdf/sabana-captura.blade.php`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sábana de Captura</title>
    <style>
        body { font-family: sans-serif; font-size: 9px; margin: 15px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 8px; color: #666; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 3px 5px; text-align: left; vertical-align: top; }
        th { background-color: #2d3748; color: white; font-size: 8px; }
        .estado-vencido { background-color: #fed7d7; }
        .estado-aprobado { background-color: #c6f6d5; }
        .estado-en_revision { background-color: #bee3f8; }
        .estado-observado { background-color: #fefcbf; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $encabezado['institucion'] }}</h2>
        <h3>{{ $encabezado['dependencia'] }}</h3>
        <h3>Sábana de Captura</h3>
        @if($filtros['trimestre'])<p>Trimestre: T{{ $filtros['trimestre'] }}</p>@endif
        @if($filtros['estado'])<p>Estado: {{ ucfirst(str_replace('_', ' ', $filtros['estado'])) }}</p>@endif
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    <table>
        <thead>
            <tr>
                <th>Programa</th>
                <th>Indicador</th>
                <th>T</th>
                <th>Meta</th>
                <th>Estado</th>
                <th>Operador</th>
                <th>Cierre</th>
            </tr>
        </thead>
        <tbody>
            @foreach($filas as $fila)
                <tr class="estado-{{ $fila['estado'] }}">
                    <td>{{ $fila['programa_clave'] }}</td>
                    <td>{{ $fila['indicador'] }}</td>
                    <td>T{{ $fila['periodo'] }}</td>
                    <td>{{ $fila['meta_periodo'] }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $fila['estado'])) }}</td>
                    <td>{{ $fila['operador'] }}</td>
                    <td>{{ $fila['fecha_cierre'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
```

**Step 9: Create SabanaCapturaExcelExport**

Create `app/Exports/Excel/SabanaCapturaExcelExport.php`:

```php
<?php

namespace App\Exports\Excel;

use App\Models\Mml\MetaPeriodo;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SabanaCapturaExcelExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        private User $user,
        private ?int $filtroPrograma = null,
        private ?int $filtroTrimestre = null,
        private ?string $filtroEstado = null,
    ) {}

    public function collection(): Collection
    {
        $isAdmin = $this->user->hasRole('admin');

        $metasQuery = MetaPeriodo::query()
            ->with(['indicador.mirNivel.programa', 'avance.capturador'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin) {
                if (! $isAdmin) {
                    $q->where('team_id', $this->user->currentTeam->id);
                }
            })
            ->where('activo', true);

        if ($this->filtroPrograma) {
            $metasQuery->whereHas('indicador.mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma));
        }
        if ($this->filtroTrimestre) {
            $metasQuery->where('periodo', $this->filtroTrimestre);
        }

        $metas = $metasQuery->orderBy('fecha_cierre')->get();

        return $metas->map(function ($meta) {
            $avance = $meta->avance;
            $estado = match (true) {
                $avance !== null => $avance->estado->value,
                $meta->fecha_cierre < now() => 'vencido',
                default => 'pendiente',
            };

            if ($this->filtroEstado && $estado !== $this->filtroEstado) {
                return null;
            }

            return [
                'programa' => $meta->indicador->mirNivel->programa->clave ?? '—',
                'indicador' => $meta->indicador->nombre,
                'trimestre' => $meta->periodo,
                'meta' => $meta->meta_periodo,
                'estado' => ucfirst(str_replace('_', ' ', $estado)),
                'operador' => $avance?->capturador?->name ?? '—',
                'fecha_cierre' => $meta->fecha_cierre->format('Y-m-d'),
            ];
        })->filter()->values();
    }

    public function headings(): array
    {
        return ['Programa', 'Indicador', 'Trimestre', 'Meta', 'Estado', 'Operador', 'Fecha Cierre'];
    }

    public function title(): string
    {
        return 'Sábana de Captura';
    }
}
```

**Step 10: Add route**

In `routes/web/tracking.php`, add after the desbloqueos route:

```php
Route::get('/sabana-captura', \App\Livewire\Tracking\SabanaCaptura::class)
    ->name('tracking.sabana-captura')
    ->middleware('can:ver_sabana_captura');
```

**Step 11: Run tests to verify they pass**

Run: `./vendor/bin/sail artisan test --filter=SabanaCapturaTest`
Expected: All 6 tests PASS.

**Step 12: Commit**

```bash
git add app/Enums/SystemPermission.php \
      database/seeders/RolesAndPermissionsSeeder.php \
      app/Livewire/Tracking/SabanaCaptura.php \
      resources/views/livewire/tracking/sabana-captura.blade.php \
      app/Exports/Pdf/SabanaCapturaPdfExport.php \
      resources/views/exports/pdf/sabana-captura.blade.php \
      app/Exports/Excel/SabanaCapturaExcelExport.php \
      routes/web/tracking.php \
      tests/Feature/Exports/SabanaCapturaTest.php
git commit -m "feat(reports): add Sábana de Captura Livewire view with PDF/Excel export

R4 operational report: new Livewire view showing all metas/periodos status
with filters by programa, trimestre, and estado. Includes PDF and Excel
export. New permissions: ver_sabana_captura, ver_concentrado_captura."
```

---

## Task 5: Concentrado de Captura — Livewire + Exports (R5)

Vista Livewire con métricas resumen y tabla agrupada por programa/indicador, con conteos por estado.

**Files:**
- Create: `app/Livewire/Tracking/ConcentradoCaptura.php`
- Create: `resources/views/livewire/tracking/concentrado-captura.blade.php`
- Create: `app/Exports/Pdf/ConcentradoCapturaPdfExport.php`
- Create: `resources/views/exports/pdf/concentrado-captura.blade.php`
- Create: `app/Exports/Excel/ConcentradoCapturaExcelExport.php`
- Modify: `routes/web/tracking.php`
- Test: `tests/Feature/Exports/ConcentradoCapturaTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Exports/ConcentradoCapturaTest.php`:

```php
<?php

namespace Tests\Feature\Exports;

use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConcentradoCapturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_concentrado_accessible_by_operador(): void
    {
        $user = User::where('email', 'ele.operador@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('tracking.concentrado-captura'));

        $response->assertStatus(200);
    }

    public function test_concentrado_forbidden_without_permission(): void
    {
        $user = User::factory()->create();
        $team = \App\Models\Team::where('clave_ur', 'SE-001')->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $response = $this->actingAs($user)
            ->get(route('tracking.concentrado-captura'));

        $response->assertStatus(403);
    }

    public function test_concentrado_renders_with_metrics(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Tracking\ConcentradoCaptura::class)
            ->assertStatus(200)
            ->assertSee('Concentrado de Captura');
    }

    public function test_concentrado_filters_by_date_range(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Tracking\ConcentradoCaptura::class)
            ->set('fechaDesde', '2025-01-01')
            ->set('fechaHasta', '2025-12-31')
            ->assertStatus(200);
    }

    public function test_admin_sees_all_teams_in_concentrado(): void
    {
        $user = User::where('email', 'ele.admin@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Tracking\ConcentradoCaptura::class)
            ->assertStatus(200);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=ConcentradoCapturaTest`
Expected: FAIL — route/component don't exist.

**Step 3: Create ConcentradoCaptura Livewire component**

Create `app/Livewire/Tracking/ConcentradoCaptura.php`:

```php
<?php

namespace App\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ConcentradoCaptura extends Component
{
    public ?string $fechaDesde = null;
    public ?string $fechaHasta = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ver_concentrado_captura'), 403);

        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        $avancesQuery = Avance::query()
            ->with(['indicador.mirNivel.programa', 'metaPeriodo'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin, $user) {
                if (! $isAdmin) {
                    $q->where('team_id', $user->currentTeam->id);
                }
            });

        if ($this->fechaDesde) {
            $avancesQuery->where('updated_at', '>=', $this->fechaDesde . ' 00:00:00');
        }
        if ($this->fechaHasta) {
            $avancesQuery->where('updated_at', '<=', $this->fechaHasta . ' 23:59:59');
        }

        $avances = $avancesQuery->get();

        // Métricas resumen
        $metricas = [
            'total' => $avances->count(),
            'aprobados' => $avances->where('estado', EstadoAvance::APROBADO)->count(),
            'en_revision' => $avances->where('estado', EstadoAvance::EN_REVISION)->count(),
            'en_captura' => $avances->where('estado', EstadoAvance::EN_CAPTURA)->count(),
            'observados' => $avances->where('estado', EstadoAvance::OBSERVADO)->count(),
        ];

        // Agrupado por programa → indicador
        $agrupado = $avances->groupBy(fn ($a) => $a->indicador->mirNivel->programa->clave ?? '—')
            ->map(fn ($porPrograma) => $porPrograma->groupBy(fn ($a) => $a->indicador->nombre)
                ->map(fn ($porIndicador) => [
                    'total' => $porIndicador->count(),
                    'aprobados' => $porIndicador->where('estado', EstadoAvance::APROBADO)->count(),
                    'en_revision' => $porIndicador->where('estado', EstadoAvance::EN_REVISION)->count(),
                    'en_captura' => $porIndicador->where('estado', EstadoAvance::EN_CAPTURA)->count(),
                    'observados' => $porIndicador->where('estado', EstadoAvance::OBSERVADO)->count(),
                ])
            );

        return view('livewire.tracking.concentrado-captura', [
            'metricas' => $metricas,
            'agrupado' => $agrupado,
        ]);
    }

    public function exportarPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $export = new \App\Exports\Pdf\ConcentradoCapturaPdfExport(
            auth()->user(),
            $this->fechaDesde,
            $this->fechaHasta,
        );

        $contenido = $export->generate();
        $filename = 'concentrado-captura-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(fn () => print($contenido), $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportarExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $export = new \App\Exports\Excel\ConcentradoCapturaExcelExport(
            auth()->user(),
            $this->fechaDesde,
            $this->fechaHasta,
        );

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'concentrado-captura-' . now()->format('Ymd-His') . '.xlsx');
    }
}
```

**Step 4: Create ConcentradoCaptura Blade view**

Create `resources/views/livewire/tracking/concentrado-captura.blade.php`:

```html
<x-page.container>
    <x-slot name="header">
        <x-page.header title="Concentrado de Captura">
            <x-slot name="actions">
                <button wire:click="exportarPdf" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                    PDF
                </button>
                <button wire:click="exportarExcel" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                    Excel
                </button>
            </x-slot>
        </x-page.header>
    </x-slot>

    {{-- Filtros --}}
    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Desde</label>
            <input type="date" wire:model.live="fechaDesde" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Hasta</label>
            <input type="date" wire:model.live="fechaHasta" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
    </div>

    {{-- Métricas --}}
    <div class="mb-6 grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $metricas['total'] }}</div>
            <div class="text-sm text-gray-500">Total</div>
        </div>
        <div class="bg-green-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $metricas['aprobados'] }}</div>
            <div class="text-sm text-gray-500">Aprobados</div>
        </div>
        <div class="bg-blue-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $metricas['en_revision'] }}</div>
            <div class="text-sm text-gray-500">En Revisión</div>
        </div>
        <div class="bg-yellow-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $metricas['en_captura'] }}</div>
            <div class="text-sm text-gray-500">En Captura</div>
        </div>
        <div class="bg-orange-50 rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-bold text-orange-600">{{ $metricas['observados'] }}</div>
            <div class="text-sm text-gray-500">Observados</div>
        </div>
    </div>

    {{-- Tabla agrupada --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Programa</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Indicador</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aprobados</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">En Revisión</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">En Captura</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Observados</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($agrupado as $programa => $indicadores)
                    @foreach($indicadores as $indicador => $conteos)
                        <tr>
                            @if($loop->first)
                                <td class="px-4 py-2 text-sm font-medium" rowspan="{{ $indicadores->count() }}">{{ $programa }}</td>
                            @endif
                            <td class="px-4 py-2 text-sm">{{ $indicador }}</td>
                            <td class="px-4 py-2 text-sm text-center">{{ $conteos['total'] }}</td>
                            <td class="px-4 py-2 text-sm text-center text-green-600">{{ $conteos['aprobados'] }}</td>
                            <td class="px-4 py-2 text-sm text-center text-blue-600">{{ $conteos['en_revision'] }}</td>
                            <td class="px-4 py-2 text-sm text-center text-yellow-600">{{ $conteos['en_captura'] }}</td>
                            <td class="px-4 py-2 text-sm text-center text-orange-600">{{ $conteos['observados'] }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron registros en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-page.container>
```

**Step 5: Create ConcentradoCapturaPdfExport**

Create `app/Exports/Pdf/ConcentradoCapturaPdfExport.php`:

```php
<?php

namespace App\Exports\Pdf;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class ConcentradoCapturaPdfExport
{
    public function __construct(
        private User $user,
        private ?string $fechaDesde = null,
        private ?string $fechaHasta = null,
    ) {}

    public function generate(): string
    {
        $isAdmin = $this->user->hasRole('admin');

        $avancesQuery = Avance::query()
            ->with(['indicador.mirNivel.programa'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin) {
                if (! $isAdmin) {
                    $q->where('team_id', $this->user->currentTeam->id);
                }
            });

        if ($this->fechaDesde) {
            $avancesQuery->where('updated_at', '>=', $this->fechaDesde . ' 00:00:00');
        }
        if ($this->fechaHasta) {
            $avancesQuery->where('updated_at', '<=', $this->fechaHasta . ' 23:59:59');
        }

        $avances = $avancesQuery->get();

        $agrupado = $avances->groupBy(fn ($a) => $a->indicador->mirNivel->programa->clave ?? '—')
            ->map(fn ($porPrograma) => $porPrograma->groupBy(fn ($a) => $a->indicador->nombre)
                ->map(fn ($porIndicador) => [
                    'total' => $porIndicador->count(),
                    'aprobados' => $porIndicador->where('estado', EstadoAvance::APROBADO)->count(),
                    'en_revision' => $porIndicador->where('estado', EstadoAvance::EN_REVISION)->count(),
                    'en_captura' => $porIndicador->where('estado', EstadoAvance::EN_CAPTURA)->count(),
                    'observados' => $porIndicador->where('estado', EstadoAvance::OBSERVADO)->count(),
                ])
            );

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.concentrado-captura', [
            'agrupado' => $agrupado,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->output();
    }
}
```

**Step 6: Create Concentrado PDF Blade template**

Create `resources/views/exports/pdf/concentrado-captura.blade.php`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Concentrado de Captura</title>
    <style>
        body { font-family: sans-serif; font-size: 9px; margin: 15px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 8px; color: #666; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 3px 5px; text-align: left; vertical-align: top; }
        th { background-color: #2d3748; color: white; font-size: 8px; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $encabezado['institucion'] }}</h2>
        <h3>{{ $encabezado['dependencia'] }}</h3>
        <h3>Concentrado de Captura</h3>
        <p>Período: {{ $fechaDesde ?? '—' }} al {{ $fechaHasta ?? '—' }}</p>
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    <table>
        <thead>
            <tr>
                <th>Programa</th>
                <th>Indicador</th>
                <th class="text-center">Total</th>
                <th class="text-center">Aprobados</th>
                <th class="text-center">En Revisión</th>
                <th class="text-center">En Captura</th>
                <th class="text-center">Observados</th>
            </tr>
        </thead>
        <tbody>
            @foreach($agrupado as $programa => $indicadores)
                @foreach($indicadores as $indicador => $conteos)
                    <tr>
                        @if($loop->first)
                            <td rowspan="{{ $indicadores->count() }}"><strong>{{ $programa }}</strong></td>
                        @endif
                        <td>{{ $indicador }}</td>
                        <td class="text-center">{{ $conteos['total'] }}</td>
                        <td class="text-center">{{ $conteos['aprobados'] }}</td>
                        <td class="text-center">{{ $conteos['en_revision'] }}</td>
                        <td class="text-center">{{ $conteos['en_captura'] }}</td>
                        <td class="text-center">{{ $conteos['observados'] }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
```

**Step 7: Create ConcentradoCapturaExcelExport**

Create `app/Exports/Excel/ConcentradoCapturaExcelExport.php`:

```php
<?php

namespace App\Exports\Excel;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ConcentradoCapturaExcelExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        private User $user,
        private ?string $fechaDesde = null,
        private ?string $fechaHasta = null,
    ) {}

    public function collection(): Collection
    {
        $isAdmin = $this->user->hasRole('admin');

        $avancesQuery = Avance::query()
            ->with(['indicador.mirNivel.programa'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin) {
                if (! $isAdmin) {
                    $q->where('team_id', $this->user->currentTeam->id);
                }
            });

        if ($this->fechaDesde) {
            $avancesQuery->where('updated_at', '>=', $this->fechaDesde . ' 00:00:00');
        }
        if ($this->fechaHasta) {
            $avancesQuery->where('updated_at', '<=', $this->fechaHasta . ' 23:59:59');
        }

        $avances = $avancesQuery->get();

        $rows = collect();

        $agrupado = $avances->groupBy(fn ($a) => $a->indicador->mirNivel->programa->clave ?? '—');

        foreach ($agrupado as $programa => $porPrograma) {
            $porIndicador = $porPrograma->groupBy(fn ($a) => $a->indicador->nombre);

            foreach ($porIndicador as $indicador => $grupo) {
                $rows->push([
                    'programa' => $programa,
                    'indicador' => $indicador,
                    'total' => $grupo->count(),
                    'aprobados' => $grupo->where('estado', EstadoAvance::APROBADO)->count(),
                    'en_revision' => $grupo->where('estado', EstadoAvance::EN_REVISION)->count(),
                    'en_captura' => $grupo->where('estado', EstadoAvance::EN_CAPTURA)->count(),
                    'observados' => $grupo->where('estado', EstadoAvance::OBSERVADO)->count(),
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Programa', 'Indicador', 'Total', 'Aprobados', 'En Revisión', 'En Captura', 'Observados'];
    }

    public function title(): string
    {
        return 'Concentrado';
    }
}
```

**Step 8: Add route**

In `routes/web/tracking.php`, add after the sabana-captura route:

```php
Route::get('/concentrado-captura', \App\Livewire\Tracking\ConcentradoCaptura::class)
    ->name('tracking.concentrado-captura')
    ->middleware('can:ver_concentrado_captura');
```

**Step 9: Run tests to verify they pass**

Run: `./vendor/bin/sail artisan test --filter=ConcentradoCapturaTest`
Expected: All 5 tests PASS.

**Step 10: Commit**

```bash
git add app/Livewire/Tracking/ConcentradoCaptura.php \
      resources/views/livewire/tracking/concentrado-captura.blade.php \
      app/Exports/Pdf/ConcentradoCapturaPdfExport.php \
      resources/views/exports/pdf/concentrado-captura.blade.php \
      app/Exports/Excel/ConcentradoCapturaExcelExport.php \
      routes/web/tracking.php \
      tests/Feature/Exports/ConcentradoCapturaTest.php
git commit -m "feat(reports): add Concentrado de Captura Livewire view with PDF/Excel

R5 operational report: summary metrics (total, aprobados, en_revision,
en_captura, observados) with table grouped by programa/indicador.
Date range filter, PDF and Excel export."
```

---

## Task 6: Documento de Integración Futura (Presupuesto + Padrón)

Crear documento arquitectónico que guíe la integración futura de datos financieros y padrón de beneficiarios.

**Files:**
- Create: `docs/architecture/future-integration-presupuesto-padron.md`

**Step 1: Write the document**

Create `docs/architecture/future-integration-presupuesto-padron.md` with:

- **Módulo Presupuestal**: esquema de tablas sugerido (`partidas_presupuestales`, `avance_financiero`), relaciones con `programa_presupuestarios`, fórmula de cruce avance físico vs financiero para Cuenta Pública, puntos de integración con sistemas externos (SIIF, SAP), permisos necesarios, reportes desbloqueados (Cuenta Pública, FMyE con presupuesto)
- **Módulo Padrón de Beneficiarios**: modelo de datos (`beneficiarios`, `apoyos_entregados`, `documentos_soporte`), anonimización automática para nivel Transparencia, georreferenciación para Análisis de Focalización, consideraciones LGPDPPSO (Ley General de Protección de Datos Personales en Posesión de Sujetos Obligados), permisos necesarios, reportes desbloqueados (Padrón Público, Focalización/Brechas)
- Para cada módulo: migraciones sugeridas con campos, relaciones con modelos existentes, enums nuevos, servicios necesarios, y diagrama de integración con el sistema actual

**Step 2: Commit**

```bash
git add docs/architecture/future-integration-presupuesto-padron.md
git commit -m "docs: add future integration architecture for presupuesto and padrón

Architectural guide for future modules: presupuestal (partidas, avance
financiero, Cuenta Pública) and padrón de beneficiarios (anonimización,
georreferenciación, LGPDPPSO compliance). Includes suggested migrations,
relationships, permissions, and unlocked reports."
```

---

## Summary

| Task | Report | Type | Files Created | Files Modified |
|------|--------|------|---------------|----------------|
| 1 | R1 Avance Trimestral + Vo.Bo. | Improve existing | 2 (partial, test) | 2 |
| 2 | R2 FMyE | New PDF | 3 (export, view, test) | 1 (controller) |
| 3 | R3 MIR Aprobada | Improve existing | 1 (test) | 2 (view, routes) |
| 4 | R4 Sábana de Captura | New Livewire + PDF + Excel | 6 (component, views, exports, test) | 3 (enum, seeder, routes) |
| 5 | R5 Concentrado de Captura | New Livewire + PDF + Excel | 6 (component, views, exports, test) | 1 (routes) |
| 6 | Doc Integración Futura | Documentation | 1 | 0 |

**Total:** 19 files created, 9 files modified, 6 commits.
