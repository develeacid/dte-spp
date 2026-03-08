<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QaTestingSeeder extends Seeder
{
    public function run(): void
    {
        // Guardia de seguridad: NUNCA correr en producción
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar QaTestingSeeder en producción.');
            return;
        }

        $this->command->info('Iniciando carga de datos QA Testing...');

        // Verificar que las URs de DesarrolloSeeder existen
        $se = Team::where('clave_ur', 'SE-001')->firstOrFail();
        $ss = Team::where('clave_ur', 'SS-002')->firstOrFail();

        $this->crearUsuarios($se, $ss);
        $this->crearProgramasYMir($se, $ss);
        $this->crearIndicadores();
        $this->crearMetaPeriodos();
        $this->crearAvances();
        $this->generarResultadosEsperados();

        $this->command->info('QaTestingSeeder completado.');
    }

    private function crearUsuarios(Team $se, Team $ss): void
    {
        $password = Hash::make('LseRdlP0P');

        $usuarios = [
            [
                'name'  => 'QA Admin',
                'email' => 'ele.admin@gmail.com',
                'role'  => SystemRole::ADMIN,
                'teams' => [$se, $ss],
                'team_role' => 'planeador',
            ],
            [
                'name'  => 'QA Planeador',
                'email' => 'ele.planeador@gmail.com',
                'role'  => SystemRole::PLANEADOR,
                'teams' => [$se],
                'team_role' => 'planeador',
            ],
            [
                'name'  => 'QA Operador',
                'email' => 'ele.operador@gmail.com',
                'role'  => SystemRole::OPERADOR,
                'teams' => [$se],
                'team_role' => 'operador',
            ],
            [
                'name'  => 'QA Planeador 2',
                'email' => 'ele.planeador2@gmail.com',
                'role'  => SystemRole::PLANEADOR,
                'teams' => [$se],
                'team_role' => 'planeador',
            ],
            [
                'name'  => 'QA Revisor',
                'email' => 'ele.revisor@gmail.com',
                'role'  => SystemRole::PLANEADOR,
                'teams' => [$ss],
                'team_role' => 'planeador',
            ],
            [
                'name'  => 'QA Operador 2',
                'email' => 'ele.operador2@gmail.com',
                'role'  => SystemRole::OPERADOR,
                'teams' => [$ss],
                'team_role' => 'operador',
            ],
        ];

        $tableRows = [];

        foreach ($usuarios as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => $password,
                ]
            );

            // Asignar rol Spatie (idempotente por naturaleza)
            if (! $user->hasRole($data['role']->value)) {
                $user->assignRole($data['role']->value);
            }

            // Asignar a equipos
            foreach ($data['teams'] as $team) {
                $team->users()->syncWithoutDetaching([
                    $user->id => ['role' => $data['team_role']],
                ]);
            }

            // Establecer equipo activo (el primero de la lista)
            $user->forceFill(['current_team_id' => $data['teams'][0]->id])->save();

            $teamNames = collect($data['teams'])->pluck('clave_ur')->implode(', ');
            $tableRows[] = [$data['name'], $data['email'], $data['role']->value, $teamNames];
        }

        $this->command->table(
            ['Usuario', 'Email', 'Rol', 'UR(s)'],
            $tableRows
        );
        $this->command->info('Contraseña para todos: LseRdlP0P');
    }

    private function crearProgramasYMir(Team $se, Team $ss): void
    {
        // Stub — se implementará en Task 3
    }

    private function crearIndicadores(): void
    {
        // Stub — se implementará en Task 4
    }

    private function crearMetaPeriodos(): void
    {
        // Stub — se implementará en Task 5
    }

    private function crearAvances(): void
    {
        // Stub — se implementará en Task 6
    }

    private function generarResultadosEsperados(): void
    {
        // Stub — se implementará en Task 7
    }
}
