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
        private int $numberOfSimulations,
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

    /** @return list<int> */
    private function createSimulations(Team $team, int $numberOfIterations): array
    {
        $mean = $this->teamStatisticsService->getOutputAverage($team);
        $stdDev = $this->teamStatisticsService->getSampleStandardDeviation($team);

        $simulations = [];
        for ($i = 0; $i < $this->numberOfSimulations; $i++) {
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
