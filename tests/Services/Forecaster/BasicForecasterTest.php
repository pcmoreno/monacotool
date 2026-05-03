<?php

declare(strict_types=1);

namespace App\Tests\Services\Forecaster;

use App\Entity\Team;
use App\Services\Forecaster\BasicForecaster;
use App\Services\TeamStatisticsService;
use PHPUnit\Framework\TestCase;

class BasicForecasterTest extends TestCase
{
    private function makeForecaster(float $mean, float $stdDev, int $simulations = 2000): BasicForecaster
    {
        $stats = $this->createMock(TeamStatisticsService::class);
        $stats->method('getOutputAverage')->willReturn($mean);
        $stats->method('getSampleStandardDeviation')->willReturn($stdDev);

        return new BasicForecaster($stats, $simulations);
    }

    public function test_probability_is_in_expected_range_with_variable_velocity(): void
    {
        // mean total over 10 iterations = 100; target 110 is ~1.27 stddevs above mean → ~10% probability
        $forecast = $this->makeForecaster(10.0, 2.5)->forecast(new Team(), 10, 110);

        $this->assertGreaterThanOrEqual(0.02, $forecast->getResult());
        $this->assertLessThanOrEqual(0.30, $forecast->getResult());
    }

    public function test_probability_is_one_when_target_is_always_met(): void
    {
        // stddev=0: every simulation produces exactly 10 per iteration
        $forecast = $this->makeForecaster(10.0, 0.0, 500)->forecast(new Team(), 1, 10);

        $this->assertSame(1.0, $forecast->getResult());
    }

    public function test_probability_is_zero_when_target_is_never_met(): void
    {
        // stddev=0: every simulation produces exactly 10; target 11 is unreachable
        $forecast = $this->makeForecaster(10.0, 0.0, 500)->forecast(new Team(), 1, 11);

        $this->assertSame(0.0, $forecast->getResult());
    }

    public function test_forecast_from_snapshot_returns_one_when_target_always_met(): void
    {
        $result = $this->makeForecaster(10.0, 0.0, 500)->forecastFromSnapshot(10.0, 0.0, 500, 1, 10);

        $this->assertSame(1.0, $result);
    }

    public function test_forecast_from_snapshot_returns_zero_when_target_never_met(): void
    {
        $result = $this->makeForecaster(10.0, 0.0, 500)->forecastFromSnapshot(10.0, 0.0, 500, 1, 11);

        $this->assertSame(0.0, $result);
    }

    public function test_forecast_from_snapshot_uses_provided_stats_not_team(): void
    {
        // Forecaster's injected stats return mean=5, but snapshot args pass mean=10 with stddev=0,
        // so all simulations produce exactly 10 — probability must be 1.0 regardless of the mock
        $result = $this->makeForecaster(5.0, 0.0, 500)->forecastFromSnapshot(10.0, 0.0, 500, 1, 10);

        $this->assertSame(1.0, $result);
    }
}
