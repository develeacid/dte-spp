<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Bus;

abstract class TestCase extends BaseTestCase
{
    /**
     * Si true, Bus::fake() se aplica en setUp() para prevenir que jobs reales
     * (ej. RegisterProgramOnGeoBase) se ejecuten contra servicios externos
     * durante la suite. Tests que necesiten dispatch real lo ponen en false
     * y deben mockear HTTP por su cuenta.
     */
    protected bool $fakeBusInSetUp = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->fakeBusInSetUp) {
            Bus::fake();
        }
    }
}
