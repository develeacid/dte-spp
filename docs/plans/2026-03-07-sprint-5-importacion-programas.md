# Sprint 5: Importación de Programas Existentes — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Allow importing existing budget programs (from previous fiscal years or other agencies) with multi-format parsing, completeness diagnosis, AI-assisted gap correction, cascade alignment, and goal scheduling.

**Architecture:** Multi-step wizard backed by services: `MirParserService` (parse) → `MirDiagnosticoService` (diagnose) → `MirPersistenciaService` (persist) → reuse `SemanticSearchService` (align) → `CalendarizacionService` (schedule). Each step is a Livewire component. New `ImportacionReporte` model tracks import state as JSONB.

**Tech Stack:** Laravel 12, Livewire 3, PostgreSQL (JSONB), maatwebsite/laravel-excel ^3.1, existing LlmService + SemanticSearchService.

**Baseline:** 248 tests passing, 7 skipped. All new code must maintain zero regressions.

**Branch strategy:** Each task gets its own feature branch off `desarrollo`, merged back with `Resolves DTE-XX`.

---

## Task 1: Importador Multi-formato y Diagnóstico (S5-T1)

**Branch:** `feat/S5-T1-importador-multiformato`

**Files:**
- Create: `app/DTOs/ImportedMirData.php`
- Create: `database/migrations/2026_03_09_010000_create_importacion_reportes_table.php`
- Create: `app/Models/Mml/ImportacionReporte.php`
- Create: `app/Services/Mml/MirParserService.php`
- Create: `app/Services/Mml/MirDiagnosticoService.php`
- Create: `app/Livewire/Mml/ImportarPrograma.php`
- Create: `resources/views/livewire/mml/importar-programa.blade.php`
- Create: `tests/Feature/Mml/MirParserTest.php`
- Create: `tests/Feature/Mml/MirDiagnosticoTest.php`
- Create: `tests/Feature/Mml/ImportarProgramaTest.php`
- Create: `tests/fixtures/mir-sample.md`
- Create: `tests/fixtures/mir-sample.csv`
- Modify: `routes/web/mml.php` (add import route)

### Step 1: Create branch

```bash
git checkout desarrollo && git pull
git checkout -b feat/S5-T1-importador-multiformato
```

### Step 2: Install maatwebsite/laravel-excel

```bash
./vendor/bin/sail composer require maatwebsite/laravel-excel:"^3.1"
```

### Step 3: Write DTO `ImportedMirData`

Create `app/DTOs/ImportedMirData.php`:

```php
<?php

namespace App\DTOs;

class ImportedMirData
{
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $clave = null,
        public readonly ?int $ejercicioFiscal = null,
        public readonly array $niveles = [],
    ) {}

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'clave' => $this->clave,
            'ejercicio_fiscal' => $this->ejercicioFiscal,
            'niveles' => array_map(fn (array $n) => $n, $this->niveles),
        ];
    }

    /**
     * Each nivel in $niveles must have:
     *   tipo_nivel: string (fin|proposito|componente|actividad)
     *   resumen_narrativo: ?string
     *   supuestos: ?string
     *   orden: int
     *   componente_idx: ?int (index in niveles array for activity→component mapping)
     *   indicadores: array of [
     *     nombre: ?string, formula_texto: ?string, tipo: ?string,
     *     dimension: ?string, frecuencia: ?string, sentido: ?string,
     *     linea_base: ?float, meta: ?float,
     *     rangos_semaforo: ?array{verde_min,verde_max,amarillo_min,amarillo_max,rojo_min,rojo_max},
     *     variables: array of [simbolo: string, nombre: string],
     *     medios: array of [nombre: string, fuente: ?string],
     *   ]
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data['nombre'] ?? null,
            clave: $data['clave'] ?? null,
            ejercicioFiscal: $data['ejercicio_fiscal'] ?? null,
            niveles: $data['niveles'] ?? [],
        );
    }
}
```

### Step 4: Write migration for `importacion_reportes`

Create `database/migrations/2026_03_09_010000_create_importacion_reportes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importacion_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->nullable()->constrained('programa_presupuestarios')->nullOnDelete();
            $table->foreignId('team_id')
                ->constrained('teams')->cascadeOnDelete();
            $table->string('archivo_original');
            $table->string('formato', 10); // md, csv, xlsx
            $table->jsonb('datos_parseados'); // ImportedMirData serialized
            $table->jsonb('diagnostico')->nullable(); // Array of gaps
            $table->string('estado', 20)->default('pendiente'); // pendiente, procesado, descartado
            $table->foreignId('created_by')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacion_reportes');
    }
};
```

### Step 5: Write model `ImportacionReporte`

Create `app/Models/Mml/ImportacionReporte.php`:

```php
<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacionReporte extends Model
{
    protected $table = 'importacion_reportes';

    protected $fillable = [
        'programa_presupuestario_id', 'team_id', 'archivo_original',
        'formato', 'datos_parseados', 'diagnostico', 'estado', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'datos_parseados' => 'array',
            'diagnostico' => 'array',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### Step 6: Run migration to verify

```bash
./vendor/bin/sail artisan migrate
```

### Step 7: Write `MirParserService` — Markdown parser

Create `app/Services/Mml/MirParserService.php`:

```php
<?php

namespace App\Services\Mml;

use App\DTOs\ImportedMirData;
use Maatwebsite\Excel\Facades\Excel;

class MirParserService
{
    /**
     * Parse a Markdown MIR table. Expected format:
     * # Programa: <nombre> (<clave>)
     * ## Ejercicio: <año>
     * | Nivel | Resumen Narrativo | Indicador | Fórmula | Tipo | Dimensión | Frecuencia | Medio Verificación | Supuestos |
     */
    public function fromMarkdown(string $content): ImportedMirData
    {
        $lines = explode("\n", $content);
        $nombre = null;
        $clave = null;
        $ejercicio = null;
        $niveles = [];
        $componenteIndices = []; // track componente positions

        // Extract header info
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^#\s+Programa:\s*(.+?)\s*\((.+?)\)/', $line, $m)) {
                $nombre = trim($m[1]);
                $clave = trim($m[2]);
            }
            if (preg_match('/^##\s+Ejercicio:\s*(\d{4})/', $line, $m)) {
                $ejercicio = (int) $m[1];
            }
        }

        // Parse table rows (skip header and separator)
        $inTable = false;
        $headerSkipped = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '|') && str_contains($line, 'Nivel')) {
                $inTable = true;
                $headerSkipped = false;
                continue;
            }
            if ($inTable && preg_match('/^\|[\s-]+\|/', $line)) {
                $headerSkipped = true;
                continue;
            }
            if ($inTable && $headerSkipped && str_starts_with($line, '|')) {
                $cells = array_map('trim', explode('|', $line));
                $cells = array_values(array_filter($cells, fn ($c) => $c !== ''));

                if (count($cells) < 3) continue;

                $tipoNivel = $this->normalizeTipoNivel($cells[0] ?? '');
                if (! $tipoNivel) continue;

                $componenteIdx = null;
                if ($tipoNivel === 'actividad') {
                    // Link to last componente
                    $componenteIdx = $this->findLastComponenteIndex($niveles);
                }

                $indicadores = [];
                $nombreInd = trim($cells[2] ?? '');
                if ($nombreInd) {
                    $indicadores[] = [
                        'nombre' => $nombreInd,
                        'formula_texto' => $cells[3] ?? null,
                        'tipo' => $this->normalizeOrNull($cells[4] ?? null),
                        'dimension' => $this->normalizeOrNull($cells[5] ?? null),
                        'frecuencia' => $this->normalizeOrNull($cells[6] ?? null),
                        'sentido' => null,
                        'linea_base' => null,
                        'meta' => null,
                        'rangos_semaforo' => null,
                        'variables' => [],
                        'medios' => $this->parseMedios($cells[7] ?? ''),
                    ];
                }

                $niveles[] = [
                    'tipo_nivel' => $tipoNivel,
                    'resumen_narrativo' => trim($cells[1] ?? '') ?: null,
                    'supuestos' => trim($cells[8] ?? '') ?: null,
                    'orden' => $this->countByType($niveles, $tipoNivel) + 1,
                    'componente_idx' => $componenteIdx,
                    'indicadores' => $indicadores,
                ];

                if ($tipoNivel === 'componente') {
                    $componenteIndices[] = count($niveles) - 1;
                }
            }
        }

        return new ImportedMirData(
            nombre: $nombre,
            clave: $clave,
            ejercicioFiscal: $ejercicio,
            niveles: $niveles,
        );
    }

    /**
     * Parse CSV file. Columns: Nivel, Resumen Narrativo, Indicador, Fórmula,
     * Tipo, Dimensión, Frecuencia, Medio Verificación, Supuestos
     */
    public function fromCsv(string $path): ImportedMirData
    {
        return $this->fromTabular($path);
    }

    /**
     * Parse Excel file. Same column structure as CSV.
     */
    public function fromExcel(string $path): ImportedMirData
    {
        return $this->fromTabular($path);
    }

    private function fromTabular(string $path): ImportedMirData
    {
        $rows = Excel::toArray(null, $path)[0] ?? [];
        if (empty($rows)) {
            return new ImportedMirData();
        }

        $header = array_map(fn ($h) => mb_strtolower(trim($h ?? '')), $rows[0]);
        $niveles = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $data = [];
            foreach ($header as $idx => $col) {
                $data[$col] = trim($row[$idx] ?? '') ?: null;
            }

            $tipoNivel = $this->normalizeTipoNivel($data['nivel'] ?? '');
            if (! $tipoNivel) continue;

            $componenteIdx = null;
            if ($tipoNivel === 'actividad') {
                $componenteIdx = $this->findLastComponenteIndex($niveles);
            }

            $indicadores = [];
            $nombreInd = $data['indicador'] ?? null;
            if ($nombreInd) {
                $indicadores[] = [
                    'nombre' => $nombreInd,
                    'formula_texto' => $data['fórmula'] ?? $data['formula'] ?? null,
                    'tipo' => $this->normalizeOrNull($data['tipo'] ?? null),
                    'dimension' => $this->normalizeOrNull($data['dimensión'] ?? $data['dimension'] ?? null),
                    'frecuencia' => $this->normalizeOrNull($data['frecuencia'] ?? null),
                    'sentido' => $this->normalizeOrNull($data['sentido'] ?? null),
                    'linea_base' => isset($data['línea base']) ? (float) $data['línea base'] : (isset($data['linea base']) ? (float) $data['linea base'] : null),
                    'meta' => isset($data['meta']) ? (float) $data['meta'] : null,
                    'rangos_semaforo' => null,
                    'variables' => [],
                    'medios' => $this->parseMedios($data['medio verificación'] ?? $data['medio verificacion'] ?? ''),
                ];
            }

            $niveles[] = [
                'tipo_nivel' => $tipoNivel,
                'resumen_narrativo' => $data['resumen narrativo'] ?? null,
                'supuestos' => $data['supuestos'] ?? null,
                'orden' => $this->countByType($niveles, $tipoNivel) + 1,
                'componente_idx' => $componenteIdx,
                'indicadores' => $indicadores,
            ];
        }

        return new ImportedMirData(niveles: $niveles);
    }

    private function normalizeTipoNivel(string $text): ?string
    {
        $text = mb_strtolower(trim($text));
        $map = [
            'fin' => 'fin',
            'propósito' => 'proposito', 'proposito' => 'proposito',
            'componente' => 'componente',
            'actividad' => 'actividad',
        ];
        return $map[$text] ?? null;
    }

    private function normalizeOrNull(?string $value): ?string
    {
        if (! $value || trim($value) === '') return null;
        return mb_strtolower(trim($value));
    }

    private function parseMedios(string $text): array
    {
        if (! $text || trim($text) === '') return [];
        // Medios separated by semicolons or newlines
        $parts = preg_split('/[;\n]/', $text);
        return array_values(array_filter(array_map(fn ($p) => [
            'nombre' => trim($p),
            'fuente' => null,
        ], $parts), fn ($m) => $m['nombre'] !== ''));
    }

    private function findLastComponenteIndex(array $niveles): ?int
    {
        for ($i = count($niveles) - 1; $i >= 0; $i--) {
            if ($niveles[$i]['tipo_nivel'] === 'componente') {
                return $i;
            }
        }
        return null;
    }

    private function countByType(array $niveles, string $tipo): int
    {
        return count(array_filter($niveles, fn ($n) => $n['tipo_nivel'] === $tipo));
    }
}
```

### Step 8: Write `MirDiagnosticoService`

Create `app/Services/Mml/MirDiagnosticoService.php`:

```php
<?php

