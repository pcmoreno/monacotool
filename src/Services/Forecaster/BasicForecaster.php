<?php

declare(strict_types=1);

namespace App\Services\Forecaster;

use App\Entity\Forecast;
use App\Entity\Iteration;
use App\Entity\Team;

class BasicForecaster implements ForecastInterface
{
    private int $numberOfSimulations;

    /** @var Iteration[] $iterations */
    private array $iterations;

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
        $simulations = [];
        for ($i = 0; $i < $this->numberOfSimulations; $i++) {
            $simulation = new Simulation($numberOfIterations, $team);
            $simulation->simulate();
            $simulations[] = array_sum($simulation->getIterations());
        }

        asort($simulations);
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

        return 100 * ($countSuccessful / count($simulations));
    }
}
