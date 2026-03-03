<?php

declare(strict_types=1);

namespace App\Tests\Services\Forecaster;

use App\Entity\Team;
use App\Services\Forecaster\BasicForecaster;
use PHPUnit\Framework\TestCase;

class BasicForecasterTest extends TestCase
{
    public function test_basic_forecaster(): void
    {
        $team = $this->createMock(Team::class);
        $team->method('getOutputAverage')->willReturn(10.0);
        $team->method('getStandardDeviation')->willReturn(2.5);

        $forecaster = new BasicForecaster(1000);
        $forecast = $forecaster->forecast($team, 10, 110);
        $this->assertTrue($forecast->getResult() < 15);
    }
}
