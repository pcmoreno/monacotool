<?php

declare(strict_types=1);

namespace App\Services\Forecaster;

use App\Entity\Forecast;
use App\Entity\Team;

interface ForecastInterface
{
        public function forecast(Team $team, int $numberOfIterations, int $outputAmount): Forecast;
}
