<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Regresi polish Paket 3: health-check operasional. */
class HealthCheckTest extends TestCase
{
    public function test_kaza_health_sehat(): void
    {
        $this->artisan('kaza:health')->assertExitCode(0);
    }
}
