<?php

namespace Tests\Unit\Models;

use App\Models\Mml\IndicadorVariable;
use Tests\TestCase;

class IndicadorVariableGeoBaseTest extends TestCase
{
    public function test_has_geo_base_link_returns_true_when_both_fields_set(): void
    {
        $variable = new IndicadorVariable([
            'geobase_endpoint_type' => 'program_coverage',
            'geobase_reference_id' => 42,
        ]);

        $this->assertTrue($variable->hasGeoBaseLink());
    }

    public function test_has_geo_base_link_returns_false_when_both_null(): void
    {
        $variable = new IndicadorVariable([
            'geobase_endpoint_type' => null,
            'geobase_reference_id' => null,
        ]);

        $this->assertFalse($variable->hasGeoBaseLink());
    }

    public function test_geobase_filter_params_casts_to_array(): void
    {
        $variable = new IndicadorVariable([
            'geobase_filter_params' => ['municipio' => 'oaxaca'],
        ]);

        $this->assertIsArray($variable->geobase_filter_params);
        $this->assertEquals(['municipio' => 'oaxaca'], $variable->geobase_filter_params);
    }
}
