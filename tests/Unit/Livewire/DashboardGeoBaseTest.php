<?php

namespace Tests\Unit\Livewire;

use App\Livewire\Dashboard;
use PHPUnit\Framework\TestCase;

class DashboardGeoBaseTest extends TestCase
{
    public function test_geobase_stats_method_exists(): void
    {
        $this->assertTrue(method_exists(Dashboard::class, 'geobaseStats'));
    }
}
