<?php

namespace App\Console\Commands;

use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Services\Evaluation\IndiceEficaciaService;
use App\Services\Evaluation\LogicaVerticalService;
use Illuminate\Console\Command;

class CalcularIndiceEficacia extends Command
{
    protected $signature = 'evaluacion:calcular-indice
        {ejercicio : Ejercicio fiscal}
        {programa_id? : ID del programa presupuestario}
        {--all : Calcular para todos los programas}
        {--con-analisis-ia : Ejecutar analisis de logica vertical con IA}';

    protected $description = 'Calcula el índice de eficacia de un programa presupuestario';

    public function handle(IndiceEficaciaService $service): int
    {
        $ejercicio = (int) $this->argument('ejercicio');

        if ($this->option('all')) {
            return $this->calcularTodos($service, $ejercicio);
        }

        $programaId = $this->argument('programa_id');

        if (! $programaId) {
            $this->error('Debe proporcionar un programa_id o usar --all.');

            return self::FAILURE;
        }

        $programa = ProgramaPresupuestario::find($programaId);

        if (! $programa) {
            $this->error("Programa #{$programaId} no encontrado.");

            return self::FAILURE;
        }

        $evaluacion = $service->calcular($programa, $ejercicio);
        $this->mostrarResultado($evaluacion);

        if ($this->option('con-analisis-ia')) {
            $analisis = app(LogicaVerticalService::class)->analizar($evaluacion);
            if ($analisis) {
                $this->info('Analisis IA guardado.');
            } else {
                $this->warn('No se pudo generar el analisis IA.');
            }
        }

        return self::SUCCESS;
    }

    private function calcularTodos(IndiceEficaciaService $service, int $ejercicio): int
    {
        $programas = ProgramaPresupuestario::where('ejercicio_fiscal', $ejercicio)->get();

        if ($programas->isEmpty()) {
            $this->warn("No se encontraron programas para el ejercicio {$ejercicio}.");

            return self::SUCCESS;
        }

        $this->info("Calculando índice para {$programas->count()} programa(s)...");

        $conAnalisis = $this->option('con-analisis-ia');

        foreach ($programas as $programa) {
            $evaluacion = $service->calcular($programa, $ejercicio);
            $this->line("  [{$programa->clave}] {$programa->nombre}: {$evaluacion->indice_eficacia}");

            if ($conAnalisis) {
                $analisis = app(LogicaVerticalService::class)->analizar($evaluacion);
                $this->line($analisis ? '    -> Analisis IA guardado.' : '    -> Analisis IA no disponible.');
            }
        }

        $this->info('Cálculo completado.');

        return self::SUCCESS;
    }

    private function mostrarResultado(EvaluacionPrograma $evaluacion): void
    {
        $this->info("Índice de eficacia: {$evaluacion->indice_eficacia}");
        $this->newLine();

        $this->info('Desglose por nivel:');
        foreach ($evaluacion->desglose_niveles as $nivel => $datos) {
            $promedio = $datos['promedio'] ?? 'N/A';
            $peso = $datos['peso'];
            $this->line("  {$nivel}: promedio={$promedio}, peso={$peso}, evaluados={$datos['indicadores_evaluados']}, no_evaluados={$datos['indicadores_no_evaluados']}");
        }

        $this->newLine();
        $this->info('Conteo de semáforos:');
        foreach ($evaluacion->conteo_semaforos as $color => $count) {
            $this->line("  {$color}: {$count}");
        }

        $this->newLine();
        $this->line("Indicadores evaluados: {$evaluacion->indicadores_evaluados}");
        $this->line("Indicadores no evaluados: {$evaluacion->indicadores_no_evaluados}");
    }
}
