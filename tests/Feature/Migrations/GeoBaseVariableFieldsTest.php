<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GeoBaseVariableFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_indicador_variables_has_geobase_endpoint_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn('indicador_variables', 'geobase_endpoint_type'));
    }

    public function test_indicador_variables_has_geobase_reference_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('indicador_variables', 'geobase_reference_id'));
    }

    public function test_indicador_variables_has_geobase_filter_params_column(): void
    {
        $this->assertTrue(Schema::hasColumn('indicador_variables', 'geobase_filter_params'));
    }

    public function test_indicador_variables_has_geobase_value_key_column(): void
    {
        $this->assertTrue(Schema::hasColumn('indicador_variables', 'geobase_value_key'));
    }
}
