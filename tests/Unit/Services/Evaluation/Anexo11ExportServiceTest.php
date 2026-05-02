<?php

namespace Tests\Unit\Services\Evaluation;

use App\Services\Evaluation\Anexo11ExportService;
use App\Services\Evaluation\Anexo11ReportData;
use App\Services\GeoBase\GeoBaseClient;
use App\Support\KAnonymityMasker;
use Mockery;
use Tests\TestCase;

class Anexo11ExportServiceTest extends TestCase
{
    public function test_it_aggregates_buckets_across_municipios(): void
    {
        $client = Mockery::mock(GeoBaseClient::class);
        $client->shouldReceive('getTerritorialReport')->once()->with(42)->andReturn([
            'data' => [
                [
                    'program_id' => 42,
                    'program_name' => 'Becas Básicas',
                    'municipio_id' => 1,
                    'total_beneficiarios' => 100,
                    'por_genero' => ['masculino' => 40, 'femenino' => 58, 'otro' => 2],
                    'por_grupo_edad' => ['infantes' => 10, 'ninios' => 30, 'adolescentes' => 20, 'jovenes' => 20, 'adultos' => 15, 'adultos_mayores' => 5],
                    'por_pueblo' => ['zapoteco' => 50, 'mixteco' => 20],
                    'por_tipo_discapacidad' => ['motriz' => 8, 'visual' => 2, 'auditiva' => 0, 'intelectual' => 0, 'psicosocial' => 0, 'multiple' => 0, 'ninguna' => 90],
                ],
                [
                    'program_id' => 42,
                    'program_name' => 'Becas Básicas',
                    'municipio_id' => 2,
                    'total_beneficiarios' => 50,
                    'por_genero' => ['masculino' => 25, 'femenino' => 25, 'otro' => 0],
                    'por_grupo_edad' => ['infantes' => 5, 'ninios' => 15, 'adolescentes' => 10, 'jovenes' => 10, 'adultos' => 8, 'adultos_mayores' => 2],
                    'por_pueblo' => ['zapoteco' => 30, 'chinanteco' => 3],
                    'por_tipo_discapacidad' => ['motriz' => 3, 'visual' => 1, 'auditiva' => 1, 'intelectual' => 0, 'psicosocial' => 0, 'multiple' => 0, 'ninguna' => 45],
                ],
            ],
            'meta' => ['total_rows' => 2, 'refreshed_at' => '2026-04-24T12:00:00Z'],
        ]);

        $service = new Anexo11ExportService($client, new KAnonymityMasker);
        $data = $service->build(42);

        $this->assertInstanceOf(Anexo11ReportData::class, $data);
        $this->assertSame(150, $data->totalBeneficiarios);
        $this->assertSame(65, $data->porGenero['masculino']);
        $this->assertSame(83, $data->porGenero['femenino']);
        // otro = 2+0 = 2 → masked
        $this->assertSame('<5', $data->porGenero['otro']);
        // adultos_mayores = 5+2 = 7 → NOT masked
        $this->assertSame(7, $data->porGrupoEdad['adultos_mayores']);
        // chinanteco = 3 → masked
        $this->assertSame('<5', $data->porPueblo['chinanteco']);
        $this->assertSame(80, $data->porPueblo['zapoteco']);
        // auditiva = 0+1 = 1 → masked
        $this->assertSame('<5', $data->porTipoDiscapacidad['auditiva']);
        $this->assertSame('2026-04-24T12:00:00Z', $data->refreshedAt);
    }

    public function test_it_returns_zero_totals_when_program_has_no_beneficiaries(): void
    {
        $client = Mockery::mock(GeoBaseClient::class);
        $client->shouldReceive('getTerritorialReport')->once()->with(99)->andReturn([
            'data' => [],
            'meta' => ['total_rows' => 0, 'refreshed_at' => '2026-04-24T12:00:00Z'],
        ]);

        $service = new Anexo11ExportService($client, new KAnonymityMasker);
        $data = $service->build(99, programaNombre: 'Sin beneficiarios');

        $this->assertSame(0, $data->totalBeneficiarios);
        $this->assertSame('<5', $data->porGenero['masculino']);
        $this->assertSame([], $data->porPueblo); // no pueblos seen at all
    }
}