namespace App\Services\Mml;

use App\DTOs\ImportedMirData;

class MirDiagnosticoService
{
    /**
     * Diagnose completeness gaps in imported MIR data.
     *
     * @return array Array of gaps, each: [nivel_idx, indicador_idx?, campo, severidad, mensaje]
     *               severidad: critico | menor | advertencia
     */
    public function diagnosticar(ImportedMirData $data): array
    {
        $huecos = [];

        foreach ($data->niveles as $nIdx => $nivel) {
            $label = ucfirst($nivel['tipo_nivel']) . ' #' . $nivel['orden'];

            // Nivel-level checks
            if (empty($nivel['resumen_narrativo'])) {
                $huecos[] = $this->hueco($nIdx, null, 'resumen_narrativo', 'critico',
                    "{$label}: Resumen narrativo faltante.");
            }

            if (empty($nivel['indicadores'])) {
                $huecos[] = $this->hueco($nIdx, null, 'indicadores', 'critico',
                    "{$label}: Sin indicadores definidos.");
            }

            if (empty($nivel['supuestos'])) {
                $huecos[] = $this->hueco($nIdx, null, 'supuestos', 'menor',
                    "{$label}: Supuestos no definidos.");
            }

            foreach ($nivel['indicadores'] ?? [] as $iIdx => $indicador) {
                $indLabel = "{$label} → Indicador " . ($iIdx + 1);

                // Critical gaps (block tracking)
                foreach (['tipo', 'dimension', 'frecuencia'] as $campo) {
                    if (empty($indicador[$campo])) {
                        $huecos[] = $this->hueco($nIdx, $iIdx, $campo, 'critico',
                            "{$indLabel}: {$campo} no definido.");
                    } elseif (! $this->esValorEnumValido($campo, $indicador[$campo])) {
                        $huecos[] = $this->hueco($nIdx, $iIdx, $campo, 'advertencia',
                            "{$indLabel}: valor '{$indicador[$campo]}' no reconocido para {$campo}.");
                    }
                }

                if (empty($indicador['formula_texto'])) {
                    $huecos[] = $this->hueco($nIdx, $iIdx, 'formula_texto', 'critico',
                        "{$indLabel}: Fórmula no definida.");
                }

                if (empty($indicador['medios'])) {
                    $huecos[] = $this->hueco($nIdx, $iIdx, 'medios', 'critico',
                        "{$indLabel}: Sin medios de verificación.");
                }

                // Minor gaps
                if (empty($indicador['sentido'])) {
                    $huecos[] = $this->hueco($nIdx, $iIdx, 'sentido', 'menor',
                        "{$indLabel}: Sentido del indicador no definido.");
                }

                if (is_null($indicador['linea_base'] ?? null)) {
                    $huecos[] = $this->hueco($nIdx, $iIdx, 'linea_base', 'menor',
                        "{$indLabel}: Línea base no definida.");
                }

                if (is_null($indicador['meta'] ?? null)) {
                    $huecos[] = $this->hueco($nIdx, $iIdx, 'meta', 'menor',
                        "{$indLabel}: Meta no definida.");
                }

                if (empty($indicador['rangos_semaforo'])) {
                    $huecos[] = $this->hueco($nIdx, $iIdx, 'rangos_semaforo', 'menor',
                        "{$indLabel}: Rangos de semáforo no definidos.");
                }
            }
        }

        return $huecos;
    }

    /**
     * Count gaps by severity.
     */
    public function conteo(array $diagnostico): array
    {
        $counts = ['critico' => 0, 'menor' => 0, 'advertencia' => 0];
        foreach ($diagnostico as $hueco) {
            $counts[$hueco['severidad']]++;
        }
        return $counts;
    }

    private function hueco(int $nivelIdx, ?int $indicadorIdx, string $campo, string $severidad, string $mensaje): array
    {
        return [
            'nivel_idx' => $nivelIdx,
            'indicador_idx' => $indicadorIdx,
            'campo' => $campo,
            'severidad' => $severidad,
            'mensaje' => $mensaje,
        ];
    }

    private function esValorEnumValido(string $campo, string $valor): bool
    {
        $validos = match ($campo) {
            'tipo' => ['estrategico', 'estratégico', 'gestion', 'gestión'],
            'dimension' => ['eficacia', 'eficiencia', 'calidad', 'economia', 'economía'],
            'frecuencia' => ['mensual', 'trimestral', 'semestral', 'anual', 'bianual', 'sexenal'],
            'sentido' => ['ascendente', 'descendente', 'regular'],
            default => [],
        };
        return in_array(mb_strtolower($valor), $validos);
    }
}
```

### Step 9: Write test fixtures

Create `tests/fixtures/mir-sample.md`:

```markdown
# Programa: Becas para el Bienestar (E001)
## Ejercicio: 2026
| Nivel | Resumen Narrativo | Indicador | Fórmula | Tipo | Dimensión | Frecuencia | Medio Verificación | Supuestos |
|-------|-------------------|-----------|---------|------|-----------|------------|-------------------|-----------|
| Fin | Contribuir a reducir la desigualdad educativa | Tasa de cobertura | (A/B) x 100 | Estratégico | Eficacia | Anual | Padrón de beneficiarios | Estabilidad presupuestal |
| Propósito | Estudiantes de bajos recursos acceden a educación superior | Porcentaje de retención | (A/B) x 100 | Estratégico | Eficacia | Semestral | Registro escolar | Demanda sostenida |
| Componente | Becas entregadas oportunamente | Porcentaje de becas entregadas | (A/B) x 100 | Gestión | Eficiencia | Trimestral | Comprobantes de pago | Recursos disponibles |
| Actividad | Registro de beneficiarios | Porcentaje de registros completados | (A/B) x 100 | Gestión | Eficacia | Mensual | Base de datos | Conectividad estable |
```

Create `tests/fixtures/mir-sample.csv`:

```csv
Nivel,Resumen Narrativo,Indicador,Fórmula,Tipo,Dimensión,Frecuencia,Medio Verificación,Supuestos
Fin,Contribuir a reducir la desigualdad,Tasa de cobertura,(A/B) x 100,Estratégico,Eficacia,Anual,Padrón de beneficiarios,Estabilidad presupuestal
Propósito,Estudiantes acceden a educación,Porcentaje retención,(A/B) x 100,Estratégico,Eficacia,Semestral,Registro escolar,
Componente,Becas entregadas,Porcentaje de becas,,,,Trimestral,Comprobantes,Recursos disponibles
Actividad,Registro de beneficiarios,,,,,,,
```

### Step 10: Write `MirParserTest`

Create `tests/Feature/Mml/MirParserTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Services\Mml\MirParserService;
use Tests\TestCase;

