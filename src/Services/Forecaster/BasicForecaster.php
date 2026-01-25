<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Forecast;
use App\Entity\Team;
use App\Services\ForecastInterface;

class BasicForecaster implements ForecastInterface
{
    public function forecast(Team $team, int $numberOfIterations, int $outputAmount): Forecast
    {
        // TODO: Properly implement method.
        return new Forecast();
    }
}
