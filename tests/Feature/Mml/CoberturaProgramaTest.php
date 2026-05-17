<?php

namespace Tests\Feature\Mml;

use App\Enums\SystemRole;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\CoberturaPrograma;
use App\Models\Mml\MirNivel;
use App\Models\Mml\PoblacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CoberturaProgramaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PadronPermissionsSeeder::class);

        // The MirNivelGeoBaseObserver dispatches a sync job whenever a
        // Componente is created on a programa with padron_geobase_activo=true;
        // these tests don't care about that side effect, so we capture them.
        Queue::fake();
        Cache::flush();
    }

    private function programaConComponente(bool $padronActivo = true): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => $padronActivo,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'Componente C1',
            'orden' => 1,
        ]);

        return $programa;
    }

    private function userPlaneador(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        return $user;
    }

    public function test_estado_inactivo_si_programa_sin_padron_activo(): void
    {
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'inactivo')
            ->assertSee('El padrón de este programa no está activo en GeoBase');
    }

    public function test_estado_ok_renderiza_kpis_y_tabla_municipios(): void
    {
        Http::fake([
            '*/programs/*/coverage*' => Http::response([
                'spp_program_id' => 1,
                'program_name' => 'Programa Demo',
                'total_enrollments' => 250,
                'total_beneficiaries' => 200,
                'by_status' => ['aprobado' => 180, 'solicitado' => 70],
                'by_municipality' => [
                    ['municipality' => 'Oaxaca de Juárez', 'count' => 120],
                    ['municipality' => 'San Pablo', 'count' => 80],
                ],
            ], 200),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'ok')
            ->assertSeeHtml('text-emerald-700">200</p>')   // KPI total_beneficiaries
            ->assertSeeHtml('text-blue-700">250</p>')      // KPI total_enrollments
            ->assertSee('aprobado')                         // chip label, no collision
            ->assertSeeHtml('<strong class="ml-1">180</strong>')  // status count
            ->assertSee('Oaxaca de Juárez')                 // municipality name, no collision
            ->assertSeeHtml('<td class="px-4 py-2 text-sm text-gray-900 text-right">120</td>') // municipality count
            ->assertDontSee('Aún no hay beneficiarios');
    }

    public function test_estado_vacio_si_total_beneficiaries_es_cero(): void
    {
        Http::fake([
            '*/programs/*/coverage*' => Http::response([
                'spp_program_id' => 1,
                'program_name' => 'Programa Demo',
                'total_enrollments' => 0,
                'total_beneficiaries' => 0,
                'by_status' => [],
                'by_municipality' => [],
            ], 200),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'vacio')
            ->assertSee('Aún no hay beneficiarios inscritos')
            ->assertDontSee('Total beneficiarios');
    }

    public function test_estado_no_registrado_si_geobase_devuelve_404(): void
    {
        Http::fake([
            '*/programs/*/coverage*' => Http::response(['message' => 'Not Found'], 404),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'no_registrado')
            ->assertSee('El programa no está registrado en GeoBase')
            ->assertSee('geobase:register-program');
    }

    public function test_estado_error_si_geobase_devuelve_5xx_con_mensaje_sanitizado(): void
    {
        Http::fake([
            '*/programs/*/coverage*' => Http::response('<html>fatal stack trace</html>', 500),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'error')
            ->assertSee('GeoBase no está disponible en este momento')
            ->assertDontSee('fatal stack trace')
            ->assertDontSee('<html>');
    }

    public function test_muestra_supuestos_del_proposito_y_componentes(): void
    {
        Http::fake([
            '*/programs/*/coverage*' => Http::response([
                'total_enrollments' => 1, 'total_beneficiaries' => 1,
                'by_status' => [], 'by_municipality' => [],
            ], 200),
        ]);

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO,
            'resumen_narrativo' => 'Propósito del programa',
            'supuestos' => 'Las condiciones climáticas se mantienen estables',
            'orden' => 1,
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'Componente C1',
            'supuestos' => 'Los productores asisten a capacitaciones',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('Supuestos del MIR')
            ->assertSee('Propósito')
            ->assertSee('Las condiciones climáticas se mantienen estables')
            ->assertSee('Componente C1')
            ->assertSee('Los productores asisten a capacitaciones');
    }

    public function test_muestra_mensaje_si_sin_supuestos_definidos(): void
    {
        Http::fake([
            '*/programs/*/coverage*' => Http::response([
                'total_enrollments' => 1, 'total_beneficiaries' => 1,
                'by_status' => [], 'by_municipality' => [],
            ], 200),
        ]);

        $programa = $this->programaConComponente(); // crea componente sin supuestos

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('Supuestos del MIR')
            ->assertSee('Sin Supuestos definidos');
    }

    public function test_muestra_timestamp_de_ultima_consulta_en_estado_ok(): void
    {
        Http::fake([
            '*/programs/*/coverage*' => Http::response([
                'total_enrollments' => 1, 'total_beneficiaries' => 1,
                'by_status' => [], 'by_municipality' => [],
            ], 200),
        ]);

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('Consultado:')
            ->assertSet('consultadoAt', fn ($value) => is_string($value) && $value !== '');
    }

    /**
     * Helper: fakes 3 GET /programs/{id}/coverage responses by querystring.
     * - all-time (sin period): $allTime
     * - period=Q actual: $qActual
     * - period=Q anterior: $qAnterior
     * Cada uno acepta arrays con campos {total_beneficiaries, total_enrollments, by_municipality}.
     */
    private function fakeCoverageWithHistory(array $allTime, array $qActual, array $qAnterior): void
    {
        $now = now();
        $qActualStr = sprintf('%d-Q%d', $now->year, (int) ceil($now->month / 3));
        $prev = $now->copy()->subMonthsNoOverflow(3);
        $qAnteriorStr = sprintf('%d-Q%d', $prev->year, (int) ceil($prev->month / 3));

        Http::fake(function ($request) use ($allTime, $qActual, $qAnterior, $qActualStr, $qAnteriorStr) {
            $url = $request->url();
            $base = ['by_status' => [], 'by_municipality' => []];
            if (str_contains($url, 'period='.$qAnteriorStr)) {
                return Http::response(array_merge($base, $qAnterior), 200);
            }
            if (str_contains($url, 'period='.$qActualStr)) {
                return Http::response(array_merge($base, $qActual), 200);
            }

            return Http::response(array_merge($base, $allTime), 200);
        });
    }

    public function test_alerta_baja_trimestre_amarillo_si_drop_entre_10_y_25(): void
    {
        // Q actual = 85, Q anterior = 100. Drop 15%.
        $this->fakeCoverageWithHistory(
            allTime: ['total_beneficiaries' => 200, 'total_enrollments' => 250],
            qActual: ['total_beneficiaries' => 85, 'total_enrollments' => 85],
            qAnterior: ['total_beneficiaries' => 100, 'total_enrollments' => 100],
        );

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'ok')
            ->assertSee('Alertas de cobertura')
            ->assertSee('cayó 15% vs trimestre anterior')
            ->assertSeeHtml('bg-amber-50'); // tailwind amarillo
    }

    public function test_alerta_baja_trimestre_rojo_si_drop_mayor_25(): void
    {
        $this->fakeCoverageWithHistory(
            allTime: ['total_beneficiaries' => 200, 'total_enrollments' => 250],
            qActual: ['total_beneficiaries' => 60, 'total_enrollments' => 60],
            qAnterior: ['total_beneficiaries' => 100, 'total_enrollments' => 100],
        );

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('cayó 40% vs trimestre anterior')
            ->assertSeeHtml('bg-red-50'); // tailwind rojo
    }

    public function test_alerta_meta_amarillo_si_cobertura_entre_25_y_50(): void
    {
        $this->fakeCoverageWithHistory(
            allTime: ['total_beneficiaries' => 400, 'total_enrollments' => 400],
            qActual: ['total_beneficiaries' => 100, 'total_enrollments' => 100],
            qAnterior: ['total_beneficiaries' => 100, 'total_enrollments' => 100],
        );

        $programa = $this->programaConComponente();
        PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'Personas',
            'referencia_cantidad' => 5000,
            'potencial_cantidad' => 2000,
            'objetivo_cantidad' => 1000,
            'anio_ejercicio' => 2026,
        ]);

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('40% de la meta')
            ->assertSeeHtml('bg-amber-50');
    }

    public function test_alerta_meta_rojo_si_cobertura_menor_25(): void
    {
        $this->fakeCoverageWithHistory(
            allTime: ['total_beneficiaries' => 100, 'total_enrollments' => 100],
            qActual: ['total_beneficiaries' => 100, 'total_enrollments' => 100],
            qAnterior: ['total_beneficiaries' => 100, 'total_enrollments' => 100],
        );

        $programa = $this->programaConComponente();
        PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'Personas',
            'referencia_cantidad' => 5000,
            'potencial_cantidad' => 2000,
            'objetivo_cantidad' => 1000,
            'anio_ejercicio' => 2026,
        ]);

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('10% de la meta')
            ->assertSeeHtml('bg-red-50');
    }

    public function test_alerta_municipios_con_drop_listados(): void
    {
        $this->fakeCoverageWithHistory(
            allTime: ['total_beneficiaries' => 200, 'total_enrollments' => 250],
            qActual: ['total_beneficiaries' => 80, 'total_enrollments' => 80, 'by_municipality' => [
                ['municipality' => 'Oaxaca de Juárez', 'count' => 50],
                ['municipality' => 'San Pablo', 'count' => 10],
            ]],
            qAnterior: ['total_beneficiaries' => 100, 'total_enrollments' => 100, 'by_municipality' => [
                ['municipality' => 'Oaxaca de Juárez', 'count' => 50],
                ['municipality' => 'San Pablo', 'count' => 40],
            ]],
        );

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('Municipios con caída')
            ->assertSee('San Pablo')    // cayó 40 → 10 (-75%)
            ->assertDontSeeHtml('Oaxaca de Juárez</li>'); // sin caída, no debe listarse
    }

    public function test_sin_alertas_si_todo_estable(): void
    {
        $this->fakeCoverageWithHistory(
            allTime: ['total_beneficiaries' => 800, 'total_enrollments' => 800],
            qActual: ['total_beneficiaries' => 100, 'total_enrollments' => 100],
            qAnterior: ['total_beneficiaries' => 95, 'total_enrollments' => 95],
        );

        $programa = $this->programaConComponente();
        PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'Personas',
            'referencia_cantidad' => 5000,
            'potencial_cantidad' => 2000,
            'objetivo_cantidad' => 1000,
            'anio_ejercicio' => 2026,
        ]);

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSee('Sin alertas activas');
    }

    public function test_no_explota_si_q_anterior_devuelve_404(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'period=')) {
                $prev = now()->copy()->subMonthsNoOverflow(3);
                $qAnteriorStr = sprintf('%d-Q%d', $prev->year, (int) ceil($prev->month / 3));
                if (str_contains($url, 'period='.$qAnteriorStr)) {
                    return Http::response(['message' => 'Not Found'], 404);
                }

                return Http::response(['total_beneficiaries' => 50, 'total_enrollments' => 50, 'by_status' => [], 'by_municipality' => []], 200);
            }

            return Http::response(['total_beneficiaries' => 100, 'total_enrollments' => 100, 'by_status' => [], 'by_municipality' => []], 200);
        });

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'ok')
            ->assertDontSee('cayó'); // sin Q-1 no se puede calcular drop
    }

    public function test_default_es_todo_el_periodo_y_periodos_disponibles_incluye_q_actual_y_3_previos(): void
    {
        Http::fake([
            '*/programs/*/coverage*' => Http::response([
                'total_beneficiaries' => 200, 'total_enrollments' => 250,
                'by_status' => [], 'by_municipality' => [],
            ], 200),
        ]);
        Http::preventStrayRequests();

        $programa = $this->programaConComponente();

        $component = Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('periodoSeleccionado', null)
            ->assertSee('Todo el periodo');

        $disponibles = $component->get('periodosDisponibles');
        $this->assertCount(4, $disponibles, 'Q actual + 3 previos = 4');

        $now = now();
        $qActualStr = sprintf('%d-Q%d', $now->year, (int) ceil($now->month / 3));
        $this->assertSame($qActualStr, $disponibles[0], 'Primer item debe ser Q actual');
    }

    public function test_seleccionar_periodo_refetcha_coverage_con_filter_y_recalcula_alertas_para_ese_q(): void
    {
        // Q-actual = 200, Q-anterior = 100 → all-time NO debería disparar alerta (sin drop hoy);
        // pero cuando seleccionamos Q-anterior (que tiene 100 vs su Q-1 con 1000) → debe disparar alerta roja del Q anterior.
        $now = now();
        $qActualStr = sprintf('%d-Q%d', $now->year, (int) ceil($now->month / 3));
        $prev = $now->copy()->subMonthsNoOverflow(3);
        $qAnteriorStr = sprintf('%d-Q%d', $prev->year, (int) ceil($prev->month / 3));
        $prev2 = $now->copy()->subMonthsNoOverflow(6);
        $qDosAtrasStr = sprintf('%d-Q%d', $prev2->year, (int) ceil($prev2->month / 3));

        Http::fake(function ($request) use ($qActualStr, $qAnteriorStr, $qDosAtrasStr) {
            $url = $request->url();
            $base = ['by_status' => [], 'by_municipality' => []];
            // Más específico primero (el match es por substring, evitar colisiones).
            if (str_contains($url, 'period='.$qDosAtrasStr)) {
                return Http::response(array_merge($base, ['total_beneficiaries' => 1000, 'total_enrollments' => 1000]), 200);
            }
            if (str_contains($url, 'period='.$qAnteriorStr)) {
                return Http::response(array_merge($base, ['total_beneficiaries' => 100, 'total_enrollments' => 100]), 200);
            }
            if (str_contains($url, 'period='.$qActualStr)) {
                return Http::response(array_merge($base, ['total_beneficiaries' => 200, 'total_enrollments' => 200]), 200);
            }

            // all-time (sin period)
            return Http::response(array_merge($base, ['total_beneficiaries' => 300, 'total_enrollments' => 300]), 200);
        });
        Http::preventStrayRequests();

        $programa = $this->programaConComponente();

        $component = Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('estado', 'ok')
            ->assertSet('periodoSeleccionado', null)
            ->assertSet('coverage.total_beneficiaries', 300) // all-time
            ->assertDontSee('cayó'); // Q-actual=200 vs Q-1=100 sube, sin alerta

        // Selecciono Q-anterior. Ahora "actual" para alertas es qAnteriorStr (=100) y "anterior" es qDosAtrasStr (=1000).
        // Drop = (1000-100)/1000 = 90% → alerta roja.
        $component->call('seleccionarPeriodo', $qAnteriorStr)
            ->assertSet('periodoSeleccionado', $qAnteriorStr)
            ->assertSet('coverage.total_beneficiaries', 100)
            ->assertSee('cayó 90% vs trimestre anterior')
            ->assertSeeHtml('bg-red-50');

        // Vuelvo a all-time: coverage regresa a 300, alerta desaparece.
        $component->call('seleccionarPeriodo', null)
            ->assertSet('periodoSeleccionado', null)
            ->assertSet('coverage.total_beneficiaries', 300)
            ->assertDontSee('cayó');
    }

    public function test_seleccionar_periodo_con_valor_invalido_es_noop(): void
    {
        Http::fake([
            '*/programs/*/coverage*' => Http::response([
                'total_beneficiaries' => 10, 'total_enrollments' => 10,
                'by_status' => [], 'by_municipality' => [],
            ], 200),
        ]);
        Http::preventStrayRequests();

        $programa = $this->programaConComponente();

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertSet('periodoSeleccionado', null)
            ->call('seleccionarPeriodo', '2099-Q9')
            ->assertSet('periodoSeleccionado', null);
    }

    public function test_no_alerta_meta_si_sin_poblacion_objetivo(): void
    {
        $this->fakeCoverageWithHistory(
            allTime: ['total_beneficiaries' => 10, 'total_enrollments' => 10],
            qActual: ['total_beneficiaries' => 5, 'total_enrollments' => 5],
            qAnterior: ['total_beneficiaries' => 5, 'total_enrollments' => 5],
        );

        $programa = $this->programaConComponente();
        // Sin PoblacionPrograma asociada

        Livewire::actingAs($this->userPlaneador())
            ->test(CoberturaPrograma::class, ['programa' => $programa])
            ->assertDontSee('de la meta');
    }
}
