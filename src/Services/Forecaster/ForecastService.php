<?php

declare(strict_types=1);

namespace App\Services\Forecaster;

use App\Entity\Forecast;
use App\Entity\Team;
use App\Repository\ForecastRepository;

class ForecastService
{
    public function __construct(
        private readonly ForecastInterface $forecaster,
        private readonly ForecastRepository $forecastRepository,
    ) {
    }

    public function forecast(Team $team, int $targetIterations, int $targetOutput): Forecast
    {
        $forecast = $this->forecaster->forecast($team, $targetIterations, $targetOutput);
        $this->forecastRepository->save($forecast);
        $this->forecastRepository->flush();

        return $forecast;
    }

    public function delete(Forecast $forecast): void
    {
        $this->forecastRepository->delete($forecast);
        $this->forecastRepository->flush();
    }
}
