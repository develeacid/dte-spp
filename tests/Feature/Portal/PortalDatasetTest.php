<?php

namespace Tests\Feature\Portal;

use App\Models\Portal\PubDatasetCatalogo;
use App\Models\Portal\PubPrograma;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class PortalDatasetTest extends TestCase
{
    use RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_detalle_ds_01_lista_programas_paginados(): void
    {
        PubDatasetCatalogo::factory()->create(['codigo' => 'DS-01', 'titulo' => 'Programas']);
        PubPrograma::factory()->count(30)->create();

        $response = $this->get(route('portal.dataset.show', 'DS-01'));

        $response->assertOk();
        $response->assertSeeText('Programas');
        $response->assertSee('?page=2', false);
    }

    public function test_codigo_no_implementado_retorna_404(): void
    {
        PubDatasetCatalogo::factory()->create(['codigo' => 'DS-01']);

        $this->get(route('portal.dataset.show', 'DS-99'))->assertNotFound();
        $this->get(route('portal.dataset.show', 'DS-02'))->assertNotFound();
    }
}