class MirParserTest extends TestCase
{
    private MirParserService $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MirParserService();
    }

    public function test_parsea_markdown_extrae_nombre_y_clave(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $data = $this->parser->fromMarkdown($content);

        $this->assertEquals('Becas para el Bienestar', $data->nombre);
        $this->assertEquals('E001', $data->clave);
        $this->assertEquals(2026, $data->ejercicioFiscal);
    }

    public function test_parsea_markdown_extrae_4_niveles(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $data = $this->parser->fromMarkdown($content);

        $this->assertCount(4, $data->niveles);
        $tipos = array_column($data->niveles, 'tipo_nivel');
        $this->assertEquals(['fin', 'proposito', 'componente', 'actividad'], $tipos);
    }

    public function test_parsea_markdown_extrae_indicadores(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $data = $this->parser->fromMarkdown($content);

        $fin = $data->niveles[0];
        $this->assertCount(1, $fin['indicadores']);
        $this->assertEquals('Tasa de cobertura', $fin['indicadores'][0]['nombre']);
        $this->assertEquals('(A/B) x 100', $fin['indicadores'][0]['formula_texto']);
    }

    public function test_parsea_markdown_actividad_apunta_a_componente(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $data = $this->parser->fromMarkdown($content);

        $actividad = $data->niveles[3];
        $this->assertEquals(2, $actividad['componente_idx']); // index of componente
    }

    public function test_parsea_markdown_extrae_medios(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $data = $this->parser->fromMarkdown($content);

        $fin = $data->niveles[0];
        $this->assertCount(1, $fin['indicadores'][0]['medios']);
        $this->assertEquals('Padrón de beneficiarios', $fin['indicadores'][0]['medios'][0]['nombre']);
    }

    public function test_parsea_csv_extrae_niveles(): void
    {
        $data = $this->parser->fromCsv(base_path('tests/fixtures/mir-sample.csv'));

        $this->assertCount(4, $data->niveles);
        $this->assertEquals('fin', $data->niveles[0]['tipo_nivel']);
    }

    public function test_parsea_csv_con_campos_vacios(): void
    {
        $data = $this->parser->fromCsv(base_path('tests/fixtures/mir-sample.csv'));

        // Actividad row has mostly empty fields
        $actividad = $data->niveles[3];
        $this->assertEmpty($actividad['indicadores']);
    }

    public function test_to_array_serializa_correctamente(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $data = $this->parser->fromMarkdown($content);
        $arr = $data->toArray();

        $this->assertArrayHasKey('nombre', $arr);
        $this->assertArrayHasKey('niveles', $arr);
        $this->assertCount(4, $arr['niveles']);
    }
}
```

### Step 11: Write `MirDiagnosticoTest`

Create `tests/Feature/Mml/MirDiagnosticoTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Services\Mml\MirDiagnosticoService;
use Tests\TestCase;

class MirDiagnosticoTest extends TestCase
{
    private MirDiagnosticoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MirDiagnosticoService();
    }

    public function test_detecta_indicador_sin_formula_como_critico(): void
    {
        $data = new ImportedMirData(niveles: [[
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'Test', 'supuestos' => null,
            'orden' => 1, 'componente_idx' => null,
            'indicadores' => [[
                'nombre' => 'Ind', 'formula_texto' => null,
                'tipo' => 'estrategico', 'dimension' => 'eficacia', 'frecuencia' => 'anual',
                'sentido' => null, 'linea_base' => null, 'meta' => null,
                'rangos_semaforo' => null, 'variables' => [], 'medios' => [['nombre' => 'M', 'fuente' => null]],
            ]],
        ]]);

        $huecos = $this->service->diagnosticar($data);
        $criticos = array_filter($huecos, fn ($h) => $h['severidad'] === 'critico' && $h['campo'] === 'formula_texto');
        $this->assertNotEmpty($criticos);
    }

    public function test_detecta_nivel_sin_indicadores_como_critico(): void
    {
        $data = new ImportedMirData(niveles: [[
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'Test', 'supuestos' => 'S',
            'orden' => 1, 'componente_idx' => null, 'indicadores' => [],
        ]]);

        $huecos = $this->service->diagnosticar($data);
        $criticos = array_filter($huecos, fn ($h) => $h['campo'] === 'indicadores');
        $this->assertNotEmpty($criticos);
    }

    public function test_detecta_meta_faltante_como_menor(): void
    {
        $data = new ImportedMirData(niveles: [[
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'Test', 'supuestos' => 'S',
            'orden' => 1, 'componente_idx' => null,
            'indicadores' => [[
                'nombre' => 'Ind', 'formula_texto' => 'A/B',
                'tipo' => 'estrategico', 'dimension' => 'eficacia', 'frecuencia' => 'anual',
                'sentido' => null, 'linea_base' => null, 'meta' => null,
                'rangos_semaforo' => null, 'variables' => [], 'medios' => [['nombre' => 'M', 'fuente' => null]],
            ]],
        ]]);

        $huecos = $this->service->diagnosticar($data);
        $menores = array_filter($huecos, fn ($h) => $h['severidad'] === 'menor' && $h['campo'] === 'meta');
        $this->assertNotEmpty($menores);
    }

    public function test_detecta_valor_enum_no_reconocido_como_advertencia(): void
    {
        $data = new ImportedMirData(niveles: [[
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'Test', 'supuestos' => 'S',
            'orden' => 1, 'componente_idx' => null,
            'indicadores' => [[
                'nombre' => 'Ind', 'formula_texto' => 'A/B',
                'tipo' => 'invalido_xyz', 'dimension' => 'eficacia', 'frecuencia' => 'anual',
                'sentido' => null, 'linea_base' => null, 'meta' => null,
                'rangos_semaforo' => null, 'variables' => [], 'medios' => [['nombre' => 'M', 'fuente' => null]],
            ]],
        ]]);

        $huecos = $this->service->diagnosticar($data);
        $advertencias = array_filter($huecos, fn ($h) => $h['severidad'] === 'advertencia');
        $this->assertNotEmpty($advertencias);
    }

    public function test_conteo_agrupa_por_severidad(): void
    {
        $data = new ImportedMirData(niveles: [[
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'Test', 'supuestos' => null,
            'orden' => 1, 'componente_idx' => null,
            'indicadores' => [[
                'nombre' => 'Ind', 'formula_texto' => null,
                'tipo' => null, 'dimension' => null, 'frecuencia' => null,
                'sentido' => null, 'linea_base' => null, 'meta' => null,
                'rangos_semaforo' => null, 'variables' => [], 'medios' => [],
            ]],
        ]]);

        $huecos = $this->service->diagnosticar($data);
        $conteo = $this->service->conteo($huecos);

        $this->assertGreaterThan(0, $conteo['critico']);
        $this->assertGreaterThan(0, $conteo['menor']);
        $this->assertArrayHasKey('advertencia', $conteo);
    }

    public function test_mir_completa_no_tiene_criticos(): void
    {
        $data = new ImportedMirData(niveles: [[
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'Test', 'supuestos' => 'Supuesto',
            'orden' => 1, 'componente_idx' => null,
            'indicadores' => [[
                'nombre' => 'Ind', 'formula_texto' => 'A/B',
                'tipo' => 'estrategico', 'dimension' => 'eficacia', 'frecuencia' => 'anual',
                'sentido' => 'ascendente', 'linea_base' => 10.0, 'meta' => 90.0,
                'rangos_semaforo' => ['verde_min' => 80, 'verde_max' => 100],
                'variables' => [['simbolo' => 'A', 'nombre' => 'Var']],
                'medios' => [['nombre' => 'Medio', 'fuente' => 'Fuente']],
            ]],
        ]]);

        $huecos = $this->service->diagnosticar($data);
        $criticos = array_filter($huecos, fn ($h) => $h['severidad'] === 'critico');
        $this->assertEmpty($criticos);
    }
}
```

### Step 12: Run tests to verify

```bash
./vendor/bin/sail artisan test --filter=MirParser
./vendor/bin/sail artisan test --filter=MirDiagnostico
```

### Step 13: Write Livewire component `ImportarPrograma`

Create `app/Livewire/Mml/ImportarPrograma.php`:

```php
<?php

namespace App\Livewire\Mml;

