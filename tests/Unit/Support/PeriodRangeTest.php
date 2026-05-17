<?php

namespace Tests\Unit\Support;

use App\Support\PeriodRange;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PeriodRangeTest extends TestCase
{
    #[Test]
    public function parsea_q1_a_primer_trimestre(): void
    {
        $range = PeriodRange::fromQuarterString('2026-Q1');
        $this->assertSame('2026-01-01', $range->start->format('Y-m-d'));
        $this->assertSame('2026-03-31', $range->end->format('Y-m-d'));
    }

    #[Test]
    public function parsea_q2_a_segundo_trimestre(): void
    {
        $range = PeriodRange::fromQuarterString('2026-Q2');
        $this->assertSame('2026-04-01', $range->start->format('Y-m-d'));
        $this->assertSame('2026-06-30', $range->end->format('Y-m-d'));
    }

    #[Test]
    public function parsea_q3_a_tercer_trimestre(): void
    {
        $range = PeriodRange::fromQuarterString('2026-Q3');
        $this->assertSame('2026-07-01', $range->start->format('Y-m-d'));
        $this->assertSame('2026-09-30', $range->end->format('Y-m-d'));
    }

    #[Test]
    public function parsea_q4_a_cuarto_trimestre(): void
    {
        $range = PeriodRange::fromQuarterString('2026-Q4');
        $this->assertSame('2026-10-01', $range->start->format('Y-m-d'));
        $this->assertSame('2026-12-31', $range->end->format('Y-m-d'));
    }

    #[Test]
    public function lanza_excepcion_si_formato_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PeriodRange::fromQuarterString('2026Q1');
    }

    #[Test]
    public function lanza_excepcion_si_trimestre_fuera_de_rango(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PeriodRange::fromQuarterString('2026-Q5');
    }

    #[Test]
    public function to_filter_array_devuelve_keys_para_query_builder(): void
    {
        $range = PeriodRange::fromQuarterString('2026-Q2');
        $this->assertSame(
            ['date_from' => '2026-04-01', 'date_to' => '2026-06-30'],
            $range->toFilterArray(),
        );
    }
}
