<?php

namespace App\Support;

class KAnonymityMasker
{
    public function __construct(private readonly int $threshold = 5)
    {
    }

    public function mask(int $count): int|string
    {
        return $count < $this->threshold ? '<'.$this->threshold : $count;
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
