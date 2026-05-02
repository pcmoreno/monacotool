<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Team;

class TeamStatisticsService
{
    public function getOutputAverage(Team $team): float
    {
        if ($team->getIterations()->isEmpty()) {
            return 0.0;
        }

        $count = $team->getIterations()->count();
        $sum = 0;
        foreach ($team->getIterations() as $iteration) {
            $sum += $iteration->getOutput();
        }

        return $sum / $count;
    }

    public function getSampleStandardDeviation(Team $team): float
    {
        $iterations = $team->getIterations();

        if ($iterations->count() < 2) {
            return 0.0;
        }

        $mean = $this->getOutputAverage($team);
        $sumOfSquaredVariances = 0;
        foreach ($iterations as $iteration) {
            $variance = $iteration->getOutput() - $mean;
            $sumOfSquaredVariances += $variance * $variance;
        }

        return sqrt($sumOfSquaredVariances / ($iterations->count() - 1));
    }
}
