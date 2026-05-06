<?php

declare(strict_types=1);

namespace App\Services\Forecaster;

use App\Entity\Forecast;
use App\Entity\Team;
use App\Services\TeamStatisticsService;

class BasicForecaster implements ForecastInterface
{
    public function __construct(
        private readonly TeamStatisticsService $teamStatisticsService,
        private readonly int $numberOfSimulations,
    ) {
        if ($numberOfSimulations < 1) {
            throw new \InvalidArgumentException('numberOfSimulations must be at least 1.');
        }
    }

    public function forecast(Team $team, int $numberOfIterations, int $outputAmount): Forecast
    {
        $simulations = $this->createSimulations($team, $numberOfIterations);

        $probability = $this->calculateProbabilityForRequestedTarget($simulations, $outputAmount);

        $forecast = new Forecast();
        $forecast->setTeam($team);
        $forecast->setResult($probability);
        $forecast->setNumberOfSimulations($this->numberOfSimulations);
        $forecast->setTargetIterations($numberOfIterations);
        $forecast->setTargetOutput($outputAmount);

        return $forecast;
    }

    public function forecastFromSnapshot(float $mean, float $stdDev, int $numberOfSimulations, int $numberOfIterations, int $outputAmount): float
    {
        $simulations = $this->runSimulations($mean, $stdDev, $numberOfSimulations, $numberOfIterations);

        return $this->calculateProbabilityForRequestedTarget($simulations, $outputAmount);
    }

    /** @return list<int> */
    private function createSimulations(Team $team, int $numberOfIterations): array
    {
        $mean = $this->teamStatisticsService->getOutputAverage($team);
        $stdDev = $this->teamStatisticsService->getSampleStandardDeviation($team);

        return $this->runSimulations($mean, $stdDev, $this->numberOfSimulations, $numberOfIterations);
    }

    /** @return list<int> */
    private function runSimulations(float $mean, float $stdDev, int $numberOfSimulations, int $numberOfIterations): array
    {
        $simulations = [];
        for ($i = 0; $i < $numberOfSimulations; $i++) {
            $simulation = new Simulation($numberOfIterations, $mean, $stdDev);
            $simulation->simulate();
            $simulations[] = array_sum($simulation->getIterations());
        }

        return $simulations;
    }

    /** @param list<int> $simulations */
    private function calculateProbabilityForRequestedTarget(array $simulations, int $target): float
    {
        $countSuccessful = 0;
        foreach ($simulations as $simulation) {
            if ($simulation >= $target) {
                $countSuccessful++;
            }
        }

        return $countSuccessful / count($simulations);
    }
}
