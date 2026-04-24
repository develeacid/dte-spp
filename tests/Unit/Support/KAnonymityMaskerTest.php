<?php

namespace Tests\Unit\Support;

use App\Support\KAnonymityMasker;
use PHPUnit\Framework\TestCase;

class KAnonymityMaskerTest extends TestCase
{
    public function test_values_below_threshold_are_masked(): void
    {
        $masker = new KAnonymityMasker(threshold: 5);

        $this->assertSame('<5', $masker->mask(0));
        $this->assertSame('<5', $masker->mask(4));
        $this->assertSame(5, $masker->mask(5));
        $this->assertSame(42, $masker->mask(42));
    }

    public function test_mask_bucket_applies_rule_to_every_value(): void
    {
        $masker = new KAnonymityMasker(threshold: 5);

        $masked = $masker->maskBucket(['motriz' => 3, 'visual' => 7, 'auditiva' => 0]);

        $this->assertSame(['motriz' => '<5', 'visual' => 7, 'auditiva' => '<5'], $masked);
    }

    public function test_mask_bucket_preserves_key_order(): void
    {
        $masker = new KAnonymityMasker(threshold: 5);

        $masked = $masker->maskBucket(['a' => 10, 'b' => 2, 'c' => 8]);

        $this->assertSame(['a', 'b', 'c'], array_keys($masked));
    }

    public function test_default_threshold_is_five(): void
    {
        $masker = new KAnonymityMasker();

        $this->assertSame('<5', $masker->mask(4));
        $this->assertSame(5, $masker->mask(5));
    }
}
