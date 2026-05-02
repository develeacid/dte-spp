<?php

namespace Database\Seeders;

use App\Enums\EstadoValidacionJuridica;
use App\Enums\NivelJerarquiaLegal;
use App\Enums\TipoSustentoLegal;
use App\Models\Juridico\CatalogoOrdenamiento;
use App\Models\Juridico\SustentoLegalPrograma;
use App\Models\Juridico\ValidacionJuridicaPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Seeder;

class JuridicoTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        $teamId = $user->currentTeam?->id ?? 1;
        $ejercicio = config('presupuesto.ejercicio_default');

        $programas = ProgramaPresupuestario::where('team_id', $teamId)
            ->ejercicio($ejercicio)
            ->limit(5)
            ->get();

        if ($programas->isEmpty()) {
            return;
        }

        $loepo = CatalogoOrdenamiento::where('abreviatura', 'LOEPO')->first();
        $leprh = CatalogoOrdenamiento::where('abreviatura', 'LEPRH')->first();

        // Programa 1: Sustento completo y validado
        if ($programas->has(0)) {
            $p = $programas[0];
            SustentoLegalPrograma::updateOrCreate(
                ['programa_presupuestario_id' => $p->id, 'tipo' => TipoSustentoLegal::FACULTAD_UR],
                [
                    'catalogo_ordenamiento_id' => $loepo?->id,
                    'ordenamiento' => 'Ley Orgánica del Poder Ejecutivo del Estado de Oaxaca',
                    'articulo' => 'Art. 45, Frac. III',
                    'descripcion' => 'Faculta a la dependencia para ejecutar programas en esta materia',
                    'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL,
                    'vigente' => true,
                    'registrado_por' => $user->id,
                    'team_id' => $teamId,
                ]
            );
            SustentoLegalPrograma::updateOrCreate(
                ['programa_presupuestario_id' => $p->id, 'tipo' => TipoSustentoLegal::MANDATO_GASTO],
                [
                    'catalogo_ordenamiento_id' => $leprh?->id,
                    'ordenamiento' => 'Ley Estatal de Presupuesto y Responsabilidad Hacendaria',
                    'articulo' => 'Art. 83',
                    'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL,
                    'vigente' => true,
                    'registrado_por' => $user->id,
                    'team_id' => $teamId,
                ]
            );
            ValidacionJuridicaPrograma::updateOrCreate(
                ['programa_presupuestario_id' => $p->id, 'ejercicio_fiscal' => $ejercicio],
                [
                    'estado' => EstadoValidacionJuridica::VALIDADO,
                    'tiene_facultad_ur' => true,
                    'tiene_mandato_gasto' => true,
                    'tiene_rop' => null,
                    'validado_por' => $user->id,
                    'validado_at' => now(),
                ]
            );
        }

        // Programa 2: Sustento parcial (solo facultad)
        if ($programas->has(1)) {
            $p = $programas[1];
            SustentoLegalPrograma::updateOrCreate(
                ['programa_presupuestario_id' => $p->id, 'tipo' => TipoSustentoLegal::FACULTAD_UR],
                [
                    'ordenamiento' => 'Ley Orgánica del Poder Ejecutivo del Estado de Oaxaca',
                    'articulo' => 'Art. 32',
                    'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL,
                    'vigente' => true,
                    'registrado_por' => $user->id,
                    'team_id' => $teamId,
                ]
            );
            ValidacionJuridicaPrograma::updateOrCreate(
                ['programa_presupuestario_id' => $p->id, 'ejercicio_fiscal' => $ejercicio],
                [
                    'estado' => EstadoValidacionJuridica::PENDIENTE,
                    'tiene_facultad_ur' => true,
                    'tiene_mandato_gasto' => false,
                    'tiene_rop' => null,
                ]
            );
        }

        // Programa 3: Sin sustento (sin_registro)
        if ($programas->has(2)) {
            // No crear nada — queda como sin_registro
        }

        // Programa 4: Rechazado
        if ($programas->has(3)) {
            $p = $programas[3];
            ValidacionJuridicaPrograma::updateOrCreate(
                ['programa_presupuestario_id' => $p->id, 'ejercicio_fiscal' => $ejercicio],
                [
                    'estado' => EstadoValidacionJuridica::RECHAZADO,
                    'tiene_facultad_ur' => true,
                    'tiene_mandato_gasto' => false,
                    'tiene_rop' => null,
                    'observaciones' => 'Falta sustento de mandato de gasto. Favor de agregar artículo aplicable.',
                    'validado_por' => $user->id,
                    'validado_at' => now(),
                ]
            );
        }
    }
}
