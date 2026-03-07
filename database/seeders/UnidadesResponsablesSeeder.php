<?php

namespace Database\Seeders;

use App\Enums\TipoUnidadResponsable;
use App\Models\Team;
use Illuminate\Database\Seeder;

class UnidadesResponsablesSeeder extends Seeder
{
    public function run(): void
    {
        // UR Sustantiva
        Team::updateOrCreate(
            ['clave_ur' => 'SE-001'],
            [
                'user_id'       => 1, // Se sobreescribe en S1-T6
                'name'          => 'Secretaría de Educación',
                'titular'       => 'Dr. Juan Pérez',
                'tipo_ur'       => TipoUnidadResponsable::SUSTANTIVA,
                'activa'        => true,
                'personal_team' => false,
            ]
        );

        // UR de Apoyo
        Team::updateOrCreate(
            ['clave_ur' => 'SS-002'],
            [
                'user_id'       => 1,
                'name'          => 'Secretaría de Salud',
                'titular'       => 'Dra. María López',
                'tipo_ur'       => TipoUnidadResponsable::APOYO,
                'activa'        => true,
                'personal_team' => false,
            ]
        );

        // UR Inactiva
        Team::updateOrCreate(
            ['clave_ur' => 'SEG-003'],
            [
                'user_id'       => 1,
                'name'          => 'Secretaría de Seguridad',
                'titular'       => 'Lic. Roberto Sánchez',
                'tipo_ur'       => TipoUnidadResponsable::SUSTANTIVA,
                'activa'        => false,
                'personal_team' => false,
            ]
        );
    }
}
