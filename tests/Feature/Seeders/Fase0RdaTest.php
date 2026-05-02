<?php

namespace Tests\Feature\Seeders;

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\Fase0PrerequisitosSeeder;
use Database\Seeders\TransparenciaPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Fase0RdaTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_crea_usuario_rda_global_con_rol_correcto(): void
    {
        $this->seed(TransparenciaPermissionsSeeder::class);
        $this->seed(Fase0PrerequisitosSeeder::class);

        $rda = User::where('email', 'rda@sistema.test')->first();
        $this->assertNotNull($rda, 'El usuario RDA debe existir tras Fase0PrerequisitosSeeder.');
        $this->assertTrue(
            $rda->hasRole(SystemRole::RESPONSABLE_DATOS_ABIERTOS->value),
            'El usuario RDA debe tener el rol responsable_datos_abiertos.'
        );
        $this->assertNotNull($rda->current_team_id, 'El RDA debe tener un team default asignado.');
    }
}