use App\DTOs\ImportedMirData;
use App\Models\Mml\ImportacionReporte;
use App\Services\Mml\MirDiagnosticoService;
use App\Services\Mml\MirParserService;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportarPrograma extends Component
{
    use WithFileUploads;

    public $archivo;
    public ?array $datosParseados = null;
    public ?array $diagnostico = null;
    public ?array $conteo = null;
    public ?int $reporteId = null;
    public ?string $errorFormato = null;

    protected $rules = [
        'archivo' => 'required|file|max:5120|mimes:md,txt,csv,xlsx,xls',
    ];

    public function updatedArchivo(): void
    {
        $this->validate();
        $this->errorFormato = null;
        $this->procesarArchivo();
    }

    public function procesarArchivo(): void
    {
        $parser = app(MirParserService::class);
        $diagnosticoService = app(MirDiagnosticoService::class);

        $extension = $this->archivo->getClientOriginalExtension();
        $formato = match (mb_strtolower($extension)) {
            'md', 'txt' => 'md',
            'csv' => 'csv',
            'xlsx', 'xls' => 'xlsx',
            default => null,
        };

        if (! $formato) {
            $this->errorFormato = 'Formato de archivo no soportado.';
            return;
        }

        try {
            $data = match ($formato) {
                'md' => $parser->fromMarkdown($this->archivo->get()),
                'csv' => $parser->fromCsv($this->archivo->getRealPath()),
                'xlsx' => $parser->fromExcel($this->archivo->getRealPath()),
            };
        } catch (\Throwable $e) {
            $this->errorFormato = 'Error al procesar el archivo: ' . $e->getMessage();
            return;
        }

        if (empty($data->niveles)) {
            $this->errorFormato = 'No se encontraron niveles MIR en el archivo.';
            return;
        }

        $this->datosParseados = $data->toArray();
        $this->diagnostico = $diagnosticoService->diagnosticar($data);
        $this->conteo = $diagnosticoService->conteo($this->diagnostico);

        // Persist report
        $reporte = ImportacionReporte::create([
            'team_id' => auth()->user()->currentTeam->id,
            'archivo_original' => $this->archivo->getClientOriginalName(),
            'formato' => $formato,
            'datos_parseados' => $this->datosParseados,
            'diagnostico' => $this->diagnostico,
            'estado' => 'pendiente',
            'created_by' => auth()->id(),
        ]);

        $this->reporteId = $reporte->id;
    }

    public function continuar(): void
    {
        if (! $this->reporteId) return;

        return $this->redirect(
            route('mml.importar.completar', ['reporte' => $this->reporteId]),
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.mml.importar-programa');
    }
}
```

### Step 14: Write view `importar-programa.blade.php`

Create `resources/views/livewire/mml/importar-programa.blade.php`:

```blade
<div>
    <x-page.header titulo="Importar Programa">
        <x-slot:actions>
            <a href="{{ url()->previous() }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver</a>
        </x-slot:actions>
    </x-page.header>

    <x-page.container>
        {{-- Upload Section --}}
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-medium mb-4">Subir archivo MIR</h3>
            <p class="text-sm text-gray-500 mb-4">Formatos aceptados: Markdown (.md), CSV (.csv), Excel (.xlsx). Máximo 5MB.</p>

            <div class="flex items-center gap-4">
                <input type="file" wire:model="archivo"
                    accept=".md,.txt,.csv,.xlsx,.xls"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />

                <div wire:loading wire:target="archivo" class="text-sm text-gray-500">
                    Procesando...
                </div>
            </div>

            @error('archivo')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if($errorFormato)
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded">
                    <p class="text-sm text-red-700">{{ $errorFormato }}</p>
                </div>
            @endif
        </div>

        {{-- Results --}}
        @if($datosParseados)
            {{-- Summary --}}
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-medium mb-4">Estructura importada</h3>

                @if($datosParseados['nombre'])
                    <p class="text-sm mb-2"><strong>Programa:</strong> {{ $datosParseados['nombre'] }}
                        @if($datosParseados['clave']) ({{ $datosParseados['clave'] }}) @endif
                    </p>
                @endif

                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Nivel</th>
                            <th class="text-left py-2">Resumen Narrativo</th>
                            <th class="text-center py-2">Indicadores</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datosParseados['niveles'] as $nivel)
                            <tr class="border-b">
                                <td class="py-2 font-medium">{{ ucfirst($nivel['tipo_nivel']) }}</td>
                                <td class="py-2">{{ \Illuminate\Support\Str::limit($nivel['resumen_narrativo'] ?? '—', 80) }}</td>
                                <td class="py-2 text-center">{{ count($nivel['indicadores'] ?? []) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Diagnosis --}}
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-medium mb-4">Diagnóstico de completitud</h3>

                <div class="flex gap-4 mb-4">
                    <span class="px-3 py-1 rounded text-sm font-medium bg-red-100 text-red-800">
                        {{ $conteo['critico'] }} críticos
                    </span>
                    <span class="px-3 py-1 rounded text-sm font-medium bg-yellow-100 text-yellow-800">
                        {{ $conteo['menor'] }} menores
                    </span>
                    <span class="px-3 py-1 rounded text-sm font-medium bg-blue-100 text-blue-800">
                        {{ $conteo['advertencia'] }} advertencias
                    </span>
                </div>

                @if(count($diagnostico) > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($diagnostico as $hueco)
                            <div class="flex items-start gap-2 text-sm p-2 rounded {{ match($hueco['severidad']) {
                                'critico' => 'bg-red-50',
                                'menor' => 'bg-yellow-50',
                                'advertencia' => 'bg-blue-50',
                            } }}">
                                <span class="font-medium {{ match($hueco['severidad']) {
                                    'critico' => 'text-red-700',
                                    'menor' => 'text-yellow-700',
                                    'advertencia' => 'text-blue-700',
                                } }}">
                                    {{ match($hueco['severidad']) { 'critico' => 'CRITICO', 'menor' => 'MENOR', 'advertencia' => 'AVISO' } }}
                                </span>
                                <span class="text-gray-700">{{ $hueco['mensaje'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-green-700">Sin huecos detectados. La MIR está completa.</p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <button wire:click="$set('datosParseados', null)" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                    Cancelar
                </button>
                <button wire:click="continuar"
                    @if($errorFormato) disabled @endif
                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                    Continuar →
                </button>
            </div>
        @endif
    </x-page.container>
</div>
```

### Step 15: Add route

Modify `routes/web/mml.php` — add inside the `{programa}` prefix group:

```php
// After existing etapa routes:
Route::get('/importar', \App\Livewire\Mml\ImportarPrograma::class)->name('mml.importar');
```

Also add a standalone import route outside `{programa}` for new imports:

```php
// Outside the {programa} prefix:
Route::get('/importar', \App\Livewire\Mml\ImportarPrograma::class)->name('mml.importar.nuevo');
```

### Step 16: Write Livewire component test

Create `tests/Feature/Mml/ImportarProgramaTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Livewire\Mml\ImportarPrograma;
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

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(ImportarPrograma::class)
            ->assertStatus(200);
    }

    public function test_sube_md_y_genera_diagnostico(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $file = UploadedFile::fake()->createWithContent('test.md', $content);

        Livewire::actingAs($this->user)
            ->test(ImportarPrograma::class)
            ->set('archivo', $file)
            ->assertSet('errorFormato', null)
            ->assertNotNull(fn ($c) => $c->datosParseados);
    }

    public function test_crea_reporte_al_subir(): void
    {
        $content = file_get_contents(base_path('tests/fixtures/mir-sample.md'));
        $file = UploadedFile::fake()->createWithContent('test.md', $content);

        Livewire::actingAs($this->user)
            ->test(ImportarPrograma::class)
            ->set('archivo', $file);

        $this->assertDatabaseHas('importacion_reportes', [
            'archivo_original' => 'test.md',
            'formato' => 'md',
            'estado' => 'pendiente',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_rechaza_archivo_vacio(): void
    {
        $file = UploadedFile::fake()->createWithContent('empty.md', '');

        Livewire::actingAs($this->user)
            ->test(ImportarPrograma::class)
            ->set('archivo', $file)
            ->assertNotNull(fn ($c) => $c->errorFormato);
    }
}
```

### Step 17: Run all tests

```bash
./vendor/bin/sail artisan test
```
Expected: all pass, zero regressions.

### Step 18: Commit and merge

```bash
git add -A
git commit -m "feat(S5-T1): implement multi-format importer with diagnosis

- DTO ImportedMirData for parser abstraction
- MirParserService: fromMarkdown, fromCsv, fromExcel
- MirDiagnosticoService: gap classification (critico/menor/advertencia)
- ImportacionReporte model with JSONB fields
- ImportarPrograma Livewire component with upload + preview
- 18 tests covering parsers, diagnosis, and component

Resolves DTE-XX"
```

---

## Task 2: Persistencia y Flujo de Completitud (S5-T2)

**Branch:** `feat/S5-T2-flujo-completitud`

**Files:**
- Create: `database/migrations/2026_03_09_020000_add_activo_seguimiento_to_indicadores.php`
- Create: `app/Services/Mml/MirPersistenciaService.php`
- Create: `app/Livewire/Mml/CompletarHuecos.php`
- Create: `resources/views/livewire/mml/completar-huecos.blade.php`
- Create: `tests/Feature/Mml/MirPersistenciaTest.php`
- Create: `tests/Feature/Mml/CompletarHuecosTest.php`
- Modify: `app/Models/Mml/Indicador.php` (add `activo_seguimiento` to fillable/casts)
- Modify: `routes/web/mml.php` (add completar route)

### Step 1: Create branch

```bash
git checkout desarrollo && git pull
git checkout -b feat/S5-T2-flujo-completitud
```

### Step 2: Write migration `add_activo_seguimiento_to_indicadores`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            $table->boolean('activo_seguimiento')->default(true)->after('orden');
        });
    }

    public function down(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            $table->dropColumn('activo_seguimiento');
        });
    }
};
```

### Step 3: Update `Indicador` model

Add `'activo_seguimiento'` to `$fillable` array. Add `'activo_seguimiento' => 'boolean'` to `casts()`.

### Step 4: Write `MirPersistenciaService`

Create `app/Services/Mml/MirPersistenciaService.php`:

```php
<?php

namespace App\Services\Mml;

use App\DTOs\ImportedMirData;
use App\Enums\DimensionIndicador;
use App\Enums\EstadoPrograma;
use App\Enums\FrecuenciaMedicion;
use App\Enums\OrigenPrograma;
use App\Enums\SentidoIndicador;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Facades\DB;

class MirPersistenciaService
{
    /**
     * Persist imported MIR data as a new ProgramaPresupuestario.
     */
    public function persistir(ImportedMirData $data, int $teamId, int $userId, ?array $diagnostico = null): ProgramaPresupuestario
    {
        return DB::transaction(function () use ($data, $teamId, $userId, $diagnostico) {
            $programa = ProgramaPresupuestario::create([
                'nombre' => $data->nombre ?? 'Programa importado',
                'clave' => $data->clave ?? 'IMP-' . now()->format('ymdHis'),
                'team_id' => $teamId,
                'ejercicio_fiscal' => $data->ejercicioFiscal ?? now()->year,
                'origen' => OrigenPrograma::IMPORTADO->value,
                'estado' => EstadoPrograma::BORRADOR->value,
                'created_by' => $userId,
            ]);

            $this->crearNiveles($programa, $data->niveles, $diagnostico);

            return $programa;
        });
    }

    private function crearNiveles(ProgramaPresupuestario $programa, array $niveles, ?array $diagnostico): void
    {
        $componenteIds = []; // map componente_idx => DB id

        foreach ($niveles as $idx => $nivelData) {
            $componenteId = null;
            if ($nivelData['tipo_nivel'] === 'actividad' && $nivelData['componente_idx'] !== null) {
                $componenteId = $componenteIds[$nivelData['componente_idx']] ?? null;
            }

            $nivel = MirNivel::create([
                'programa_presupuestario_id' => $programa->id,
                'tipo_nivel' => $nivelData['tipo_nivel'],
                'componente_id' => $componenteId,
                'resumen_narrativo' => $nivelData['resumen_narrativo'],
                'supuestos' => $nivelData['supuestos'] ?? null,
                'orden' => $nivelData['orden'],
            ]);

            if ($nivelData['tipo_nivel'] === 'componente') {
                $componenteIds[$idx] = $nivel->id;
            }

            foreach ($nivelData['indicadores'] ?? [] as $iIdx => $indData) {
                $tieneCritico = $this->indicadorTieneCritico($idx, $iIdx, $diagnostico);

                $indicador = Indicador::create([
                    'mir_nivel_id' => $nivel->id,
                    'nombre' => $indData['nombre'] ?? 'Sin nombre',
                    'formula_texto' => $indData['formula_texto'] ?? null,
                    'tipo' => $this->mapearEnum(TipoIndicador::class, $indData['tipo'] ?? null)?->value ?? TipoIndicador::ESTRATEGICO->value,
                    'dimension' => $this->mapearEnum(DimensionIndicador::class, $indData['dimension'] ?? null)?->value ?? DimensionIndicador::EFICACIA->value,
                    'frecuencia' => $this->mapearEnum(FrecuenciaMedicion::class, $indData['frecuencia'] ?? null)?->value ?? FrecuenciaMedicion::ANUAL->value,
                    'sentido' => $this->mapearEnum(SentidoIndicador::class, $indData['sentido'] ?? null)?->value,
                    'linea_base' => $indData['linea_base'] ?? null,
                    'meta' => $indData['meta'] ?? null,
                    'orden' => $iIdx + 1,
                    'activo_seguimiento' => ! $tieneCritico,
                ]);

                foreach ($indData['variables'] ?? [] as $vIdx => $varData) {
                    IndicadorVariable::create([
                        'indicador_id' => $indicador->id,
                        'simbolo' => $varData['simbolo'] ?? chr(65 + $vIdx),
                        'nombre' => $varData['nombre'],
                        'orden' => $vIdx + 1,
                    ]);
                }

                foreach ($indData['medios'] ?? [] as $mIdx => $medioData) {
                    MedioVerificacion::create([
                        'indicador_id' => $indicador->id,
                        'nombre' => $medioData['nombre'],
                        'fuente' => $medioData['fuente'] ?? null,
                        'orden' => $mIdx + 1,
                    ]);
                }
            }
        }
    }

    private function indicadorTieneCritico(int $nivelIdx, int $indicadorIdx, ?array $diagnostico): bool
    {
        if (! $diagnostico) return false;

        foreach ($diagnostico as $hueco) {
            if ($hueco['nivel_idx'] === $nivelIdx
                && $hueco['indicador_idx'] === $indicadorIdx
                && $hueco['severidad'] === 'critico') {
                return true;
            }
        }
        return false;
    }

    private function mapearEnum(string $enumClass, ?string $valor): ?object
    {
        if (! $valor) return null;

        $normalizado = mb_strtolower(trim($valor));
        // Handle accented variants
        $normalizado = str_replace(
            ['estratégico', 'gestión', 'economía', 'dimensión'],
            ['estrategico', 'gestion', 'economia', 'dimension'],
            $normalizado
        );

        return $enumClass::tryFrom($normalizado);
    }
}
```

### Step 5: Write `MirPersistenciaTest`

Create `tests/Feature/Mml/MirPersistenciaTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Enums\OrigenPrograma;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\User;
use App\Services\Mml\MirDiagnosticoService;
use App\Services\Mml\MirPersistenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MirPersistenciaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private MirPersistenciaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->service = new MirPersistenciaService();
    }

    public function test_crea_programa_importado(): void
    {
        $data = $this->buildData();
        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id);

        $this->assertEquals('Test', $programa->nombre);
        $this->assertEquals(OrigenPrograma::IMPORTADO, $programa->origen);
    }

    public function test_crea_niveles_con_jerarquia(): void
    {
        $data = $this->buildData();
        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id);

        $this->assertEquals(4, $programa->mirNiveles()->count());
        $actividad = $programa->mirNiveles()->where('tipo_nivel', 'actividad')->first();
        $this->assertNotNull($actividad->componente_id);
    }

    public function test_crea_indicadores_y_medios(): void
    {
        $data = $this->buildData();
        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id);

        $fin = $programa->mirNiveles()->where('tipo_nivel', 'fin')->first();
        $this->assertEquals(1, $fin->indicadores()->count());
        $this->assertEquals(1, $fin->indicadores()->first()->mediosVerificacion()->count());
    }

    public function test_indicador_con_criticos_desactiva_seguimiento(): void
    {
        $data = new ImportedMirData(nombre: 'T', clave: 'T-01', niveles: [[
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'X', 'supuestos' => null,
            'orden' => 1, 'componente_idx' => null,
            'indicadores' => [[
                'nombre' => 'Ind', 'formula_texto' => null,
                'tipo' => 'estrategico', 'dimension' => 'eficacia', 'frecuencia' => 'anual',
                'sentido' => null, 'linea_base' => null, 'meta' => null,
                'rangos_semaforo' => null, 'variables' => [], 'medios' => [],
            ]],
        ]]);

        $diagnostico = (new MirDiagnosticoService())->diagnosticar($data);
        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id, $diagnostico);

        $indicador = $programa->mirNiveles()->first()->indicadores()->first();
        $this->assertFalse($indicador->activo_seguimiento);
    }

    public function test_mapea_enums_con_acentos(): void
    {
        $data = new ImportedMirData(nombre: 'T', clave: 'T-01', niveles: [[
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'X', 'supuestos' => null,
            'orden' => 1, 'componente_idx' => null,
            'indicadores' => [[
                'nombre' => 'Ind', 'formula_texto' => 'A/B',
                'tipo' => 'Estratégico', 'dimension' => 'Eficacia', 'frecuencia' => 'Anual',
                'sentido' => null, 'linea_base' => null, 'meta' => null,
                'rangos_semaforo' => null, 'variables' => [], 'medios' => [['nombre' => 'M', 'fuente' => null]],
            ]],
        ]]);

        $programa = $this->service->persistir($data, $this->user->currentTeam->id, $this->user->id);
        $indicador = $programa->mirNiveles()->first()->indicadores()->first();

        $this->assertEquals('estrategico', $indicador->getRawOriginal('tipo'));
        $this->assertEquals('eficacia', $indicador->getRawOriginal('dimension'));
    }

    private function buildData(): ImportedMirData
    {
        return new ImportedMirData(
            nombre: 'Test', clave: 'PT-001', ejercicioFiscal: 2026,
            niveles: [
                ['tipo_nivel' => 'fin', 'resumen_narrativo' => 'Fin', 'supuestos' => 'S', 'orden' => 1, 'componente_idx' => null,
                    'indicadores' => [['nombre' => 'Ind', 'formula_texto' => 'A/B', 'tipo' => 'estrategico', 'dimension' => 'eficacia', 'frecuencia' => 'anual', 'sentido' => null, 'linea_base' => null, 'meta' => null, 'rangos_semaforo' => null, 'variables' => [], 'medios' => [['nombre' => 'M', 'fuente' => null]]]]],
                ['tipo_nivel' => 'proposito', 'resumen_narrativo' => 'Prop', 'supuestos' => null, 'orden' => 1, 'componente_idx' => null, 'indicadores' => []],
                ['tipo_nivel' => 'componente', 'resumen_narrativo' => 'Comp', 'supuestos' => null, 'orden' => 1, 'componente_idx' => null, 'indicadores' => []],
                ['tipo_nivel' => 'actividad', 'resumen_narrativo' => 'Act', 'supuestos' => null, 'orden' => 1, 'componente_idx' => 2, 'indicadores' => []],
            ],
        );
    }
}
```

### Step 6: Write `CompletarHuecos` component

Create `app/Livewire/Mml/CompletarHuecos.php`:

```php
<?php

namespace App\Livewire\Mml;

use App\Models\Mml\ImportacionReporte;
use App\Models\Mml\Indicador;
use App\Services\Mml\MirDiagnosticoService;
use App\Services\Mml\MirPersistenciaService;
use Livewire\Component;

class CompletarHuecos extends Component
{
    public ImportacionReporte $reporte;
    public ?int $programaId = null;
    public array $diagnostico = [];
    public array $conteo = [];
    public bool $persistido = false;

    public function mount(ImportacionReporte $reporte): void
    {
        $this->reporte = $reporte;
        $this->diagnostico = $reporte->diagnostico ?? [];
        $this->conteo = (new MirDiagnosticoService())->conteo($this->diagnostico);

        // Persist if not yet done
        if (! $reporte->programa_presupuestario_id) {
            $this->persistirPrograma();
        } else {
            $this->programaId = $reporte->programa_presupuestario_id;
            $this->persistido = true;
        }
    }

    private function persistirPrograma(): void
    {
        $data = \App\DTOs\ImportedMirData::fromArray($this->reporte->datos_parseados);
        $service = app(MirPersistenciaService::class);
        $programa = $service->persistir(
            $data,
            $this->reporte->team_id,
            $this->reporte->created_by,
            $this->diagnostico,
        );

        $this->reporte->update(['programa_presupuestario_id' => $programa->id]);
        $this->programaId = $programa->id;
        $this->persistido = true;
    }

    public function corregirCampo(int $indicadorId, string $campo, $valor): void
    {
        $indicador = Indicador::findOrFail($indicadorId);

        $fillable = ['formula_texto', 'tipo', 'dimension', 'frecuencia', 'sentido', 'linea_base', 'meta'];
        if (! in_array($campo, $fillable)) return;

        $indicador->update([$campo => $valor]);

        // Re-check if all critical gaps resolved for this indicator
        $this->actualizarSeguimiento($indicador);
    }

    private function actualizarSeguimiento(Indicador $indicador): void
    {
        $camposCriticos = ['tipo', 'dimension', 'frecuencia', 'formula_texto'];
        $tieneHueco = false;

        foreach ($camposCriticos as $campo) {
            if (empty($indicador->$campo)) {
                $tieneHueco = true;
                break;
            }
        }

        // Also check medios
        if (! $tieneHueco && $indicador->mediosVerificacion()->count() === 0) {
            $tieneHueco = true;
        }

        $indicador->update(['activo_seguimiento' => ! $tieneHueco]);
    }

    public function finalizar(): void
    {
        $this->reporte->update(['estado' => 'procesado']);

        return $this->redirect(
            route('mml.importar.vincular', ['reporte' => $this->reporte->id]),
            navigate: true
        );
    }

    public function progreso(): float
    {
        if (! $this->programaId) return 0;

        $total = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->programaId))->count();
        if ($total === 0) return 100;

        $activos = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->programaId))
            ->where('activo_seguimiento', true)->count();

        return round(($activos / $total) * 100, 1);
    }

    public function render()
    {
        $indicadores = $this->programaId
            ? Indicador::with(['mirNivel', 'mediosVerificacion', 'variables'])
                ->whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->programaId))
                ->orderBy('activo_seguimiento') // inactive first
                ->get()
            : collect();

        return view('livewire.mml.completar-huecos', [
            'indicadores' => $indicadores,
            'progreso' => $this->progreso(),
        ]);
    }
}
```

### Step 7: Write view `completar-huecos.blade.php`

Create `resources/views/livewire/mml/completar-huecos.blade.php`:

```blade
<div>
    <x-page.header titulo="Completar Huecos">
        <x-slot:actions>
            <span class="text-sm text-gray-500">Progreso: {{ $progreso }}%</span>
        </x-slot:actions>
    </x-page.header>

    <x-page.container>
        {{-- Progress bar --}}
        <div class="mb-6">
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full transition-all" style="width: {{ $progreso }}%"></div>
            </div>
        </div>

        {{-- Indicators --}}
        <div class="space-y-4">
            @foreach($indicadores as $indicador)
                <div class="bg-white rounded-lg shadow p-4 {{ $indicador->activo_seguimiento ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500' }}">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-medium text-sm">
                            {{ ucfirst($indicador->mirNivel->tipo_nivel) }} → {{ $indicador->nombre }}
                        </h4>
                        <span class="text-xs px-2 py-1 rounded {{ $indicador->activo_seguimiento ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $indicador->activo_seguimiento ? 'Activo' : 'Incompleto' }}
                        </span>
                    </div>

                    @if(! $indicador->activo_seguimiento)
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            @if(empty($indicador->formula_texto))
                                <div>
                                    <label class="block text-xs text-gray-500">Fórmula</label>
                                    <input type="text" wire:change="corregirCampo({{ $indicador->id }}, 'formula_texto', $event.target.value)"
                                        class="mt-1 block w-full rounded border-gray-300 text-sm" placeholder="Ej: (A/B) x 100" />
                                </div>
                            @endif
                            @if(empty($indicador->getRawOriginal('tipo')))
                                <div>
                                    <label class="block text-xs text-gray-500">Tipo</label>
                                    <select wire:change="corregirCampo({{ $indicador->id }}, 'tipo', $event.target.value)"
                                        class="mt-1 block w-full rounded border-gray-300 text-sm">
                                        <option value="">Seleccionar</option>
                                        @foreach(\App\Enums\TipoIndicador::cases() as $t)
                                            <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if(empty($indicador->getRawOriginal('dimension')))
                                <div>
                                    <label class="block text-xs text-gray-500">Dimensión</label>
                                    <select wire:change="corregirCampo({{ $indicador->id }}, 'dimension', $event.target.value)"
                                        class="mt-1 block w-full rounded border-gray-300 text-sm">
                                        <option value="">Seleccionar</option>
                                        @foreach(\App\Enums\DimensionIndicador::cases() as $d)
                                            <option value="{{ $d->value }}">{{ $d->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if(empty($indicador->getRawOriginal('frecuencia')))
                                <div>
                                    <label class="block text-xs text-gray-500">Frecuencia</label>
                                    <select wire:change="corregirCampo({{ $indicador->id }}, 'frecuencia', $event.target.value)"
                                        class="mt-1 block w-full rounded border-gray-300 text-sm">
                                        <option value="">Seleccionar</option>
                                        @foreach(\App\Enums\FrecuenciaMedicion::cases() as $f)
                                            <option value="{{ $f->value }}">{{ $f->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Actions --}}
        <div class="flex justify-end mt-6">
            <button wire:click="finalizar"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                Continuar a Vinculación →
            </button>
        </div>
    </x-page.container>
</div>
```

### Step 8: Add route for completar

In `routes/web/mml.php`:

```php
Route::get('/importar/{reporte}/completar', \App\Livewire\Mml\CompletarHuecos::class)->name('mml.importar.completar');
```

### Step 9: Write `CompletarHuecosTest`

Create `tests/Feature/Mml/CompletarHuecosTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Livewire\Mml\CompletarHuecos;
use App\Models\Mml\ImportacionReporte;
use App\Models\User;
use App\Services\Mml\MirDiagnosticoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompletarHuecosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    public function test_persiste_programa_al_montar(): void
    {
        $reporte = $this->crearReporte();

        Livewire::actingAs($this->user)
            ->test(CompletarHuecos::class, ['reporte' => $reporte]);

        $reporte->refresh();
        $this->assertNotNull($reporte->programa_presupuestario_id);
    }

    public function test_corregir_campo_actualiza_indicador(): void
    {
        $reporte = $this->crearReporte();

        $component = Livewire::actingAs($this->user)
            ->test(CompletarHuecos::class, ['reporte' => $reporte]);

        $reporte->refresh();
        $indicador = $reporte->programa->mirNiveles()->first()->indicadores()->first();

        $component->call('corregirCampo', $indicador->id, 'formula_texto', 'A/B');

        $indicador->refresh();
        $this->assertEquals('A/B', $indicador->formula_texto);
    }

    public function test_finalizar_cambia_estado_reporte(): void
    {
        $reporte = $this->crearReporte();

        Livewire::actingAs($this->user)
            ->test(CompletarHuecos::class, ['reporte' => $reporte])
            ->call('finalizar');

        $reporte->refresh();
        $this->assertEquals('procesado', $reporte->estado);
    }

    private function crearReporte(): ImportacionReporte
    {
        $data = new ImportedMirData(nombre: 'Test', clave: 'T-01', niveles: [[
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'Fin', 'supuestos' => null,
            'orden' => 1, 'componente_idx' => null,
            'indicadores' => [[
                'nombre' => 'Ind', 'formula_texto' => null,
                'tipo' => 'estrategico', 'dimension' => 'eficacia', 'frecuencia' => 'anual',
                'sentido' => null, 'linea_base' => null, 'meta' => null,
                'rangos_semaforo' => null, 'variables' => [], 'medios' => [['nombre' => 'M', 'fuente' => null]],
            ]],
        ]]);

        $diagnostico = (new MirDiagnosticoService())->diagnosticar($data);

        return ImportacionReporte::create([
            'team_id' => $this->user->currentTeam->id,
            'archivo_original' => 'test.md',
            'formato' => 'md',
            'datos_parseados' => $data->toArray(),
            'diagnostico' => $diagnostico,
            'estado' => 'pendiente',
            'created_by' => $this->user->id,
        ]);
    }
}
```

### Step 10: Run tests and commit

```bash
./vendor/bin/sail artisan test
git add -A
git commit -m "feat(S5-T2): persistence service and gap completion flow

- Migration add_activo_seguimiento_to_indicadores
- MirPersistenciaService: transactional import with enum mapping
- CompletarHuecos Livewire component with inline correction
- Indicators with critical gaps: activo_seguimiento=false
- 8 tests covering persistence, correction, and finalization

Resolves DTE-XX"
```

---

## Task 3: Vinculación con Cascada de Planes (S5-T3)

**Branch:** `feat/S5-T3-vinculacion-importados`

**Files:**
- Create: `app/Livewire/Mml/VincularAlineacion.php`
- Create: `resources/views/livewire/mml/vincular-alineacion.blade.php`
- Create: `tests/Feature/Mml/VincularAlineacionTest.php`
- Modify: `routes/web/mml.php`

### Step 1: Create branch

```bash
git checkout desarrollo && git pull
git checkout -b feat/S5-T3-vinculacion-importados
```

### Step 2: Write `VincularAlineacion` component

Create `app/Livewire/Mml/VincularAlineacion.php`:

```php
<?php

namespace App\Livewire\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Cascade\PedLineaAccion;
use App\Models\Cascade\PedObjetivoEstrategico;
use App\Models\Mml\ImportacionReporte;
use App\Models\Mml\MirNivel;
use App\Services\Embeddings\SemanticSearchService;
use Livewire\Component;

class VincularAlineacion extends Component
{
    public ImportacionReporte $reporte;
    public int $pasoActual = 0;
    public array $sugerencias = [];
    public array $nivelesIds = [];

    public function mount(ImportacionReporte $reporte): void
    {
        $this->reporte = $reporte;
        $this->nivelesIds = $reporte->programa->mirNiveles()
            ->orderByRaw("FIELD(tipo_nivel, 'fin', 'proposito', 'componente', 'actividad')")
            ->pluck('id')
            ->toArray();
    }

    public function buscar(): void
    {
        $nivel = MirNivel::find($this->nivelesIds[$this->pasoActual] ?? null);
        if (! $nivel || ! $nivel->resumen_narrativo) {
            $this->sugerencias = [];
            return;
        }

        $search = app(SemanticSearchService::class);
        $tipoNivel = TipoNivelMir::from($nivel->tipo_nivel);

        $modelClass = in_array($tipoNivel, [TipoNivelMir::FIN, TipoNivelMir::PROPOSITO])
            ? PedObjetivoEstrategico::class
            : PedLineaAccion::class;

        try {
            $results = $search->findSimilar($nivel->resumen_narrativo, $modelClass, 5);
            $this->sugerencias = $results->map(fn ($r) => [
                'id' => $r->model->id,
                'tipo' => class_basename($modelClass),
                'texto' => method_exists($r->model, 'getDescripcionAttribute')
                    ? $r->model->descripcion
                    : ($r->model->nombre ?? $r->model->descripcion ?? ''),
                'score' => round($r->score * 100, 1),
                'alta_confianza' => $r->isHighQuality(0.85),
            ])->toArray();
        } catch (\Throwable $e) {
            $this->sugerencias = [];
        }
    }

    public function seleccionar(int $entidadId, string $tipo): void
    {
        $nivel = MirNivel::find($this->nivelesIds[$this->pasoActual] ?? null);
        if (! $nivel) return;

        if ($tipo === 'PedObjetivoEstrategico') {
            $nivel->update(['ped_objetivo_estrategico_id' => $entidadId]);
        } else {
            $nivel->update(['ped_linea_accion_id' => $entidadId]);
        }

        $this->siguiente();
    }

    public function omitir(): void
    {
        $this->siguiente();
    }

    public function siguiente(): void
    {
        $this->sugerencias = [];
        if ($this->pasoActual < count($this->nivelesIds) - 1) {
            $this->pasoActual++;
        }
    }

    public function anterior(): void
    {
        $this->sugerencias = [];
        if ($this->pasoActual > 0) {
            $this->pasoActual--;
        }
    }

    public function finalizar(): void
    {
        return $this->redirect(
            route('mml.importar.calendarizar', ['reporte' => $this->reporte->id]),
            navigate: true
        );
    }

    public function render()
    {
        $nivelActual = isset($this->nivelesIds[$this->pasoActual])
            ? MirNivel::with(['pedObjetivoEstrategico', 'pedLineaAccion'])->find($this->nivelesIds[$this->pasoActual])
            : null;

        $niveles = MirNivel::whereIn('id', $this->nivelesIds)
            ->with(['pedObjetivoEstrategico', 'pedLineaAccion'])
            ->get()
            ->keyBy('id');

        return view('livewire.mml.vincular-alineacion', [
            'nivelActual' => $nivelActual,
            'niveles' => $niveles,
            'totalPasos' => count($this->nivelesIds),
        ]);
    }
}
```

### Step 3: Write view (abbreviated — follows same patterns as prior views)

Create `resources/views/livewire/mml/vincular-alineacion.blade.php` with:
- Stepper showing current step / total
- Current nivel card with resumen narrativo
- "Buscar Alineación" button triggering `buscar()`
- Suggestions list with score % and "Alta confianza" badge
- Select / Skip buttons
- Summary table at the end

### Step 4: Add route

```php
Route::get('/importar/{reporte}/vincular', \App\Livewire\Mml\VincularAlineacion::class)->name('mml.importar.vincular');
```

### Step 5: Write tests

Create `tests/Feature/Mml/VincularAlineacionTest.php` with:
- `test_componente_se_renderiza`
- `test_omitir_avanza_paso`
- `test_seleccionar_persiste_fk` (mock SemanticSearchService)
- `test_finalizar_redirige`

### Step 6: Run tests and commit

```bash
./vendor/bin/sail artisan test
git add -A && git commit -m "feat(S5-T3): cascade alignment wizard for imported programs

- VincularAlineacion Livewire component with step-by-step wizard
- Reuses SemanticSearchService::findSimilar() from S4-T8
- Skip/select per nivel, summary table
- 4 tests

Resolves DTE-XX"
```

---

## Task 4: Calendarización de Metas (S5-T4)

**Branch:** `feat/S5-T4-calendarizacion-metas`

**Files:**
- Create: `database/migrations/2026_03_09_040000_create_metas_periodo_table.php`
- Create: `app/Models/Mml/MetaPeriodo.php`
- Create: `app/Services/Mml/CalendarizacionService.php`
- Create: `app/Livewire/Mml/CalendarizarMetas.php`
- Create: `resources/views/livewire/mml/calendarizar-metas.blade.php`
- Create: `tests/Feature/Mml/CalendarizacionTest.php`
- Modify: `routes/web/mml.php`
- Modify: `app/Models/Mml/Indicador.php` (add `metasPeriodo` relation)

### Step 1: Create branch

```bash
git checkout desarrollo && git pull
git checkout -b feat/S5-T4-calendarizacion-metas
```

### Step 2: Write migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas_periodo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')
                ->constrained('indicadores')->cascadeOnDelete();
            $table->smallInteger('periodo'); // 1..12 for monthly, 1..4 for quarterly, etc.
            $table->decimal('meta_periodo', 12, 4);
            $table->integer('ejercicio_fiscal');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['indicador_id', 'periodo', 'ejercicio_fiscal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_periodo');
    }
};
```

### Step 3: Write model `MetaPeriodo`

Create `app/Models/Mml/MetaPeriodo.php`:

```php
<?php

namespace App\Models\Mml;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaPeriodo extends Model
{
    protected $table = 'metas_periodo';

    protected $fillable = [
        'indicador_id', 'periodo', 'meta_periodo', 'ejercicio_fiscal', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'periodo' => 'integer',
            'meta_periodo' => 'decimal:4',
            'ejercicio_fiscal' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }
}
```

### Step 4: Add relation to Indicador

Add to `app/Models/Mml/Indicador.php`:

```php
public function metasPeriodo(): HasMany
{
    return $this->hasMany(MetaPeriodo::class)->orderBy('periodo');
}
```

### Step 5: Write `CalendarizacionService`

Create `app/Services/Mml/CalendarizacionService.php`:

```php
<?php

namespace App\Services\Mml;

use App\Enums\FrecuenciaMedicion;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;

class CalendarizacionService
{
    /**
     * Generate proposed period goals for all active indicators.
     *
     * @return array<int, array{indicador_id: int, nombre: string, meta: float, frecuencia: string, periodos: array}>
     */
    public function generar(ProgramaPresupuestario $programa): array
    {
        $indicadores = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $programa->id))
            ->where('activo_seguimiento', true)
            ->whereNotNull('meta')
            ->get();

        return $indicadores->map(function (Indicador $ind) use ($programa) {
            $numPeriodos = $this->numeroPeriodos($ind->frecuencia);
            $metaPorPeriodo = $numPeriodos > 0 ? round($ind->meta / $numPeriodos, 4) : $ind->meta;

            $periodos = [];
            for ($p = 1; $p <= $numPeriodos; $p++) {
                $periodos[] = [
                    'periodo' => $p,
                    'meta_periodo' => $metaPorPeriodo,
                ];
            }

            return [
                'indicador_id' => $ind->id,
                'nombre' => $ind->nombre,
                'meta' => (float) $ind->meta,
                'frecuencia' => $ind->frecuencia->value,
                'periodos' => $periodos,
            ];
        })->values()->toArray();
    }

    /**
     * Persist confirmed period goals.
     */
    public function confirmar(ProgramaPresupuestario $programa, array $metasAjustadas, int $ejercicio): void
    {
        foreach ($metasAjustadas as $item) {
            foreach ($item['periodos'] as $periodo) {
                MetaPeriodo::updateOrCreate(
                    [
                        'indicador_id' => $item['indicador_id'],
                        'periodo' => $periodo['periodo'],
                        'ejercicio_fiscal' => $ejercicio,
                    ],
                    [
                        'meta_periodo' => $periodo['meta_periodo'],
                        'activo' => true,
                    ]
                );
            }
        }
    }

    public function numeroPeriodos(FrecuenciaMedicion $frecuencia): int
    {
        return match ($frecuencia) {
            FrecuenciaMedicion::MENSUAL => 12,
            FrecuenciaMedicion::TRIMESTRAL => 4,
            FrecuenciaMedicion::SEMESTRAL => 2,
            FrecuenciaMedicion::ANUAL => 1,
            FrecuenciaMedicion::BIANUAL, FrecuenciaMedicion::SEXENAL => 1,
        };
    }
}
```

### Step 6: Write `CalendarizacionTest`

Create `tests/Feature/Mml/CalendarizacionTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Mml\CalendarizacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarizacionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private CalendarizacionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $this->service = new CalendarizacionService();
    }

    public function test_genera_12_periodos_para_mensual(): void
    {
        $this->crearIndicador('mensual', 1200);
        $propuesta = $this->service->generar($this->programa);

        $this->assertCount(1, $propuesta);
        $this->assertCount(12, $propuesta[0]['periodos']);
        $this->assertEquals(100, $propuesta[0]['periodos'][0]['meta_periodo']);
    }

    public function test_genera_4_periodos_para_trimestral(): void
    {
        $this->crearIndicador('trimestral', 400);
        $propuesta = $this->service->generar($this->programa);

        $this->assertCount(4, $propuesta[0]['periodos']);
        $this->assertEquals(100, $propuesta[0]['periodos'][0]['meta_periodo']);
    }

    public function test_genera_1_periodo_para_anual(): void
    {
        $this->crearIndicador('anual', 500);
        $propuesta = $this->service->generar($this->programa);

        $this->assertCount(1, $propuesta[0]['periodos']);
        $this->assertEquals(500, $propuesta[0]['periodos'][0]['meta_periodo']);
    }

    public function test_ignora_indicadores_sin_meta(): void
    {
        $this->crearIndicador('anual', null);
        $propuesta = $this->service->generar($this->programa);

        $this->assertEmpty($propuesta);
    }

    public function test_ignora_indicadores_inactivos(): void
    {
        $this->crearIndicador('anual', 100, false);
        $propuesta = $this->service->generar($this->programa);

        $this->assertEmpty($propuesta);
    }

    public function test_confirmar_persiste_metas_periodo(): void
    {
        $indicador = $this->crearIndicador('trimestral', 400);
        $propuesta = $this->service->generar($this->programa);

        $this->service->confirmar($this->programa, $propuesta, 2026);

        $this->assertEquals(4, MetaPeriodo::where('indicador_id', $indicador->id)->count());
        $this->assertDatabaseHas('metas_periodo', [
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'ejercicio_fiscal' => 2026,
        ]);
    }

    private function crearIndicador(string $frecuencia, ?float $meta, bool $activo = true): Indicador
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test',
            'orden' => 1,
        ]);

        return Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Ind test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => $frecuencia,
            'meta' => $meta,
            'activo_seguimiento' => $activo,
            'orden' => 1,
        ]);
    }
}
```

### Step 7: Write Livewire component and view (follows same pattern as prior components)

### Step 8: Add route, run tests, commit

```bash
Route::get('/importar/{reporte}/calendarizar', \App\Livewire\Mml\CalendarizarMetas::class)->name('mml.importar.calendarizar');

