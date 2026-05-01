<?php

declare(strict_types=1);

namespace App\Services\Forecaster;

use App\Entity\Forecast;
use App\Entity\Team;

class BasicForecaster implements ForecastInterface
{
    private int $numberOfSimulations;

    public function __construct(int $numberOfSimulations)
    {
        $this->numberOfSimulations = $numberOfSimulations;
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

    private function createSimulations(Team $team, int $numberOfIterations): array
    {
        $mean = $team->getOutputAverage();
        $stdDev = $team->getSampleStandardDeviation();

        $simulations = [];
        for ($i = 0; $i < $this->numberOfSimulations; $i++) {
            $simulation = new Simulation($numberOfIterations, $mean, $stdDev);
            $simulation->simulate();
            $simulations[] = array_sum($simulation->getIterations());
        }

        return $simulations;
    }

    private function calculateProbabilityForRequestedTarget(array $simulations, int $target) : float
    {
        $countSuccessful = 0;
        foreach ($simulations as $key => $value) {
            if ($value >= $target) {
                $countSuccessful++;
            }
        }

        return $countSuccessful / count($simulations);
    }
}
