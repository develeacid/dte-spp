<?php

namespace Tests\Unit\Models\Transparencia;

use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransparenciaPublicacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_crea_publicacion_valida(): void
    {
        $pub = TransparenciaPublicacion::factory()->create();

        $this->assertNotNull($pub->id);
        $this->assertNotNull($pub->dataset_abierto_id);
        $this->assertNotNull($pub->dataset_clave);
        $this->assertContains($pub->action, ['publish', 'retire']);
        $this->assertIsBool($pub->success);
    }

    public function test_relacion_dataset_y_user(): void
    {
        $pub = TransparenciaPublicacion::factory()->create();

        $this->assertInstanceOf(DatasetAbierto::class, $pub->datasetAbierto);
        if ($pub->publicado_por_user_id) {
            $this->assertInstanceOf(User::class, $pub->publicadoPor);
        }
    }

    public function test_scope_exitosas_filtra_por_success(): void
    {
        TransparenciaPublicacion::factory()->create(['success' => true]);
        TransparenciaPublicacion::factory()->create(['success' => false]);

        $this->assertSame(1, TransparenciaPublicacion::exitosas()->count());
    }

    public function test_scope_para_clave_filtra(): void
    {
        TransparenciaPublicacion::factory()->create(['dataset_clave' => 'DS-01']);
        TransparenciaPublicacion::factory()->create(['dataset_clave' => 'DS-03']);

        $this->assertSame(1, TransparenciaPublicacion::paraClave('DS-01')->count());
    }
}
