<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GlosarioConfigTest extends TestCase
{
    private array $glosario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->glosario = require __DIR__ . '/../../config/glosario.php';
    }

    /** @test */
    public function glosario_has_all_mir_level_keys(): void
    {
        $requiredKeys = ['fin', 'proposito', 'componente', 'actividad'];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing key: {$key}");
            $this->assertNotEmpty($this->glosario[$key], "Empty value for: {$key}");
        }
    }

    /** @test */
    public function glosario_has_all_mir_column_keys(): void
    {
        $requiredKeys = ['resumen_narrativo', 'supuestos', 'indicador', 'medios_verificacion'];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing key: {$key}");
        }
    }

    /** @test */
    public function glosario_has_all_cremaa_keys(): void
    {
        $cremaaKeys = [
            'cremaa', 'cremaa_claro', 'cremaa_relevante', 'cremaa_economico',
            'cremaa_monitoreable', 'cremaa_adecuado', 'cremaa_aportante',
        ];

        foreach ($cremaaKeys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing CREMAA key: {$key}");
        }
    }

    /** @test */
    public function glosario_has_logic_keys(): void
    {
        $this->assertArrayHasKey('logica_vertical', $this->glosario);
        $this->assertArrayHasKey('logica_horizontal', $this->glosario);
    }

    /** @test */
    public function glosario_has_sentido_keys(): void
    {
        $keys = ['sentido_ascendente', 'sentido_descendente', 'sentido_regular'];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing key: {$key}");
        }
    }

    /** @test */
    public function glosario_has_tree_keys(): void
    {
        $keys = ['problema_central', 'causa_directa', 'causa_indirecta', 'efecto_directo', 'efecto_indirecto'];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing key: {$key}");
        }
    }

    /** @test */
    public function all_values_are_non_empty_strings(): void
    {
        foreach ($this->glosario as $key => $value) {
            $this->assertIsString($value, "Value for '{$key}' should be string");
            $this->assertNotEmpty($value, "Value for '{$key}' should not be empty");
        }
    }
}
