<?php

declare(strict_types=1);

namespace App\Services\Forecaster;

class Simulation
{
    private array $iterations;

    public function __construct(
        private readonly int $numberOfIterations,
        private readonly float $mean,
        private readonly float $stdDev,
    ) {
        $this->iterations = [];
    }

    public function simulate(): void
    {
        for ($i = 0; $i < $this->numberOfIterations; $i++) {
            $this->iterations[] = $this->getRandomNumberWithNormalDistribution($this->mean, $this->stdDev);
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
