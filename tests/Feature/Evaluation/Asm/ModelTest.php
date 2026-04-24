<?php

namespace Tests\Feature\Evaluation\Asm;

use App\Enums\SemaforoAsm;
use App\Enums\StatusAsm;
use App\Enums\TipoAccionAsm;
use App\Enums\TipoPlazoAsm;
use App\Models\Evaluation\Asm;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function creates_an_asm_via_factory_with_defaults(): void
    {
        $asm = Asm::factory()->create();

        $this->assertNotNull($asm->id);
        $this->assertInstanceOf(ProgramaPresupuestario::class, $asm->programa);
        $this->assertInstanceOf(User::class, $asm->responsable);
        $this->assertInstanceOf(StatusAsm::class, $asm->status);
        $this->assertInstanceOf(TipoPlazoAsm::class, $asm->tipo_plazo);
        $this->assertInstanceOf(TipoAccionAsm::class, $asm->tipo_accion);
    }

    /** @test */
    public function derives_semaforo_accessor_from_fecha_compromiso_and_status(): void
    {
        $asm = Asm::factory()->create([
            'fecha_compromiso' => now()->addDays(60),
            'status' => StatusAsm::PENDIENTE,
        ]);

        $this->assertSame(SemaforoAsm::VERDE, $asm->semaforo);
    }

    /** @test */
    public function soft_deletes_records(): void
    {
        $asm = Asm::factory()->create();
        $id = $asm->id;

        $asm->delete();

        $this->assertNull(Asm::find($id));
        $this->assertNotNull(Asm::withTrashed()->find($id));
    }
}
