<?php

declare(strict_types=1);

namespace App\Services\Forecaster;

use App\Entity\Forecast;
use App\Entity\Team;
use App\Repository\ForecastRepository;
use App\Services\TeamStatisticsService;

class ForecastService
{
    public function __construct(
        private readonly ForecastInterface $forecaster,
        private readonly ForecastRepository $forecastRepository,
        private readonly TeamStatisticsService $teamStatisticsService,
    ) {
    }

    public function forecast(Team $team, int $targetIterations, int $targetOutput): Forecast
    {
        $forecast = $this->forecaster->forecast($team, $targetIterations, $targetOutput);
        $forecast->setTeamStatsSnapshot([
            'mean' => $this->teamStatisticsService->getOutputAverage($team),
            'std_dev' => $this->teamStatisticsService->getSampleStandardDeviation($team),
        ]);
        $this->forecastRepository->save($forecast);
        $this->forecastRepository->flush();

        return $forecast;
    }

    public function computeSensitivity(Forecast $forecast): array
    {
        if ($forecast->getSensitivityTable() !== null) {
            return $forecast->getSensitivityTable();
        }

        $snapshot = $forecast->getTeamStatsSnapshot();
        $target = $forecast->getTargetIterations();
        $table = [];

        for ($i = $target - 5; $i <= $target + 5; $i++) {
            if ($i === $target || $i < 1) {
                continue;
            }
            $result = $this->forecaster->forecastFromSnapshot(
                $snapshot['mean'],
                $snapshot['std_dev'],
                $forecast->getNumberOfSimulations(),
                $i,
                $forecast->getTargetOutput(),
            );
            $table[(string) $i] = $result;
        }

        $forecast->setSensitivityTable($table);
        $this->forecastRepository->save($forecast);
        $this->forecastRepository->flush();

        return $table;
    }

    public function delete(Forecast $forecast): void
    {
        $this->forecastRepository->delete($forecast);
        $this->forecastRepository->flush();
    }
}
