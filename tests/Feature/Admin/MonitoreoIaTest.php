<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MonitoreoIa;
use App\Models\LlmBudget;
use App\Models\LlmLog;
use App\Models\User;
use App\Notifications\LlmBudgetAlertNotification;
use App\Services\Llm\LlmBudgetService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class MonitoreoIaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->withPersonalTeam()->create();
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->withPersonalTeam()->create();
        $this->regularUser->assignRole('operador');
    }

    public function test_dashboard_renders_with_metrics(): void
    {
        // Create some LLM logs
        LlmLog::create([
            'user_id' => $this->admin->id,
            'method' => 'suggest',
            'prompt_text' => 'test prompt',
            'status' => 'success',
            'total_tokens' => 500,
            'prompt_tokens' => 200,
            'completion_tokens' => 300,
            'duration_ms' => 1500,
            'cost_usd' => 0.000210,
        ]);

        LlmLog::create([
            'user_id' => $this->admin->id,
            'method' => 'validate',
            'prompt_text' => 'test validate',
            'status' => 'success',
            'total_tokens' => 300,
            'prompt_tokens' => 100,
            'completion_tokens' => 200,
            'duration_ms' => 800,
            'cost_usd' => 0.000135,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(MonitoreoIa::class)
            ->assertSee('Monitoreo de IA')
            ->assertSee('Total llamadas')
            ->assertSee('Total tokens')
            ->assertSee('Costo estimado')
            ->assertSee('Tiempo promedio')
            ->assertSee('Tasa de error');
    }

    public function test_usage_by_type_shows_correct_grouping(): void
    {
        LlmLog::create([
            'user_id' => $this->admin->id,
            'method' => 'suggest',
            'prompt_text' => 'test 1',
            'status' => 'success',
            'total_tokens' => 100,
            'cost_usd' => 0.000050,
        ]);

        LlmLog::create([
            'user_id' => $this->admin->id,
            'method' => 'suggest',
            'prompt_text' => 'test 2',
            'status' => 'success',
            'total_tokens' => 200,
            'cost_usd' => 0.000100,
        ]);

        LlmLog::create([
            'user_id' => $this->admin->id,
            'method' => 'validate',
            'prompt_text' => 'test 3',
            'status' => 'success',
            'total_tokens' => 150,
            'cost_usd' => 0.000075,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(MonitoreoIa::class)
            ->assertSee('suggest')
            ->assertSee('validate')
            ->assertSee('Uso por tipo de operacion');
    }

    public function test_budget_alert_triggers_when_threshold_exceeded(): void
    {
        Notification::fake();

        $budget = LlmBudget::create([
            'scope' => 'global',
            'scope_id' => null,
            'month' => now()->startOfMonth()->toDateString(),
            'budget_usd' => 1.00,
            'spent_usd' => 0.79,
            'alert_threshold' => 0.80,
        ]);

        // Create a log that will push spending over threshold
        $log = LlmLog::create([
            'user_id' => $this->admin->id,
            'method' => 'suggest',
            'prompt_text' => 'test',
            'status' => 'success',
            'prompt_tokens' => 10000,
            'completion_tokens' => 5000,
            'total_tokens' => 15000,
        ]);

        $service = new LlmBudgetService();
        $service->checkAndAlert($log);

        $budget->refresh();

        // Budget should have been updated with additional cost
        $this->assertGreaterThan(0.79, (float) $budget->spent_usd);

        // Alert should have been sent
        if ($budget->isOverThreshold()) {
            $this->assertNotNull($budget->alerted_at);
            Notification::assertSentTo($this->admin, LlmBudgetAlertNotification::class);
        }
    }

    public function test_cleanup_command_respects_days_flag(): void
    {
        // Create old log with explicit old date
        $oldLog = LlmLog::create([
            'user_id' => $this->admin->id,
            'method' => 'suggest',
            'prompt_text' => 'old log',
            'status' => 'success',
        ]);
        // Force-update the created_at via DB to avoid model timestamp interference
        \Illuminate\Support\Facades\DB::table('llm_logs')
            ->where('id', $oldLog->id)
            ->update(['created_at' => now()->subDays(100)]);

        // Create recent log
        LlmLog::create([
            'user_id' => $this->admin->id,
            'method' => 'suggest',
            'prompt_text' => 'recent log',
            'status' => 'success',
        ]);

        $this->assertEquals(2, LlmLog::count());

        $this->artisan('llm:cleanup-logs', ['--days' => 90])
            ->assertSuccessful();

        // Old log deleted, recent kept
        $this->assertEquals(1, LlmLog::count());
        $this->assertEquals('recent log', LlmLog::first()->prompt_text);
    }

    public function test_403_without_administrar_usuarios_permission(): void
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('admin.monitoreo-ia'));
        $response->assertStatus(403);
    }

    public function test_budget_percent_used_calculation(): void
    {
        $budget = new LlmBudget([
            'scope' => 'global',
            'scope_id' => null,
            'month' => now()->startOfMonth()->toDateString(),
            'budget_usd' => 100.00,
            'spent_usd' => 75.000000,
            'alert_threshold' => 0.80,
        ]);

        $this->assertEquals(75.00, $budget->percentUsed());
        $this->assertFalse($budget->isOverThreshold());

        $budget->spent_usd = 80.000000;
        $this->assertEquals(80.00, $budget->percentUsed());
        $this->assertTrue($budget->isOverThreshold());

        $budget->spent_usd = 0;
        $budget->budget_usd = 0;
        $this->assertEquals(0, $budget->percentUsed());
        $this->assertFalse($budget->isOverThreshold());
    }

    public function test_cleanup_dry_run_does_not_delete(): void
    {
        LlmLog::create([
            'user_id' => $this->admin->id,
            'method' => 'suggest',
            'prompt_text' => 'old log',
            'status' => 'success',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        $this->assertEquals(1, LlmLog::count());

        $this->artisan('llm:cleanup-logs', ['--days' => 90, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertEquals(1, LlmLog::count());
    }

    public function test_cost_estimation(): void
    {
        $log = new LlmLog([
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
        ]);

        $cost = LlmBudgetService::estimateCost($log);

        // 1000/1M * 0.15 = 0.000150
        // 500/1M * 0.60 = 0.000300
        // Total = 0.000450
        $this->assertEquals(0.00045, $cost);
    }

    public function test_probar_conexion_returns_success_result(): void
    {
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [['embedding' => array_fill(0, 1536, 0.1)]],
                'usage' => ['prompt_tokens' => 2, 'total_tokens' => 2],
                'model' => 'text-embedding-ada-002',
            ], 200),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(MonitoreoIa::class)
            ->call('probarConexion');

        $component->assertSet('resultadoConexion.estado', 'ok');
        $component->assertSet('resultadoConexion.modelo', 'text-embedding-ada-002');
        $component->assertSet('resultadoConexion.dimensiones', 1536);
        $this->assertArrayHasKey('latencia_ms', $component->get('resultadoConexion'));
        $this->assertArrayHasKey('api_key_preview', $component->get('resultadoConexion'));
        $this->assertArrayHasKey('url', $component->get('resultadoConexion'));
    }

    public function test_probar_conexion_returns_error_on_api_failure(): void
    {
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'error' => ['message' => 'Incorrect API key provided', 'type' => 'invalid_request_error'],
            ], 401),
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(MonitoreoIa::class)
            ->call('probarConexion');

        $component->assertSet('resultadoConexion.estado', 'error');
        $this->assertArrayHasKey('mensaje_error', $component->get('resultadoConexion'));
    }

    public function test_probar_conexion_requires_admin(): void
    {
        Livewire::actingAs($this->regularUser)
            ->test(MonitoreoIa::class)
            ->call('probarConexion')
            ->assertForbidden();
    }
}
