<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class PeriodRange
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function fromQuarterString(string $period): self
    {
        if (! preg_match('/^(\d{4})-Q([1-4])$/', $period, $m)) {
            throw new InvalidArgumentException(
                "Periodo inválido: '{$period}'. Esperado 'YYYY-QN' donde N ∈ [1..4]."
            );
        }
        $year = (int) $m[1];
        $q = (int) $m[2];
        $startMonth = ($q - 1) * 3 + 1;
        $start = CarbonImmutable::create($year, $startMonth, 1);
        $end = $start->endOfQuarter()->startOfDay();

        return new self($start, $end);
    }

    /** @return array{date_from: string, date_to: string} */
    public function toFilterArray(): array
    {
        return [
            'date_from' => $this->start->format('Y-m-d'),
            'date_to' => $this->end->format('Y-m-d'),
        ];
    }
}
