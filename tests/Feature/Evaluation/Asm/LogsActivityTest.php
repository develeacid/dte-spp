<?php

namespace Tests\Feature\Evaluation\Asm;

use App\Models\Evaluation\Asm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LogsActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_created_activity_on_asm_creation(): void
    {
        $asm = Asm::factory()->create();

        $log = Activity::where('log_name', 'asm')
            ->where('subject_id', $asm->id)
            ->where('event', 'created')
            ->latest()
            ->first();

        $this->assertNotNull($log);
    }

    public function test_records_updated_activity_with_old_and_new_values_for_porcentaje_avance(): void
    {
        $asm = Asm::factory()->create(['porcentaje_avance' => 10]);

        $asm->update(['porcentaje_avance' => 50]);

        $log = Activity::where('log_name', 'asm')
            ->where('subject_id', $asm->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(10, $log->properties['old']['porcentaje_avance'] ?? null);
        $this->assertSame(50, $log->properties['attributes']['porcentaje_avance'] ?? null);
    }

    public function test_records_deleted_activity_on_soft_delete(): void
    {
        $asm = Asm::factory()->create();
        $asm->delete();

        $log = Activity::where('log_name', 'asm')
            ->where('subject_id', $asm->id)
            ->where('event', 'deleted')
            ->latest()
            ->first();

        $this->assertNotNull($log);
    }
}
