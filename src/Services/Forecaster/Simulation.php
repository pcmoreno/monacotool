<?php

declare(strict_types=1);

namespace App\Services\Forecaster;

use App\Entity\Team;

class Simulation
{
    private array $iterations;
    private Team $team;
    private int $numberOfIterations;

    public function __construct(int $numberOfIterations, Team $team)
    {
        $this->iterations = [];
        $this->team = $team;
        $this->numberOfIterations = $numberOfIterations;
    }

    public function simulate(): void
    {
        for ($i = 0; $i < $this->numberOfIterations; $i++) {
            $iteration = $this->getRandomNumberWithNormalDistribution(
                $this->team->getOutputAverage(), $this->team->getSampleStandardDeviation()
            );
            $this->iterations[] = $iteration;
        }
    }

    private function getRandomNumberWithNormalDistribution($mean, $sd): int
    {
        $x = 1 - mt_rand() / mt_getrandmax(); // (0, 1] to avoid log(0)
        $y = mt_rand() / mt_getrandmax();
        return max(0, (int) round(sqrt(-2 * log($x)) * cos(2 * pi() * $y) * $sd + $mean, 0, PHP_ROUND_HALF_UP));
    }

    public function getIterations(): array
    {
        return $this->iterations;
    }

}
