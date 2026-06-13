<?php

namespace Database\Seeders\Evaluation;

use App\Enums\EstadoEvaluacionExterna;
use App\Enums\PrioridadRecomendacion;
use App\Enums\SeveridadHallazgo;
use App\Enums\StatusAsm;
use App\Enums\TipoAccionAsm;
use App\Enums\TipoEvaluacionExterna;
use App\Enums\TipoPlazoAsm;
use App\Models\Evaluation\Asm;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Evaluation\Hallazgo;
use App\Models\Evaluation\InformeEvaluacion;
use App\Models\Evaluation\Recomendacion;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos demo del sub-sistema de Evaluación Externa estructurada (PR #30-#34):
 * cadena completa EvaluacionExterna → InformeEvaluacion → Hallazgo →
 * Recomendacion → Asm, sobre el programa ISM-001.
 *
 * Idempotente (firstOrCreate por claves naturales). Pensado para invocarse
 * desde QaTestingSeeder; requiere que ISM-001 y los usuarios QA ya existan.
 */
class EvaluacionExternaDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('No se puede ejecutar EvaluacionExternaDemoSeeder en producción.');

            return;
        }

        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->first();
        if (! $programa) {
            $this->command?->warn('ISM-001 no existe; se omite EvaluacionExternaDemoSeeder.');

            return;
        }

        $responsable = User::where('email', 'ele.planeador@gmail.com')->first()
            ?? User::where('email', 'ele.admin@gmail.com')->first();
        if (! $responsable) {
            $this->command?->warn('No hay usuario QA responsable; se omite EvaluacionExternaDemoSeeder.');

            return;
        }

        // Vincular al cálculo interno de ISM-001 si existe (null si no).
        $evaluacionInterna = EvaluacionPrograma::where('programa_presupuestario_id', $programa->id)
            ->orderByDesc('ejercicio_fiscal')
            ->first();

        // ── (a) Evaluación de DISEÑO concluida ──────────────────────────
        $diseno = EvaluacionExterna::firstOrCreate(
            [
                'programa_presupuestario_id' => $programa->id,
                'tipo' => TipoEvaluacionExterna::DISENO->value,
                'ejercicio_fiscal' => 2026,
            ],
            [
                'evaluador_externo' => 'Colegio de Evaluadores de Oaxaca S.C.',
                'fecha_inicio' => '2026-02-01',
                'fecha_fin' => '2026-05-30',
                'estado' => EstadoEvaluacionExterna::CONCLUIDA->value,
                'evaluacion_programa_id' => $evaluacionInterna?->id,
            ]
        );

        // ── (b) Evaluación de PROCESOS en proceso ───────────────────────
        EvaluacionExterna::firstOrCreate(
            [
                'programa_presupuestario_id' => $programa->id,
                'tipo' => TipoEvaluacionExterna::PROCESOS->value,
                'ejercicio_fiscal' => 2026,
            ],
            [
                'evaluador_externo' => 'Centro de Investigación en Políticas Públicas A.C.',
                'fecha_inicio' => '2026-04-15',
                'fecha_fin' => null,
                'estado' => EstadoEvaluacionExterna::EN_PROCESO->value,
                'evaluacion_programa_id' => null,
            ]
        );

        $enProceso = EvaluacionExterna::where('programa_presupuestario_id', $programa->id)
            ->where('tipo', TipoEvaluacionExterna::PROCESOS->value)
            ->where('ejercicio_fiscal', 2026)
            ->first();

        // ── Informes ────────────────────────────────────────────────────
        $informeDiseno = InformeEvaluacion::firstOrCreate(
            ['evaluacion_externa_id' => $diseno->id],
            [
                'resumen_ejecutivo' => 'La evaluación de diseño del programa Impulso al Sector Mezcalero analizó la consistencia de su Matriz de Indicadores para Resultados. Se identificó una lógica vertical sólida entre los niveles de Componente y Propósito, aunque persisten áreas de mejora en la definición de la población objetivo. El programa cuenta con reglas de operación publicadas y un padrón de beneficiarios verificable.',
                'metodologia' => 'Se aplicó el modelo de términos de referencia del CONEVAL para evaluaciones de diseño, mediante análisis de gabinete de la documentación normativa y entrevistas semiestructuradas con el área responsable. Se revisaron 28 indicadores y sus medios de verificación, así como el árbol del problema y el árbol de objetivos.',
                'conclusiones' => 'El diseño del programa es pertinente respecto al problema público que atiende. Se recomienda fortalecer la cuantificación de la población potencial y atendida, así como precisar los supuestos de los componentes turísticos. El programa es susceptible de escalamiento si se garantiza la suficiencia presupuestaria.',
                'fichas' => 'Ficha técnica de 28 indicadores revisados con su nivel de cumplimiento metodológico. Anexo de hallazgos clasificados por severidad y matriz de recomendaciones priorizadas conforme a los Aspectos Susceptibles de Mejora.',
            ]
        );

        if ($enProceso) {
            InformeEvaluacion::firstOrCreate(
                ['evaluacion_externa_id' => $enProceso->id],
                [
                    'resumen_ejecutivo' => 'Avance preliminar de la evaluación de procesos: se han mapeado los procesos sustantivos de recepción de solicitudes y entrega de subsidios. El levantamiento de campo continúa en curso, por lo que las conclusiones son parciales.',
                ]
            );
        }

        // ── Hallazgos (solo en el informe concluido) ───────────────────
        $hallazgoAlta = Hallazgo::firstOrCreate(
            [
                'informe_evaluacion_id' => $informeDiseno->id,
                'severidad' => SeveridadHallazgo::ALTA->value,
            ],
            [
                'descripcion' => 'La población objetivo no se encuentra cuantificada con una metodología documentada, lo que impide valorar la cobertura efectiva del programa.',
                'evidencia_url' => 'https://evaluaciones.oaxaca.gob.mx/ism-001/2026/hallazgo-poblacion-objetivo.pdf',
            ]
        );

        $hallazgoMedia = Hallazgo::firstOrCreate(
            [
                'informe_evaluacion_id' => $informeDiseno->id,
                'severidad' => SeveridadHallazgo::MEDIA->value,
            ],
            [
                'descripcion' => 'Los supuestos de los componentes turísticos están redactados de forma genérica y no permiten un monitoreo claro de los riesgos externos.',
                'evidencia_url' => null,
            ]
        );

        // ── Recomendaciones ─────────────────────────────────────────────
        $recomAlta = Recomendacion::firstOrCreate(
            [
                'hallazgo_id' => $hallazgoAlta->id,
                'prioridad' => PrioridadRecomendacion::ALTA->value,
            ],
            [
                'descripcion' => 'Elaborar y publicar la metodología de cuantificación de la población objetivo, con fuentes oficiales y periodicidad de actualización definida.',
            ]
        );

        Recomendacion::firstOrCreate(
            [
                'hallazgo_id' => $hallazgoAlta->id,
                'prioridad' => PrioridadRecomendacion::MEDIA->value,
            ],
            [
                'descripcion' => 'Integrar la población objetivo cuantificada al sistema de seguimiento para vincularla con las metas de los indicadores de Propósito.',
            ]
        );

        $recomMedia = Recomendacion::firstOrCreate(
            [
                'hallazgo_id' => $hallazgoMedia->id,
                'prioridad' => PrioridadRecomendacion::MEDIA->value,
            ],
            [
                'descripcion' => 'Reformular los supuestos de los componentes turísticos en términos específicos y verificables conforme a la Metodología de Marco Lógico.',
            ]
        );

        // ── ASM (Aspectos Susceptibles de Mejora) ───────────────────────
        // 2 vinculadas a recomendaciones
        Asm::firstOrCreate(
            [
                'programa_presupuestario_id' => $programa->id,
                'recomendacion_id' => $recomAlta->id,
            ],
            [
                'descripcion_aspecto' => 'Ausencia de metodología documentada para cuantificar la población objetivo.',
                'accion_mejora' => 'Diseñar la metodología de cuantificación de población objetivo y publicarla en el portal institucional.',
                'tipo_plazo' => TipoPlazoAsm::MEDIANO->value,
                'tipo_accion' => TipoAccionAsm::GESTION_INFORMACION->value,
                'responsable_id' => $responsable->id,
                'area_responsable' => 'Dirección de Planeación y Evaluación',
                'fecha_compromiso' => '2026-09-30',
                'porcentaje_avance' => 0,
                'status' => StatusAsm::PENDIENTE->value,
            ]
        );

        Asm::firstOrCreate(
            [
                'programa_presupuestario_id' => $programa->id,
                'recomendacion_id' => $recomMedia->id,
            ],
            [
                'descripcion_aspecto' => 'Supuestos de componentes turísticos redactados de forma genérica.',
                'accion_mejora' => 'Reformular los supuestos de los componentes turísticos en términos específicos y verificables.',
                'tipo_plazo' => TipoPlazoAsm::CORTO->value,
                'tipo_accion' => TipoAccionAsm::NORMATIVO->value,
                'responsable_id' => $responsable->id,
                'area_responsable' => 'Subsecretaría de Turismo',
                'fecha_compromiso' => '2026-08-15',
                'porcentaje_avance' => 40,
                'status' => StatusAsm::EN_PROCESO->value,
            ]
        );

        // 2 legacy sin vínculo a recomendación
        Asm::firstOrCreate(
            [
                'programa_presupuestario_id' => $programa->id,
                'recomendacion_id' => null,
                'descripcion_aspecto' => 'Rezago en la conciliación del padrón de beneficiarios con GeoBase.',
            ],
            [
                'accion_mejora' => 'Ejecutar la conciliación trimestral del padrón de beneficiarios con el sistema GeoBase.',
                'tipo_plazo' => TipoPlazoAsm::CORTO->value,
                'tipo_accion' => TipoAccionAsm::OPERATIVO->value,
                'responsable_id' => $responsable->id,
                'area_responsable' => 'Unidad de Sistemas',
                'fecha_compromiso' => '2026-03-31',
                'fecha_cumplimiento' => '2026-03-20',
                'porcentaje_avance' => 100,
                'status' => StatusAsm::CUMPLIDO->value,
            ]
        );

        Asm::firstOrCreate(
            [
                'programa_presupuestario_id' => $programa->id,
                'recomendacion_id' => null,
                'descripcion_aspecto' => 'Falta de capacitación del personal operativo en la captura de avances.',
            ],
            [
                'accion_mejora' => 'Impartir un taller de capacitación al personal operativo sobre el módulo de seguimiento.',
                'tipo_plazo' => TipoPlazoAsm::MEDIANO->value,
                'tipo_accion' => TipoAccionAsm::GESTION_INFORMACION->value,
                'responsable_id' => $responsable->id,
                'area_responsable' => 'Dirección Administrativa',
                'fecha_compromiso' => '2026-10-31',
                'porcentaje_avance' => 0,
                'status' => StatusAsm::PENDIENTE->value,
            ]
        );

        $this->command?->info('EvaluacionExternaDemoSeeder completado (cadena Evaluación Externa → ASM sobre ISM-001).');
    }
}
