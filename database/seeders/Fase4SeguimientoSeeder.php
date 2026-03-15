<?php

namespace Database\Seeders;

use App\Enums\EstadoAvance;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\Tracking\AvanceVariable;
use App\Models\Tracking\Desbloqueo;
use App\Models\Team;
use App\Models\User;
use App\Services\Tracking\AvanceEstadoService;
use App\Services\Tracking\FormulaEvaluatorService;
use App\Services\Tracking\SemaforoService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class Fase4SeguimientoSeeder extends Seeder
{
    private AvanceEstadoService $estadoService;

    private FormulaEvaluatorService $formulaService;

    private SemaforoService $semaforoService;

    private string $dummyHash;

    /** UR clave → slug mapping */
    private array $slugMap = [
        'SE-001' => 'se',
        'SS-002' => 'ss',
        'SEG-003' => 'seg',
        'SECTUR-004' => 'sectur',
    ];

    /** Cache of resolved users per UR slug */
    private array $userCache = [];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar este seeder en producción.');

            return;
        }

        $this->command->info('Fase 4: Creando datos de seguimiento (avances)...');

        $this->estadoService = app(AvanceEstadoService::class);
        $this->formulaService = new FormulaEvaluatorService;
        $this->semaforoService = new SemaforoService;

        // Create dummy evidence file
        Storage::disk('local')->put('evidencias/dummy/evidencia-qa.pdf', '%PDF-1.4 dummy QA');
        $this->dummyHash = hash('sha256', Storage::disk('local')->get('evidencias/dummy/evidencia-qa.pdf'));

        // Pre-cache users
        $this->preloadUsers();

        // Process all indicators with their open metas
        $hoy = Carbon::parse('2026-03-15');

        $indicadores = Indicador::with([
            'variables',
            'mirNivel.programa.team',
            'mirNivel.team',
            'metasPeriodo',
        ])->whereHas('metasPeriodo')->get();

        $contador = 0;

        foreach ($indicadores as $indicador) {
            $teamId = $indicador->mirNivel->team_id ?? $indicador->mirNivel->programa->team_id;
            $team = $indicador->mirNivel->team ?? $indicador->mirNivel->programa->team;

            if (! $team) {
                continue;
            }

            $urSuffix = $this->getUrSuffix($team->clave_ur);
            $operador = $this->getUser("operador.{$urSuffix}@sistema.test");
            $planeador = $this->getUser("planeador.{$urSuffix}@sistema.test");

            if (! $operador || ! $planeador) {
                continue;
            }

            $programaClave = $indicador->mirNivel->programa->clave ?? 'UNKNOWN';

            // Get metas with fecha_apertura <= today
            $metasProcesar = $indicador->metasPeriodo
                ->filter(fn (MetaPeriodo $m) => $m->fecha_apertura && $m->fecha_apertura->lte($hoy))
                ->sortBy('periodo');

            foreach ($metasProcesar as $meta) {
                $contador++;
                $escenario = $this->escenarioParaPrograma(
                    $programaClave,
                    $meta->periodo,
                    $meta->ejercicio_fiscal
                );

                $isPastDue = $meta->fecha_cierre && $meta->fecha_cierre->lt($hoy);

                if ($isPastDue && $contador % 33 === 0) {
                    // ~3% VENCIDO - only for closed periods
                    $this->crearAvanceVencido($meta, $indicador, $operador);
                } elseif ($contador % 50 === 0) {
                    // ~2% EN_CAPTURA - just created, no transitions
                    $this->crearAvanceEnCaptura($meta, $indicador, $operador);
                } elseif ($contador % 33 === 0) {
                    // ~3% EN_REVISION - sent but not yet reviewed
                    $avance = $this->crearAvanceConDatos($meta, $indicador, $operador, $escenario);
                    $this->estadoService->transicionar($avance, EstadoAvance::EN_REVISION, $operador, notificar: false);
                } elseif ($contador % 10 === 0) {
                    // ~10% APROBADO with rejection cycle
                    $avance = $this->crearAvanceConRechazo($meta, $indicador, $operador, $planeador, $escenario);
                    if ($contador % 5 === 0) {
                        $this->crearEvidencia($avance, $team, $meta, $operador);
                    }
                } else {
                    // ~82% APROBADO direct
                    $avance = $this->crearAvanceAprobado($meta, $indicador, $operador, $planeador, $escenario);
                    if ($contador % 5 === 0) {
                        $this->crearEvidencia($avance, $team, $meta, $operador);
                    }
                }
            }
        }

        // Create 1 desbloqueo
        $this->crearDesbloqueo();

        $this->command->info("Fase 4 completada.");
        $this->command->info("  Avances: " . Avance::count());
        $this->command->info("  Variables: " . AvanceVariable::count());
        $this->command->info("  Evidencias: " . AvanceEvidencia::count());
        $this->command->info("  Desbloqueos: " . Desbloqueo::count());
    }

    // ─── Avance creation methods ────────────────────────────────────────

    private function crearAvanceVencido(MetaPeriodo $meta, Indicador $indicador, User $operador): Avance
    {
        $avance = Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA,
            'capturado_por' => $operador->id,
        ]);

        $this->estadoService->transicionar($avance, EstadoAvance::VENCIDO, $operador, notificar: false);

        return $avance;
    }

    private function crearAvanceEnCaptura(MetaPeriodo $meta, Indicador $indicador, User $operador): Avance
    {
        return Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA,
            'capturado_por' => $operador->id,
        ]);
    }

    private function crearAvanceConDatos(MetaPeriodo $meta, Indicador $indicador, User $operador, string $escenario): Avance
    {
        $avance = Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA,
            'capturado_por' => $operador->id,
        ]);

        $this->capturarVariablesYCalcular($avance, $indicador, $meta, $escenario);

        return $avance;
    }

    private function crearAvanceAprobado(MetaPeriodo $meta, Indicador $indicador, User $operador, User $planeador, string $escenario): Avance
    {
        $avance = $this->crearAvanceConDatos($meta, $indicador, $operador, $escenario);

        $this->estadoService->transicionar($avance, EstadoAvance::EN_REVISION, $operador, notificar: false);
        $this->estadoService->transicionar($avance, EstadoAvance::APROBADO, $planeador, notificar: false);

        return $avance;
    }

    private function crearAvanceConRechazo(MetaPeriodo $meta, Indicador $indicador, User $operador, User $planeador, string $escenario): Avance
    {
        $avance = $this->crearAvanceConDatos($meta, $indicador, $operador, $escenario);

        // Send to review
        $this->estadoService->transicionar($avance, EstadoAvance::EN_REVISION, $operador, notificar: false);

        // Planner rejects
        $observacion = $this->generarObservacion($indicador);
        $this->estadoService->transicionar($avance, EstadoAvance::OBSERVADO, $planeador, $observacion, notificar: false);

        // Operator corrects
        $this->estadoService->transicionar($avance, EstadoAvance::EN_CAPTURA, $operador, notificar: false);

        // Adjust variable value (~5% change)
        $this->corregirAvance($avance, $indicador);

        // Re-send to review
        $this->estadoService->transicionar($avance, EstadoAvance::EN_REVISION, $operador, notificar: false);

        // Planner approves on second attempt
        $this->estadoService->transicionar($avance, EstadoAvance::APROBADO, $planeador, notificar: false);

        return $avance;
    }

    // ─── Variable capture & calculation ─────────────────────────────────

    private function capturarVariablesYCalcular(Avance $avance, Indicador $indicador, MetaPeriodo $meta, string $escenario): void
    {
        $variablesMap = [];

        foreach ($indicador->variables as $variable) {
            $valor = $this->generarValor($indicador, $variable, $escenario, $meta);

            AvanceVariable::create([
                'avance_id' => $avance->id,
                'indicador_variable_id' => $variable->id,
                'valor' => $valor,
            ]);

            $variablesMap[$variable->simbolo] = $valor;
        }

        // Calculate result using real formula evaluator
        $resultado = null;
        if ($indicador->formula_texto && ! empty($variablesMap)) {
            $resultado = $this->formulaService->evaluar($indicador->formula_texto, $variablesMap);
        }

        // Calculate semaforo using real service
        $semaforo = null;
        if ($resultado !== null) {
            $semaforo = $this->semaforoService->calcular($resultado, $indicador, (float) $meta->meta_periodo);
        }

        // Justification for amarillo/rojo
        $justificacion = null;
        if (in_array($semaforo, ['amarillo', 'rojo'])) {
            $justificacion = $this->generarJustificacion($indicador, $semaforo);
        }

        $avance->update([
            'resultado' => $resultado,
            'semaforo_calculado' => $semaforo,
            'justificacion_final' => $justificacion,
        ]);
    }

    // ─── Value generation ───────────────────────────────────────────────

    private function generarValor(Indicador $indicador, $variable, string $escenario, MetaPeriodo $meta): float
    {
        $metaAnual = (float) $indicador->meta;
        $metaPeriodo = (float) $meta->meta_periodo;
        $numVariables = $indicador->variables->count();

        // Single-variable formula: value IS the result
        if ($numVariables === 1) {
            $targetPct = match ($escenario) {
                'verde' => rand(90, 110) / 100,
                'amarillo' => rand(70, 89) / 100,
                'rojo' => rand(30, 69) / 100,
                default => rand(85, 105) / 100,
            };

            return round($metaPeriodo * $targetPct, 4);
        }

        // Multi-variable formulas "(A / B) x 100":
        if ($variable->orden === 2) {
            // Denominator: fixed base value
            return max(1, round($metaAnual * 2, 0));
        }

        // Numerator: varies by desired outcome
        $base = max(1, round($metaAnual * 2, 0));
        $targetPct = match ($escenario) {
            'verde' => rand(90, 110) / 100,
            'amarillo' => rand(70, 89) / 100,
            'rojo' => rand(30, 69) / 100,
            default => rand(85, 105) / 100,
        };

        return round($base * $metaPeriodo / 100 * $targetPct, 2);
    }

    // ─── Correction after rejection ─────────────────────────────────────

    private function corregirAvance(Avance $avance, Indicador $indicador): void
    {
        $primeraVariable = $avance->variables()->first();
        if ($primeraVariable) {
            $nuevoValor = round((float) $primeraVariable->valor * 1.05, 4);
            $primeraVariable->update(['valor' => $nuevoValor]);
        }

        // Recalculate
        $variablesMap = [];
        foreach ($avance->variables()->with('indicadorVariable')->get() as $av) {
            $variablesMap[$av->indicadorVariable->simbolo] = (float) $av->valor;
        }

        if (empty($variablesMap) || ! $indicador->formula_texto) {
            return;
        }

        $resultado = $this->formulaService->evaluar($indicador->formula_texto, $variablesMap);
        $metaPeriodo = (float) $avance->metaPeriodo->meta_periodo;
        $semaforo = $resultado !== null
            ? $this->semaforoService->calcular($resultado, $indicador, $metaPeriodo)
            : null;

        $avance->update([
            'resultado' => $resultado,
            'semaforo_calculado' => $semaforo,
        ]);
    }

    // ─── Evidence ───────────────────────────────────────────────────────

    private function crearEvidencia(Avance $avance, Team $team, MetaPeriodo $meta, User $operador): void
    {
        AvanceEvidencia::create([
            'avance_id' => $avance->id,
            'nombre_archivo' => 'reporte-trimestral.pdf',
            'ruta_archivo' => 'evidencias/dummy/evidencia-qa.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1024,
            'hash_archivo' => $this->dummyHash,
            'nombre_documento' => 'Reporte trimestral del indicador',
            'area_generadora' => $team->name,
            'fecha_documento' => $meta->fecha_cierre,
            'subido_por' => $operador->id,
        ]);
    }

    // ─── Desbloqueo ─────────────────────────────────────────────────────

    private function crearDesbloqueo(): void
    {
        $avanceAprobado = Avance::where('estado', 'aprobado')->latest()->first();

        if (! $avanceAprobado) {
            return;
        }

        $admin = User::where('email', 'admin@sistema.test')->first();

        if (! $admin) {
            return;
        }

        Desbloqueo::create([
            'avance_id' => $avanceAprobado->id,
            'motivo' => 'Error en variable A: se registró 450 pero el dato correcto es 485.',
            'solicitado_por' => $avanceAprobado->capturado_por,
            'estado' => 'aprobado',
            'resuelto_por' => $admin->id,
            'resuelto_at' => now(),
        ]);

        $historial = $avanceAprobado->historial_observaciones ?? [];
        $historial[] = [
            'fecha' => now()->toISOString(),
            'usuario_id' => $admin->id,
            'usuario_nombre' => $admin->name,
            'accion' => 'desbloqueo_aprobado',
            'estado_anterior' => 'aprobado',
            'estado_nuevo' => 'en_captura',
            'observacion' => 'Desbloqueo excepcional. Motivo: Error en variable A.',
        ];

        $avanceAprobado->update([
            'estado' => EstadoAvance::EN_CAPTURA,
            'congelado_at' => null,
            'historial_observaciones' => $historial,
        ]);
    }

    // ─── Scenario patterns ──────────────────────────────────────────────

    private function escenarioParaPrograma(string $clave, int $periodo, int $ejercicio): string
    {
        return match ($clave) {
            'ISM-001' => match ($periodo) { 3 => 'rojo', 4 => 'amarillo', default => 'verde' },
            'EDU-002' => 'verde',
            'PEC-001' => $periodo <= 2 ? 'amarillo' : 'verde',
            'FSP-001' => match ($periodo) { 1 => 'rojo', 2 => 'rojo', 3 => 'amarillo', default => 'verde' },
            'DDT-001' => match ($periodo) { 3 => 'rojo', default => 'verde' },
            default => 'verde',
        };
    }

    // ─── Observation text ───────────────────────────────────────────────

    private function generarObservacion(Indicador $indicador): string
    {
        $dim = $indicador->dimension?->value ?? 'eficacia';

        return match ($dim) {
            'eficacia' => 'El valor reportado no coincide con el padrón de beneficiarios. Verificar contra registro actualizado del periodo.',
            'eficiencia' => 'El costo unitario reportado no incluye gastos indirectos. Recalcular con datos del módulo presupuestal.',
            'economia' => 'El monto devengado difiere del avance financiero registrado en el sistema. Cruzar información con partida presupuestal.',
            'calidad' => 'La encuesta de satisfacción no alcanza el tamaño muestral mínimo requerido. Ampliar muestra.',
            default => 'Revisar datos reportados. Se detectaron inconsistencias con la información de respaldo.',
        };
    }

    // ─── Justification text ─────────────────────────────────────────────

    private function generarJustificacion(Indicador $indicador, string $semaforo): string
    {
        $nombre = $indicador->nombre ?? 'el indicador';

        if ($semaforo === 'rojo') {
            return "El indicador \"{$nombre}\" presenta un avance significativamente menor al esperado. "
                . 'Se implementarán acciones correctivas en el siguiente periodo para recuperar la meta programada.';
        }

        return "El indicador \"{$nombre}\" muestra un avance ligeramente inferior a la meta del periodo. "
            . 'Se están realizando ajustes operativos para alcanzar la meta anual.';
    }

    // ─── User resolution helpers ────────────────────────────────────────

    private function preloadUsers(): void
    {
        $users = User::whereIn('email', [
            'operador.se@sistema.test', 'planeador.se@sistema.test',
            'operador.ss@sistema.test', 'planeador.ss@sistema.test',
            'operador.seg@sistema.test', 'planeador.seg@sistema.test',
            'operador.sectur@sistema.test', 'planeador.sectur@sistema.test',
            'admin@sistema.test',
        ])->get()->keyBy('email');

        foreach ($users as $email => $user) {
            $this->userCache[$email] = $user;
        }
    }

    private function getUser(string $email): ?User
    {
        return $this->userCache[$email] ?? null;
    }

    private function getUrSuffix(string $claveUr): string
    {
        return $this->slugMap[$claveUr] ?? strtolower(explode('-', $claveUr)[0]);
    }
}
