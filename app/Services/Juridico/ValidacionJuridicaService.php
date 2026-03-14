<?php

namespace App\Services\Juridico;

use App\Enums\EstadoValidacionJuridica;
use App\Enums\TipoDocumentoNormativo;
use App\Enums\TipoSustentoLegal;
use App\Models\Juridico\DocumentoNormativo;
use App\Models\Juridico\SustentoLegalPrograma;
use App\Models\Juridico\ValidacionJuridicaPrograma;
use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Collection;

class ValidacionJuridicaService
{
    /**
     * Recalcula el checklist de validación jurídica de un programa.
     */
    public function recalcularChecklist(int $programaId, int $ejercicio): ValidacionJuridicaPrograma
    {
        $validacion = ValidacionJuridicaPrograma::firstOrCreate(
            ['programa_presupuestario_id' => $programaId, 'ejercicio_fiscal' => $ejercicio],
            ['estado' => EstadoValidacionJuridica::PENDIENTE],
        );

        $programa = ProgramaPresupuestario::find($programaId);

        // Facultad de la UR: existe sustento tipo FACULTAD_UR vigente
        $validacion->tiene_facultad_ur = SustentoLegalPrograma::where('programa_presupuestario_id', $programaId)
            ->where('tipo', TipoSustentoLegal::FACULTAD_UR)
            ->where('vigente', true)
            ->exists();

        // Mandato de gasto: existe sustento tipo MANDATO_GASTO vigente
        $validacion->tiene_mandato_gasto = SustentoLegalPrograma::where('programa_presupuestario_id', $programaId)
            ->where('tipo', TipoSustentoLegal::MANDATO_GASTO)
            ->where('vigente', true)
            ->exists();

        // ROP: depende de si el programa requiere ROP
        if ($programa && $programa->requiere_rop) {
            $validacion->tiene_rop = DocumentoNormativo::where('programa_presupuestario_id', $programaId)
                ->where('tipo_documento', TipoDocumentoNormativo::REGLAS_OPERACION)
                ->where('verificado', true)
                ->exists();
        } else {
            $validacion->tiene_rop = null; // No aplica
        }

        $validacion->save();

        return $validacion;
    }

    /**
     * Valida un programa jurídicamente.
     *
     * @throws \DomainException si el checklist no está completo
     */
    public function validar(int $programaId, int $ejercicio, int $validadorId, ?string $observaciones = null): ValidacionJuridicaPrograma
    {
        $validacion = $this->recalcularChecklist($programaId, $ejercicio);

        if (! $validacion->checklist_completo) {
            throw new \DomainException(
                'No se puede validar: faltan items del checklist ('
                .implode(', ', $validacion->items_pendientes).')'
            );
        }

        $validacion->update([
            'estado' => EstadoValidacionJuridica::VALIDADO,
            'validado_por' => $validadorId,
            'validado_at' => now(),
            'observaciones' => $observaciones,
        ]);

        return $validacion;
    }

    /**
     * Rechaza la validación jurídica con observaciones obligatorias.
     */
    public function rechazar(int $programaId, int $ejercicio, int $validadorId, string $observaciones): ValidacionJuridicaPrograma
    {
        $validacion = ValidacionJuridicaPrograma::where('programa_presupuestario_id', $programaId)
            ->where('ejercicio_fiscal', $ejercicio)
            ->firstOrFail();

        $validacion->update([
            'estado' => EstadoValidacionJuridica::RECHAZADO,
            'validado_por' => $validadorId,
            'validado_at' => now(),
            'observaciones' => $observaciones,
        ]);

        return $validacion;
    }

    /**
     * Verifica si un programa puede abrir su T1 de seguimiento.
     * Solo bloquea si requiere_rop = true Y no tiene ROP verificada.
     */
    public function puedeAbrirSeguimiento(int $programaId): bool
    {
        $programa = ProgramaPresupuestario::find($programaId);

        if (! $programa || ! $programa->requiere_rop) {
            return true;
        }

        return DocumentoNormativo::where('programa_presupuestario_id', $programaId)
            ->where('tipo_documento', TipoDocumentoNormativo::REGLAS_OPERACION)
            ->where('verificado', true)
            ->exists();
    }

    /**
     * Programas pendientes de validación jurídica para un team.
     */
    public function programasPendientes(int $teamId, int $ejercicio): Collection
    {
        return ProgramaPresupuestario::where('team_id', $teamId)
            ->where('ejercicio_fiscal', $ejercicio)
            ->whereDoesntHave('validacionJuridica', function ($query) {
                $query->where('estado', EstadoValidacionJuridica::VALIDADO);
            })
            ->get();
    }

    /**
     * Programas con documentos de vigencia expirada.
     */
    public function programasConVigenciaExpirada(int $teamId): Collection
    {
        $programaIds = DocumentoNormativo::where('team_id', $teamId)
            ->whereNotNull('fecha_vigencia')
            ->where('fecha_vigencia', '<', now())
            ->pluck('programa_presupuestario_id')
            ->unique();

        return ProgramaPresupuestario::whereIn('id', $programaIds)->get();
    }
}
