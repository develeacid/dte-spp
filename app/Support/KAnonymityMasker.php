<?php

namespace App\Support;

use InvalidArgumentException;

final class KAnonymityMasker
{
    public const DEFAULT_THRESHOLD = 5;

    public function __construct(private readonly int $threshold = self::DEFAULT_THRESHOLD)
    {
        if ($this->threshold < 1) {
            throw new InvalidArgumentException(
                "KAnonymityMasker threshold must be a positive integer, {$this->threshold} given."
            );
        }
    }

    public function threshold(): int
    {
        return $this->threshold;
    }

    public function maskLiteral(): string
    {
        return '<'.$this->threshold;
    }

    public function mask(int $count): int|string
    {
        if ($count < 0) {
            throw new InvalidArgumentException(
                "KAnonymityMasker::mask() expects a non-negative count, {$count} given."
            );
        }

        return $count < $this->threshold ? $this->maskLiteral() : $count;
    }

    /**
     * @param  array<string,int>  $bucket
     * @return array<string,int|string>
     */
    public function maskBucket(array $bucket): array
    {
        $masked = [];
        foreach ($bucket as $key => $value) {
            $masked[$key] = $this->mask($value);
        }

        return $masked;
    }
}
