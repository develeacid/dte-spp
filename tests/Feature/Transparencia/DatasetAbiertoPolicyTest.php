<?php

namespace Tests\Feature\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransparenciaPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DatasetAbiertoPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TransparenciaPermissionsSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function makeUserWithRole(string $role): User
    {
        $u = User::factory()->withPersonalTeam()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_planeador_puede_crear_entrega_pero_no_aprobar(): void
    {
        $planeador = $this->makeUserWithRole('planeador');
        $plantilla = DatasetAbierto::factory()->create(['periodo' => null]);
        $entrega = DatasetAbierto::factory()->create(['periodo' => '2026-Q1', 'status' => EstadoDatasetAbierto::REVISION]);

        $this->assertTrue($planeador->can('crearEntrega', $plantilla));
        $this->assertFalse($planeador->can('aprobar', $entrega));
    }

    public function test_rda_puede_aprobar_pero_admin_no(): void
    {
        $rda = $this->makeUserWithRole('responsable_datos_abiertos');
        $admin = $this->makeUserWithRole('admin');
        $entrega = DatasetAbierto::factory()->create(['periodo' => '2026-Q1', 'status' => EstadoDatasetAbierto::REVISION]);

        $this->assertTrue($rda->can('aprobar', $entrega));
        $this->assertFalse($admin->can('aprobar', $entrega));
    }

    public function test_autor_puede_editar_su_borrador(): void
    {
        $autor = $this->makeUserWithRole('planeador');
        $entrega = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::BORRADOR,
            'creado_por' => $autor->id,
        ]);

        $this->assertTrue($autor->can('update', $entrega));
    }

    public function test_planeador_no_puede_editar_borrador_ajeno(): void
    {
        $autor = $this->makeUserWithRole('planeador');
        $otro = $this->makeUserWithRole('planeador');
        $entrega = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::BORRADOR,
            'creado_por' => $autor->id,
        ]);

        $this->assertFalse($otro->can('update', $entrega));
    }

    public function test_rda_puede_editar_cualquier_borrador(): void
    {
        $autor = $this->makeUserWithRole('planeador');
        $rda = $this->makeUserWithRole('responsable_datos_abiertos');
        $entrega = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::BORRADOR,
            'creado_por' => $autor->id,
        ]);

        $this->assertTrue($rda->can('update', $entrega));
    }

    public function test_solo_rda_edita_plantillas(): void
    {
        $planeador = $this->makeUserWithRole('planeador');
        $rda = $this->makeUserWithRole('responsable_datos_abiertos');
        $admin = $this->makeUserWithRole('admin');
        $plantilla = DatasetAbierto::factory()->create(['periodo' => null]);

        $this->assertFalse($planeador->can('editarPlantilla', $plantilla));
        $this->assertTrue($rda->can('editarPlantilla', $plantilla));
        $this->assertFalse($admin->can('editarPlantilla', $plantilla));
    }

    public function test_admin_no_bypassea_acciones_segregadas_pero_si_ver_y_gestionar(): void
    {
        // Test del Gate::before fix — admin sigue teniendo viewAny y create
        // (no segregadas), pero NO aprobar/publicar/retirar/rechazar/editarPlantilla.
        $admin = $this->makeUserWithRole('admin');
        $plantilla = DatasetAbierto::factory()->create(['periodo' => null]);
        $aprobado = DatasetAbierto::factory()->create(['periodo' => '2026-Q1', 'status' => EstadoDatasetAbierto::APROBADO]);
        $publicado = DatasetAbierto::factory()->create(['periodo' => '2026-Q1', 'status' => EstadoDatasetAbierto::PUBLICADO]);

        $this->assertTrue($admin->can('viewAny', DatasetAbierto::class));
        $this->assertTrue($admin->can('create', DatasetAbierto::class));
        $this->assertFalse($admin->can('publicar', $aprobado));
        $this->assertFalse($admin->can('retirar', $publicado));
        $this->assertFalse($admin->can('editarPlantilla', $plantilla));
    }

    public function test_admin_con_permiso_explicito_si_puede_aprobar(): void
    {
        // Documenta el escape hatch: si un dev otorga manualmente
        // aprobar_datos_abiertos al admin (saltándose RolesAndPermissionsSeeder),
        // hasPermissionTo retorna true y la Policy autoriza. Permisos directos
        // ganan sobre la segregación de roles.
        $admin = $this->makeUserWithRole('admin');
        $admin->givePermissionTo('aprobar_datos_abiertos');
        $entrega = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::REVISION,
        ]);

        $this->assertTrue($admin->can('aprobar', $entrega));
    }

    public function test_admin_no_ve_update_ni_enviar_a_revision_si_status_no_borrador(): void
    {
        // Regresión: con la lógica original de SEGREGATED_ABILITIES, admin
        // veía botones state-gated (Editar borrador, Enviar a revisión) en
        // datasets en revision/aprobado/publicado, click resultaba en
        // DomainException. Ahora la Policy es autoritativa para admin sobre
        // DatasetAbierto y los botones se ocultan correctamente.
        $admin = $this->makeUserWithRole('admin');

        $publicado = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);
        $revision = DatasetAbierto::factory()->create([
            'periodo' => '2026-Q1',
            'status' => EstadoDatasetAbierto::REVISION,
        ]);

        $this->assertFalse($admin->can('update', $publicado));
        $this->assertFalse($admin->can('update', $revision));
        $this->assertFalse($admin->can('enviarARevision', $publicado));
        $this->assertFalse($admin->can('enviarARevision', $revision));
    }
}
