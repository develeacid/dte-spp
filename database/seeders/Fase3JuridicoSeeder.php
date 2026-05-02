<?php

namespace Database\Seeders;

use App\Enums\EstadoValidacionJuridica;
use App\Enums\NivelJerarquiaLegal;
use App\Enums\TipoDocumentoNormativo;
use App\Enums\TipoSustentoLegal;
use App\Models\Juridico\CatalogoOrdenamiento;
use App\Models\Juridico\DocumentoNormativo;
use App\Models\Juridico\SustentoLegalPrograma;
use App\Models\Juridico\ValidacionJuridicaPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class Fase3JuridicoSeeder extends Seeder
{
    /**
     * Mapeo de claves de programa → UR slug para resolver el usuario jurídico.
     */
    private array $claveToSlug = [
        'ISM-001' => 'se',
        'EDU-002' => 'se',
        'EDU-003' => 'se',
        'EDU-004' => 'se',
        'PEC-001' => 'ss',
        'SAL-002' => 'ss',
        'SAL-003' => 'ss',
        'SAL-004' => 'ss',
        'FSP-001' => 'seg',
        'SEG-002' => 'seg',
        'SEG-003P' => 'seg',
        'SEG-004' => 'seg',
        'DDT-001' => 'sectur',
        'TUR-002' => 'sectur',
        'TUR-003' => 'sectur',
        'TUR-004' => 'sectur',
    ];

    /**
     * Distribución de estados de validación por clave de programa.
     */
    private array $validados = ['ISM-001', 'EDU-002', 'PEC-001', 'SAL-002', 'FSP-001', 'SEG-002', 'DDT-001', 'TUR-002'];

    private array $pendientes = ['EDU-003', 'SAL-003', 'SEG-003P', 'TUR-003'];

    private array $sinRegistro = ['EDU-004', 'SEG-004'];

    private array $rechazados = ['SAL-004', 'TUR-004'];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar este seeder en producción.');

            return;
        }

        $this->command->info('Fase 3: Creando datos jurídicos para 16 programas...');

        // Catálogos de ordenamientos
        $loepo = CatalogoOrdenamiento::where('abreviatura', 'LOEPO')->first();
        $leprh = CatalogoOrdenamiento::where('abreviatura', 'LEPRH')->first();

        if (! $loepo || ! $leprh) {
            $this->command->error('Faltan catálogos de ordenamientos (LOEPO/LEPRH). Ejecute CatalogoOrdenamientosSeeder primero.');

            return;
        }

        // Crear archivo dummy una sola vez
        $ropPath = 'documentos-normativos/dummy/rop-qa.pdf';
        Storage::disk('local')->put($ropPath, '%PDF-1.4 dummy ROP QA file');

        $ejercicio = 2025;
        $counters = ['sustentos' => 0, 'validaciones' => 0, 'documentos' => 0];

        // ── VALIDADO (8 programas) ─────────────────────────────────────
        foreach ($this->validados as $clave) {
            $prog = $this->findPrograma($clave);
            if (! $prog) {
                continue;
            }

            $juridicoUser = $this->findJuridicoUser($clave);

            // Sustento 1: FACULTAD_UR
            SustentoLegalPrograma::firstOrCreate(
                ['programa_presupuestario_id' => $prog->id, 'tipo' => TipoSustentoLegal::FACULTAD_UR],
                [
                    'catalogo_ordenamiento_id' => $loepo->id,
                    'ordenamiento' => 'Ley Orgánica del Poder Ejecutivo del Estado de Oaxaca',
                    'articulo' => 'Art. 45, Frac. III',
                    'descripcion' => 'Faculta a la dependencia para ejecutar programas en esta materia',
                    'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL,
                    'vigente' => true,
                    'registrado_por' => $juridicoUser?->id,
                    'team_id' => $prog->team_id,
                ]
            );
            $counters['sustentos']++;

            // Sustento 2: MANDATO_GASTO
            SustentoLegalPrograma::firstOrCreate(
                ['programa_presupuestario_id' => $prog->id, 'tipo' => TipoSustentoLegal::MANDATO_GASTO],
                [
                    'catalogo_ordenamiento_id' => $leprh->id,
                    'ordenamiento' => 'Ley Estatal de Presupuesto y Responsabilidad Hacendaria',
                    'articulo' => 'Art. 83',
                    'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL,
                    'vigente' => true,
                    'registrado_por' => $juridicoUser?->id,
                    'team_id' => $prog->team_id,
                ]
            );
            $counters['sustentos']++;

            // Validación jurídica
            ValidacionJuridicaPrograma::firstOrCreate(
                ['programa_presupuestario_id' => $prog->id, 'ejercicio_fiscal' => $ejercicio],
                [
                    'estado' => EstadoValidacionJuridica::VALIDADO,
                    'tiene_facultad_ur' => true,
                    'tiene_mandato_gasto' => true,
                    'tiene_rop' => null,
                    'validado_por' => $juridicoUser?->id,
                    'validado_at' => now(),
                ]
            );
            $counters['validaciones']++;

            // Documento normativo (ROP)
            DocumentoNormativo::firstOrCreate(
                ['programa_presupuestario_id' => $prog->id, 'nombre' => 'Reglas de Operación '.$prog->clave],
                [
                    'tipo_documento' => TipoDocumentoNormativo::REGLAS_OPERACION,
                    'archivo_path' => $ropPath,
                    'archivo_size' => 1024,
                    'registrado_por' => $juridicoUser?->id,
                    'team_id' => $prog->team_id,
                ]
            );
            $counters['documentos']++;
        }

        // ── PENDIENTE (4 programas) ────────────────────────────────────
        foreach ($this->pendientes as $clave) {
            $prog = $this->findPrograma($clave);
            if (! $prog) {
                continue;
            }

            $juridicoUser = $this->findJuridicoUser($clave);

            // Solo FACULTAD_UR
            SustentoLegalPrograma::firstOrCreate(
                ['programa_presupuestario_id' => $prog->id, 'tipo' => TipoSustentoLegal::FACULTAD_UR],
                [
                    'catalogo_ordenamiento_id' => $loepo->id,
                    'ordenamiento' => 'Ley Orgánica del Poder Ejecutivo del Estado de Oaxaca',
                    'articulo' => 'Art. 32',
                    'descripcion' => 'Facultad parcial registrada, pendiente mandato de gasto',
                    'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL,
                    'vigente' => true,
                    'registrado_por' => $juridicoUser?->id,
                    'team_id' => $prog->team_id,
                ]
            );
            $counters['sustentos']++;

            ValidacionJuridicaPrograma::firstOrCreate(
                ['programa_presupuestario_id' => $prog->id, 'ejercicio_fiscal' => $ejercicio],
                [
                    'estado' => EstadoValidacionJuridica::PENDIENTE,
                    'tiene_facultad_ur' => true,
                    'tiene_mandato_gasto' => false,
                    'tiene_rop' => null,
                ]
            );
            $counters['validaciones']++;
        }

        // ── SIN_REGISTRO (2 programas) ─────────────────────────────────
        // No se crean sustentos ni validaciones para EDU-004 y SEG-004.
        // EstadoConsolidadoService los detectará como sin_registro.

        // ── RECHAZADO (2 programas) ────────────────────────────────────
        foreach ($this->rechazados as $clave) {
            $prog = $this->findPrograma($clave);
            if (! $prog) {
                continue;
            }

            $juridicoUser = $this->findJuridicoUser($clave);

            // Solo FACULTAD_UR (sin MANDATO_GASTO → razón del rechazo)
            SustentoLegalPrograma::firstOrCreate(
                ['programa_presupuestario_id' => $prog->id, 'tipo' => TipoSustentoLegal::FACULTAD_UR],
                [
                    'catalogo_ordenamiento_id' => $loepo->id,
                    'ordenamiento' => 'Ley Orgánica del Poder Ejecutivo del Estado de Oaxaca',
                    'articulo' => 'Art. 27, Frac. I',
                    'descripcion' => 'Facultad registrada pero falta mandato de gasto',
                    'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL,
                    'vigente' => true,
                    'registrado_por' => $juridicoUser?->id,
                    'team_id' => $prog->team_id,
                ]
            );
            $counters['sustentos']++;

            ValidacionJuridicaPrograma::firstOrCreate(
                ['programa_presupuestario_id' => $prog->id, 'ejercicio_fiscal' => $ejercicio],
                [
                    'estado' => EstadoValidacionJuridica::RECHAZADO,
                    'tiene_facultad_ur' => true,
                    'tiene_mandato_gasto' => false,
                    'tiene_rop' => null,
                    'observaciones' => 'Falta sustento de mandato de gasto obligatorio.',
                    'validado_por' => $juridicoUser?->id,
                    'validado_at' => now(),
                ]
            );
            $counters['validaciones']++;
        }

        // ── Resumen ────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->table(
            ['Dato', 'Cantidad'],
            [
                ['Sustentos legales', $counters['sustentos']],
                ['Validaciones jurídicas', $counters['validaciones']],
                ['Documentos normativos', $counters['documentos']],
                ['VALIDADO', 8],
                ['PENDIENTE', 4],
                ['SIN_REGISTRO', 2],
                ['RECHAZADO', 2],
            ]
        );
        $this->command->info('Fase 3 completada.');
    }

    /**
     * Buscar programa por clave.
     */
    private function findPrograma(string $clave): ?ProgramaPresupuestario
    {
        $prog = ProgramaPresupuestario::where('clave', $clave)->first();
        if (! $prog) {
            $this->command->warn("Programa {$clave} no encontrado, saltando.");
        }

        return $prog;
    }

    /**
     * Buscar usuario jurídico de la UR correspondiente al programa.
     */
    private function findJuridicoUser(string $clave): ?User
    {
        $slug = $this->claveToSlug[$clave] ?? null;
        if (! $slug) {
            return null;
        }

        return User::where('email', "juridico.{$slug}@sistema.test")->first();
    }
}
