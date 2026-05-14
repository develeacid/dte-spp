<?php

namespace Tests\Feature\Portal;

use App\Models\Portal\PubDatasetCatalogo;
use App\Models\Portal\PubPrograma;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class PortalDownloadTest extends TestCase
{
    use RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_descarga_csv_con_headers_correctos(): void
    {
        PubDatasetCatalogo::factory()->create(['codigo' => 'DS-01']);
        PubPrograma::factory()->count(3)->create();

        $response = $this->get(route('portal.dataset.download', 'DS-01'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition',
            'attachment; filename=DS-01-programas-'.now()->toDateString().'.csv');

        $body = $response->streamedContent();
        $this->assertSame(4, substr_count($body, "\n"));
    }

    public function test_descarga_codigo_no_implementado_404(): void
    {
        $this->get(route('portal.dataset.download', 'DS-99'))->assertNotFound();
    }
}
