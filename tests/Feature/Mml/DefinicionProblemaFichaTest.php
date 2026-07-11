<?php

namespace Tests\Feature\Mml;

use App\Livewire\Mml\DefinicionProblema;
use App\Models\Mml\FichaInformacionBasica;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DefinicionProblemaFichaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Ficha',
            'clave' => 'PT-FICHA-'.uniqid(),
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_relacion_ficha_es_hasone(): void
    {
        $this->assertInstanceOf(HasOne::class, $this->programa->fichaInformacionBasica());
    }

    public function test_ficha_se_persiste(): void
    {
        $ficha = FichaInformacionBasica::create([
            'programa_presupuestario_id' => $this->programa->id,
            'magnitud' => '120,000 alumnos (Estadística 911)',
        ]);

        $this->assertDatabaseHas('fichas_informacion_basica', [
            'programa_presupuestario_id' => $this->programa->id,
            'magnitud' => '120,000 alumnos (Estadística 911)',
        ]);
        $this->assertSame($ficha->id, $this->programa->fichaInformacionBasica->id);
    }

    public function test_guardar_persiste_ficha_y_es_idempotente(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', 'Alta tasa de deserción escolar en zonas rurales')
            ->set('magnitud', '120,000 alumnos (Estadística 911)')
            ->set('focalizacion', 'Localidades de alta marginación')
            ->call('guardar')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(1, FichaInformacionBasica::where('programa_presupuestario_id', $this->programa->id)->count());
        $this->assertDatabaseHas('fichas_informacion_basica', [
            'programa_presupuestario_id' => $this->programa->id,
            'magnitud' => '120,000 alumnos (Estadística 911)',
            'focalizacion' => 'Localidades de alta marginación',
        ]);
    }

    public function test_mount_carga_ficha_existente(): void
    {
        FichaInformacionBasica::create([
            'programa_presupuestario_id' => $this->programa->id,
            'bienes_servicios' => 'Becas + materiales',
        ]);

        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->assertSet('bienesServicios', 'Becas + materiales');
    }

    public function test_completitud_cuenta_las_cinco(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', 'Alta tasa de deserción escolar en zonas rurales')
            ->set('magnitud', 'X')
            ->set('focalizacion', 'Y')
            ->assertSee('3/5 preguntas respondidas');
    }

    public function test_ficha_opcional_no_bloquea_guardado(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', 'Alta tasa de deserción escolar en zonas rurales')
            ->call('guardar')
            ->assertHasNoErrors();
    }
}
