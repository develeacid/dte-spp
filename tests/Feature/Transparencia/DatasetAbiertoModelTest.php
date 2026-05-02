<?php

namespace Tests\Feature\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatasetAbiertoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_enviar_a_revision_solo_desde_borrador(): void
    {
        $ds = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::BORRADOR]);
        $ds->enviarARevision();
        $this->assertSame(EstadoDatasetAbierto::REVISION, $ds->fresh()->status);

        $this->expectException(DomainException::class);
        $ds->enviarARevision();
    }

    public function test_aprobar_setea_aprobado_por_y_aprobado_en(): void
    {
        $ds = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::REVISION]);
        $rda = User::factory()->create();

        $ds->aprobar($rda);

        $fresh = $ds->fresh();
        $this->assertSame(EstadoDatasetAbierto::APROBADO, $fresh->status);
        $this->assertSame($rda->id, $fresh->aprobado_por);
        $this->assertNotNull($fresh->aprobado_en);
    }

    public function test_publicar_setea_publicado_en(): void
    {
        $ds = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::APROBADO]);

        $ds->publicar();

        $fresh = $ds->fresh();
        $this->assertSame(EstadoDatasetAbierto::PUBLICADO, $fresh->status);
        $this->assertNotNull($fresh->publicado_en);
    }

    public function test_retirar_setea_motivo_solo_desde_publicado(): void
    {
        $ds = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::PUBLICADO]);

        $ds->retirar('Información incorrecta detectada por auditoría');

        $fresh = $ds->fresh();
        $this->assertSame(EstadoDatasetAbierto::RETIRADO, $fresh->status);
        $this->assertSame('Información incorrecta detectada por auditoría', $fresh->motivo_cambio_estado);
    }

    public function test_retirar_falla_si_no_publicado(): void
    {
        $ds = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::BORRADOR]);
        $this->expectException(DomainException::class);
        $ds->retirar('motivo');
    }

    public function test_rechazar_limpia_aprobado_por_y_setea_motivo(): void
    {
        $rda = User::factory()->create();
        $ds = DatasetAbierto::factory()->create([
            'status' => EstadoDatasetAbierto::REVISION,
            'aprobado_por' => $rda->id,
            'aprobado_en' => now(),
        ]);

        $ds->rechazar('Faltan campos del DCAT');

        $fresh = $ds->fresh();
        $this->assertSame(EstadoDatasetAbierto::BORRADOR, $fresh->status);
        $this->assertSame('Faltan campos del DCAT', $fresh->motivo_cambio_estado);
        $this->assertNull($fresh->aprobado_por);
        $this->assertNull($fresh->aprobado_en);
    }

    public function test_rechazar_falla_si_no_revision(): void
    {
        $ds = DatasetAbierto::factory()->create(['status' => EstadoDatasetAbierto::BORRADOR]);
        $this->expectException(DomainException::class);
        $ds->rechazar('motivo');
    }

    public function test_scope_plantillas_y_entregas(): void
    {
        DatasetAbierto::factory()->create(['dataset_clave' => 'DS-99', 'periodo' => null]);
        DatasetAbierto::factory()->create(['dataset_clave' => 'DS-99', 'periodo' => '2026-Q1']);

        $this->assertCount(1, DatasetAbierto::plantillas()->get());
        $this->assertCount(1, DatasetAbierto::entregas()->get());
    }
}