./vendor/bin/sail artisan test
git add -A && git commit -m "feat(S5-T4): goal scheduling by measurement frequency

- Migration create_metas_periodo_table (anticipates S6)
- MetaPeriodo model with unique constraint
- CalendarizacionService: generar() + confirmar()
- CalendarizarMetas component with editable table
- 6 tests covering all frequencies and edge cases

Resolves DTE-XX"
```

---

## Task 5: Ruta y Dashboard de Importación (S5-T5)

**Branch:** `feat/S5-T5-ruta-dashboard-importacion`

**Files:**
- Create: `app/Livewire/Mml/DashboardImportaciones.php`
- Create: `resources/views/livewire/mml/dashboard-importaciones.blade.php`
- Create: `resources/views/livewire/mml/partials/stepper-importacion.blade.php`
- Create: `tests/Feature/Mml/DashboardImportacionesTest.php`
- Modify: `routes/web/mml.php` (consolidate import routes)

### Step 1: Create branch

```bash
git checkout desarrollo && git pull
git checkout -b feat/S5-T5-ruta-dashboard-importacion
```

### Step 2: Write stepper partial

Create `resources/views/livewire/mml/partials/stepper-importacion.blade.php`:

```blade
@props(['pasoActual' => 1])

@php
$pasos = [
    ['num' => 1, 'label' => 'Subir archivo'],
    ['num' => 2, 'label' => 'Completar huecos'],
    ['num' => 3, 'label' => 'Vincular planes'],
    ['num' => 4, 'label' => 'Calendarizar'],
];
@endphp

