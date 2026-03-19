<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;

class SyncGeoBaseAvancesTest extends TestCase
{
    public function test_command_exists(): void
    {
        $this->artisan('geobase:sync-avances --dry-run')
            ->assertExitCode(0);
    }

    public function test_command_accepts_program_option(): void
    {
        $this->artisan('geobase:sync-avances --dry-run --program=999')
            ->assertExitCode(0);
    }
}
