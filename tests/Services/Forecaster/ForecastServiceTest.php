<?php

declare(strict_types=1);

namespace App\Tests\Services\Forecaster;

use App\Entity\Forecast;
use App\Entity\Team;
use App\Repository\ForecastRepository;
use App\Services\Forecaster\ForecastInterface;
use App\Services\Forecaster\ForecastService;
use App\Services\TeamStatisticsService;
use App\Tests\Services\AbstractServiceTest;

class ForecastServiceTest extends AbstractServiceTest
{
    private ForecastService $service;
    private ForecastInterface $forecaster;
    private ForecastRepository $forecastRepository;
    private TeamStatisticsService $teamStatisticsService;

    protected function setUp(): void
    {
        $this->forecaster = $this->createMock(ForecastInterface::class);
        $this->forecastRepository = $this->createMock(ForecastRepository::class);
        $this->teamStatisticsService = $this->createMock(TeamStatisticsService::class);

        $this->service = new ForecastService(
            $this->forecaster,
            $this->forecastRepository,
            $this->teamStatisticsService,
        );
    }

    // --- forecast ---

    public function test_forecast_captures_team_stats_snapshot(): void
    {
        $team = $this->makeTeam();
        $forecast = new Forecast();
        $forecast->setTargetIterations(10);
        $forecast->setTargetOutput(100);
        $forecast->setNumberOfSimulations(1000);
        $forecast->setResult(0.75);

        $this->forecaster->method('forecast')->willReturn($forecast);
        $this->teamStatisticsService->method('getOutputAverage')->willReturn(12.5);
        $this->teamStatisticsService->method('getSampleStandardDeviation')->willReturn(3.2);
        $this->forecastRepository->method('save');
        $this->forecastRepository->method('flush');

        $result = $this->service->forecast($team, 10, 100);

        $this->assertSame(['mean' => 12.5, 'std_dev' => 3.2], $result->getTeamStatsSnapshot());
    }

    public function test_forecast_persists_and_flushes(): void
    {
        $team = $this->makeTeam();
        $forecast = new Forecast();
        $forecast->setTargetIterations(10);
        $forecast->setTargetOutput(100);
        $forecast->setNumberOfSimulations(1000);
        $forecast->setResult(0.75);

        $this->forecaster->method('forecast')->willReturn($forecast);
        $this->teamStatisticsService->method('getOutputAverage')->willReturn(10.0);
        $this->teamStatisticsService->method('getSampleStandardDeviation')->willReturn(2.0);
        $this->forecastRepository->expects($this->once())->method('save')->with($forecast);
        $this->forecastRepository->expects($this->once())->method('flush');

        $this->service->forecast($team, 10, 100);
    }

    // --- computeSensitivity ---

    public function test_compute_sensitivity_returns_cached_table_without_recomputing(): void
    {
        $cached = ['6' => 0.8, '7' => 0.9];
        $forecast = new Forecast();
        $forecast->setTargetIterations(5);
        $forecast->setSensitivityTable($cached);

        $this->forecaster->expects($this->never())->method('forecastFromSnapshot');

        $result = $this->service->computeSensitivity($forecast);

        $this->assertSame($cached, $result);
    }

    public function test_compute_sensitivity_skips_target_iteration(): void
    {
        $forecast = new Forecast();
        $forecast->setTargetIterations(10);
        $forecast->setTargetOutput(100);
        $forecast->setNumberOfSimulations(500);
        $forecast->setTeamStatsSnapshot(['mean' => 10.0, 'std_dev' => 2.0]);

        $calledWithIterations = [];
        $this->forecaster->method('forecastFromSnapshot')
            ->willReturnCallback(function (float $mean, float $stdDev, int $sims, int $iter) use (&$calledWithIterations): float {
                $calledWithIterations[] = $iter;
                return 0.5;
            });
        $this->forecastRepository->method('save');
        $this->forecastRepository->method('flush');

        $this->service->computeSensitivity($forecast);

        $this->assertNotContains(10, $calledWithIterations);
        $this->assertCount(10, $calledWithIterations);
    }

    public function test_compute_sensitivity_skips_iterations_below_one(): void
    {
        $forecast = new Forecast();
        $forecast->setTargetIterations(3);
        $forecast->setTargetOutput(100);
        $forecast->setNumberOfSimulations(500);
        $forecast->setTeamStatsSnapshot(['mean' => 10.0, 'std_dev' => 2.0]);

        $calledWithIterations = [];
        $this->forecaster->method('forecastFromSnapshot')
            ->willReturnCallback(function (float $mean, float $stdDev, int $sims, int $iter) use (&$calledWithIterations): float {
                $calledWithIterations[] = $iter;
                return 0.5;
            });
        $this->forecastRepository->method('save');
        $this->forecastRepository->method('flush');

        $this->service->computeSensitivity($forecast);

        $this->assertNotContains(0, $calledWithIterations);
        $this->assertNotContains(-1, $calledWithIterations);
        $this->assertNotContains(-2, $calledWithIterations);
    }

    public function test_compute_sensitivity_stores_and_flushes(): void
    {
        $forecast = new Forecast();
        $forecast->setTargetIterations(10);
        $forecast->setTargetOutput(100);
        $forecast->setNumberOfSimulations(500);
        $forecast->setTeamStatsSnapshot(['mean' => 10.0, 'std_dev' => 2.0]);

        $this->forecaster->method('forecastFromSnapshot')->willReturn(0.5);
        $this->forecastRepository->expects($this->once())->method('save')->with($forecast);
        $this->forecastRepository->expects($this->once())->method('flush');

        $this->service->computeSensitivity($forecast);

        $this->assertNotNull($forecast->getSensitivityTable());
    }
}
