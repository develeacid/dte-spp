<?php

namespace Tests\Traits;

use App\Enums\SystemRole;
use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\MetaGastoTrimestral;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PresupuestoPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Jetstream\Team;
use Spatie\Permission\PermissionRegistrar;

trait PresupuestoTestHelpers
{
    protected function seedPermissions(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PresupuestoPermissionsSeeder::class);
    }

    protected function crearUsuarioFinanciero(?Team $team = null): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        if ($team) {
            $team->users()->attach($user);
            $user->switchTeam($team);
        }

        return $user;
    }

    protected function crearUsuarioConRol(string $role, ?Team $team = null): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole($role);

        if ($team) {
            $team->users()->attach($user);
            $user->switchTeam($team);
        }

        return $user;
    }

    protected function crearPrograma(int $teamId, string $clave = 'PT-001', int $ejercicio = 2026): ProgramaPresupuestario
    {
        return ProgramaPresupuestario::create([
            'nombre' => "Programa {$clave}",
            'clave' => $clave,
            'team_id' => $teamId,
            'ejercicio_fiscal' => $ejercicio,
        ]);
    }

    protected function crearPartida(
        int $programaId,
        int $teamId,
        int $userId,
        string $clave = '1000',
        float $aprobado = 1000000,
        ?float $modificado = null,
        int $ejercicio = 2026,
    ): PartidaPresupuestal {
        return PartidaPresupuestal::create([
            'programa_presupuestario_id' => $programaId,
            'clave_partida' => $clave,
            'descripcion' => "Partida {$clave}",
            'monto_aprobado' => $aprobado,
            'monto_modificado' => $modificado,
            'ejercicio_fiscal' => $ejercicio,
            'team_id' => $teamId,
            'registrado_por' => $userId,
        ]);
    }

    protected function crearAvance(
        int $partidaId,
        int $userId,
        int $trimestre = 1,
        float $comprometido = 300000,
        float $devengado = 280000,
        float $pagado = 250000,
    ): AvanceFinanciero {
        return AvanceFinanciero::create([
            'partida_presupuestal_id' => $partidaId,
            'trimestre' => $trimestre,
            'monto_comprometido' => $comprometido,
            'monto_devengado' => $devengado,
            'monto_pagado' => $pagado,
            'registrado_por' => $userId,
        ]);
    }

    protected function crearMeta(
        int $partidaId,
        int $trimestre = 1,
        float $programado = 250000,
    ): MetaGastoTrimestral {
        return MetaGastoTrimestral::create([
            'partida_presupuestal_id' => $partidaId,
            'trimestre' => $trimestre,
            'monto_programado' => $programado,
        ]);
    }

    /**
     * Crea una partida con avance y meta en un trimestre dado.
     */
    protected function crearPartidaConAvance(
        int $programaId,
        int $teamId,
        int $userId,
        int $trimestre = 1,
        float $aprobado = 10000,
        float $pagado = 5000,
        string $clave = '1000',
    ): array {
        $partida = $this->crearPartida($programaId, $teamId, $userId, $clave, $aprobado);

        $avance = $this->crearAvance(
            $partida->id,
            $userId,
            $trimestre,
            comprometido: $pagado * 1.2,
            devengado: $pagado * 1.1,
            pagado: $pagado,
        );

        return [$partida, $avance];
    }
}
