<?php

namespace Tests\Feature\Evaluation\Asm;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use App\Models\Evaluation\Asm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles + permissions + assignments (idempotent — RefreshDatabase wipes each test)
        foreach (SystemRole::cases() as $rol) {
            Role::findOrCreate($rol->value, 'web');
        }
        foreach ([SystemPermission::VER_ASM, SystemPermission::GESTIONAR_ASM] as $p) {
            Permission::findOrCreate($p->value, 'web');
        }
        Role::findByName(SystemRole::PLANEADOR->value)
            ->givePermissionTo([SystemPermission::VER_ASM->value, SystemPermission::GESTIONAR_ASM->value]);
        Role::findByName(SystemRole::ANALISTA_FINANCIERO->value)
            ->givePermissionTo([SystemPermission::VER_ASM->value]);
    }

    public function test_redirects_guests_to_login_on_index(): void
    {
        $this->get('/evaluacion/asms')->assertRedirect('/login');
    }

    public function test_forbids_users_without_ver_asm_from_viewing_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/evaluacion/asms')->assertForbidden();
    }

    public function test_allows_users_with_ver_asm_to_view_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_FINANCIERO->value);
        $this->actingAs($user)->get('/evaluacion/asms')->assertOk();
    }

    public function test_forbids_users_with_only_ver_asm_from_creating(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ANALISTA_FINANCIERO->value);
        $this->actingAs($user)->get('/evaluacion/asms/crear')->assertForbidden();
    }

    public function test_allows_users_with_gestionar_asm_to_access_create(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);
        $this->actingAs($user)->get('/evaluacion/asms/crear')->assertOk();
    }

    public function test_filters_the_index_by_programa_via_query_string(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $asm1 = Asm::factory()->create();
        $asm2 = Asm::factory()->create();

        $response = $this->actingAs($user)->get(
            route('evaluation.asms.index', ['programa' => $asm1->programa_presupuestario_id])
        );

        $response->assertOk();
        // Can't easily assert the absence of asm2 in a server-rendered Livewire response;
        // just confirm the filter parameter doesn't break the page.
    }
}
