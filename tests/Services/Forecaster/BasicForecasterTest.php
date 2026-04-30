<?php

declare(strict_types=1);

namespace App\Tests\Services\Forecaster;

use App\Entity\Team;
use App\Services\Forecaster\BasicForecaster;
use PHPUnit\Framework\TestCase;

class BasicForecasterTest extends TestCase
{
    public function test_probability_is_in_expected_range_with_variable_velocity(): void
    {
        $team = $this->createMock(Team::class);
        $team->method('getOutputAverage')->willReturn(10.0);
        $team->method('getSampleStandardDeviation')->willReturn(2.5);

        // mean total over 10 iterations = 100; target 110 is ~1.27 stddevs above mean → ~10% probability
        $forecast = (new BasicForecaster(2000))->forecast($team, 10, 110);

        $this->assertGreaterThanOrEqual(0.02, $forecast->getResult());
        $this->assertLessThanOrEqual(0.30, $forecast->getResult());
    }

    public function test_probability_is_one_when_target_is_always_met(): void
    {
        $team = $this->createMock(Team::class);
        $team->method('getOutputAverage')->willReturn(10.0);
        $team->method('getSampleStandardDeviation')->willReturn(0.0);

        // stddev=0: every simulation produces exactly 10 per iteration
        $forecast = (new BasicForecaster(500))->forecast($team, 1, 10);

        $this->assertSame(1.0, $forecast->getResult());
    }

    public function test_probability_is_zero_when_target_is_never_met(): void
    {
        $team = $this->createMock(Team::class);
        $team->method('getOutputAverage')->willReturn(10.0);
        $team->method('getSampleStandardDeviation')->willReturn(0.0);

        // stddev=0: every simulation produces exactly 10; target 11 is unreachable
        $forecast = (new BasicForecaster(500))->forecast($team, 1, 11);

        $this->assertSame(0.0, $forecast->getResult());
    }
}
