<?php

namespace Tests\Traits;

use App\Enums\NivelJerarquiaLegal;
use App\Enums\SystemRole;
use App\Enums\TipoSustentoLegal;
use App\Models\Juridico\CatalogoOrdenamiento;
use App\Models\Juridico\SustentoLegalPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\CatalogoOrdenamientosSeeder;
use Database\Seeders\JuridicoPermissionsSeeder;
use Database\Seeders\PresupuestoPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Jetstream\Team;
use Spatie\Permission\PermissionRegistrar;

trait JuridicoTestHelpers
{
    protected function seedJuridicoPermissions(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PresupuestoPermissionsSeeder::class);
        $this->seed(JuridicoPermissionsSeeder::class);
    }

    protected function crearUsuarioJuridico(?Team $team = null): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::ANALISTA_JURIDICO->value);

        if ($team) {
            $team->users()->attach($user);
            $user->switchTeam($team);
        }

        return $user;
    }

    protected function crearPrograma(int $teamId, string $clave = 'PJ-001', int $ejercicio = 2026): ProgramaPresupuestario
    {
        return ProgramaPresupuestario::create([
            'nombre' => "Programa {$clave}",
            'clave' => $clave,
            'team_id' => $teamId,
            'ejercicio_fiscal' => $ejercicio,
        ]);
    }

    protected function crearSustento(
        int $programaId,
        int $teamId,
        int $userId,
        TipoSustentoLegal $tipo = TipoSustentoLegal::FACULTAD_UR,
    ): SustentoLegalPrograma {
        return SustentoLegalPrograma::create([
            'programa_presupuestario_id' => $programaId,
            'tipo' => $tipo,
            'ordenamiento' => 'Ley Orgánica del Poder Ejecutivo del Estado de Oaxaca',
            'articulo' => 'Art. 45',
            'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL,
            'vigente' => true,
            'registrado_por' => $userId,
            'team_id' => $teamId,
        ]);
    }
}
