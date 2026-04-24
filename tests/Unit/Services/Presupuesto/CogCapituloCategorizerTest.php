<?php

namespace Tests\Unit\Services\Presupuesto;

use App\Services\Presupuesto\CogCapituloCategorizer;
use PHPUnit\Framework\TestCase;

class CogCapituloCategorizerTest extends TestCase
{
    public function test_extracts_capitulo_1000_from_servicios_personales(): void
    {
        $this->assertSame('1000', CogCapituloCategorizer::capitulo('1101'));
        $this->assertSame('1000', CogCapituloCategorizer::capitulo('1599'));
    }

    public function test_extracts_capitulo_2000_for_materiales(): void
    {
        $this->assertSame('2000', CogCapituloCategorizer::capitulo('2504'));
    }

    public function test_extracts_capitulo_3000_for_servicios_generales(): void
    {
        $this->assertSame('3000', CogCapituloCategorizer::capitulo('3301'));
    }

    public function test_extracts_capitulo_4000_for_transferencias(): void
    {
        $this->assertSame('4000', CogCapituloCategorizer::capitulo('4401'));
    }

    public function test_returns_null_for_invalid_format(): void
    {
        $this->assertNull(CogCapituloCategorizer::capitulo('A123'));
        $this->assertNull(CogCapituloCategorizer::capitulo(''));
    }

    public function test_label_returns_canonical_name(): void
    {
        $this->assertSame('Servicios Personales', CogCapituloCategorizer::label('1000'));
        $this->assertSame('Materiales y Suministros', CogCapituloCategorizer::label('2000'));
        $this->assertSame('Servicios Generales', CogCapituloCategorizer::label('3000'));
        $this->assertSame('Transferencias, Asignaciones, Subsidios', CogCapituloCategorizer::label('4000'));
    }

    public function test_label_returns_unknown_for_invalid_capitulo(): void
    {
        $this->assertSame('Desconocido', CogCapituloCategorizer::label('9999'));
    }
}
