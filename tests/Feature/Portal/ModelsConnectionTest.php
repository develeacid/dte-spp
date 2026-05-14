<?php

namespace Tests\Feature\Portal;

use App\Models\Portal\PubDatasetCatalogo;
use App\Models\Portal\PubPrograma;
use Tests\TestCase;

class ModelsConnectionTest extends TestCase
{
    public function test_pub_dataset_catalogo_usa_pgsql_public_read(): void
    {
        $this->assertSame('pgsql_public_read', (new PubDatasetCatalogo)->getConnectionName());
    }

    public function test_pub_programa_usa_pgsql_public_read(): void
    {
        $this->assertSame('pgsql_public_read', (new PubPrograma)->getConnectionName());
    }
}