<nav class="mb-6">
    <ol class="flex items-center w-full text-sm">
        @foreach($pasos as $paso)
            <li class="flex items-center {{ $loop->last ? '' : 'w-full' }}">
                <span class="flex items-center justify-center w-8 h-8 rounded-full shrink-0
                    {{ $paso['num'] < $pasoActual ? 'bg-green-600 text-white' :
                       ($paso['num'] === $pasoActual ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500') }}">
                    {{ $paso['num'] < $pasoActual ? '✓' : $paso['num'] }}
                </span>
                <span class="ml-2 {{ $paso['num'] === $pasoActual ? 'font-medium' : 'text-gray-500' }}">
                    {{ $paso['label'] }}
                </span>
                @if(!$loop->last)
                    <div class="w-full h-0.5 mx-4 {{ $paso['num'] < $pasoActual ? 'bg-green-600' : 'bg-gray-200' }}"></div>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
```

### Step 3: Write `DashboardImportaciones` component

Create `app/Livewire/Mml/DashboardImportaciones.php`:

```php
<?php

namespace App\Livewire\Mml;

use App\Models\Mml\ImportacionReporte;
use Livewire\Component;

class DashboardImportaciones extends Component
{
    public function render()
    {
        $reportes = ImportacionReporte::where('team_id', auth()->user()->currentTeam->id)
            ->with(['programa', 'creador'])
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.mml.dashboard-importaciones', [
            'reportes' => $reportes,
        ]);
    }
}
```

### Step 4: Write view, consolidate routes, write tests

Add to `routes/web/mml.php`:

```php
// Import flow routes (outside {programa} group)
Route::prefix('importar')->group(function () {
    Route::get('/', \App\Livewire\Mml\DashboardImportaciones::class)->name('mml.importaciones');
    Route::get('/nuevo', \App\Livewire\Mml\ImportarPrograma::class)->name('mml.importar.nuevo');
    Route::get('/{reporte}/completar', \App\Livewire\Mml\CompletarHuecos::class)->name('mml.importar.completar');
    Route::get('/{reporte}/vincular', \App\Livewire\Mml\VincularAlineacion::class)->name('mml.importar.vincular');
    Route::get('/{reporte}/calendarizar', \App\Livewire\Mml\CalendarizarMetas::class)->name('mml.importar.calendarizar');
});
```

### Step 5: Write `DashboardImportacionesTest`

```php
<?php

namespace Tests\Feature\Mml;

use App\Livewire\Mml\DashboardImportaciones;
use App\Models\Mml\ImportacionReporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardImportacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_reportes_del_equipo(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        ImportacionReporte::create([
            'team_id' => $user->currentTeam->id,
            'archivo_original' => 'test.md',
            'formato' => 'md',
            'datos_parseados' => ['niveles' => []],
            'estado' => 'pendiente',
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(DashboardImportaciones::class)
            ->assertSee('test.md');
    }

    public function test_no_muestra_reportes_de_otro_equipo(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $other = User::factory()->withPersonalTeam()->create();

        ImportacionReporte::create([
            'team_id' => $other->currentTeam->id,
            'archivo_original' => 'secret.md',
            'formato' => 'md',
            'datos_parseados' => ['niveles' => []],
            'estado' => 'pendiente',
            'created_by' => $other->id,
        ]);

        Livewire::actingAs($user)
            ->test(DashboardImportaciones::class)
            ->assertDontSee('secret.md');
    }
}
```

### Step 6: Run tests and commit

```bash
./vendor/bin/sail artisan test
git add -A && git commit -m "feat(S5-T5): import dashboard and wizard routing

- DashboardImportaciones component with team-scoped listing
- Stepper partial for 4-step wizard navigation
- Consolidated import routes under /importar prefix
- 2 tests for team scoping

Resolves DTE-XX"
```

---

## Execution Summary

| Task | Branch | Tests | Key Deliverables |
|------|--------|-------|-----------------|
| T1 | `feat/S5-T1-importador-multiformato` | ~18 | DTO, ParserService, DiagnosticoService, ImportarPrograma |
| T2 | `feat/S5-T2-flujo-completitud` | ~8 | PersistenciaService, CompletarHuecos, activo_seguimiento |
| T3 | `feat/S5-T3-vinculacion-importados` | ~4 | VincularAlineacion wizard, reuses SemanticSearch |
| T4 | `feat/S5-T4-calendarizacion-metas` | ~6 | metas_periodo table, CalendarizacionService |
| T5 | `feat/S5-T5-ruta-dashboard-importacion` | ~2 | Dashboard, stepper, consolidated routes |

**Execution order:** T1 → T2 → T3 ∥ T4 → T5

**Estimated new tests:** ~38
**Expected baseline after sprint:** ~286 tests passing
