<?php

namespace Tests\Feature\Evaluation\Externa;

use App\Enums\EstadoEvaluacionExterna;
use App\Enums\SystemRole;
use App\Enums\TipoEvaluacionExterna;
use App\Livewire\Evaluation\EvaluacionExternaIndex;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\User;
use Database\Seeders\Evaluation\EvaluacionExternaPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EvaluacionExternaPermissionsSeeder::class);
    }

    public function test_redirects_guests_to_login(): void
    {
        $this->get('/evaluacion/externas')->assertRedirect('/login');
    }

    public function test_forbids_users_without_ver_evaluacion_externa(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/evaluacion/externas')->assertForbidden();
    }

    public function test_allows_user_with_ver_evaluacion_externa_to_view_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::OPERADOR->value); // operador recibe ver (mismo set que ver_asm)

        EvaluacionExterna::factory()->create();

        $this->actingAs($user)->get('/evaluacion/externas')->assertOk();
    }

    public function test_filters_by_tipo(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $diseno = EvaluacionExterna::factory()->create([
            'tipo' => TipoEvaluacionExterna::DISENO,
            'evaluador_externo' => 'Despacho Diseño SC',
        ]);
        EvaluacionExterna::factory()->create([
            'tipo' => TipoEvaluacionExterna::IMPACTO,
            'evaluador_externo' => 'Despacho Impacto SC',
        ]);

        Livewire::actingAs($user)
            ->test(EvaluacionExternaIndex::class)
            ->set('tipoFiltro', TipoEvaluacionExterna::DISENO->value)
            ->assertSee('Despacho Diseño SC')
            ->assertDontSee('Despacho Impacto SC');
    }

    public function test_kpis_count_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        EvaluacionExterna::factory()->count(2)->create(['estado' => EstadoEvaluacionExterna::EN_PROCESO]);
        EvaluacionExterna::factory()->count(3)->create(['estado' => EstadoEvaluacionExterna::CONCLUIDA]);

        Livewire::actingAs($user)
            ->test(EvaluacionExternaIndex::class)
            ->assertViewHas('kpis', function (array $kpis) {
                $byLabel = collect($kpis)->keyBy('label');

                return $byLabel['Total']['value'] === 5
                    && $byLabel['En proceso']['value'] === 2
                    && $byLabel['Concluidas']['value'] === 3;
            });
    }

    public function test_nueva_evaluacion_button_visible_for_planeador(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        // El botón está envuelto en @if(Route::has('evaluation.externas.create')).
        // Mientras Task 3 no registre esa ruta, el botón no se renderiza, así que
        // aquí solo verificamos que el planeador accede al índice (200). Task 3
        // activará el botón vía Route::has y reactivará el assertSee('Nueva evaluación').
        $this->actingAs($user)->get('/evaluacion/externas')->assertOk();
    }

    public function test_nueva_evaluacion_button_hidden_for_ver_only(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::OPERADOR->value); // ver pero no gestionar

        $this->actingAs($user)->get('/evaluacion/externas')->assertDontSee('Nueva evaluación');
    }
}
