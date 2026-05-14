<?php

namespace Tests\Feature\Portal;

use App\Models\Portal\PubDatasetCatalogo;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class PortalIndexTest extends TestCase
{
    use RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_indice_carga_sin_auth_y_lista_catalogo(): void
    {
        PubDatasetCatalogo::factory()->create([
            'codigo' => 'DS-01',
            'titulo' => 'Catálogo de programas presupuestarios',
        ]);

        $response = $this->get(route('portal.index'));

        $response->assertOk();
        $response->assertSeeText('DS-01');
        $response->assertSeeText('Catálogo de programas presupuestarios');
    }

    public function test_indice_no_requiere_autenticacion(): void
    {
        $this->get(route('portal.index'))->assertOk();
    }
}
