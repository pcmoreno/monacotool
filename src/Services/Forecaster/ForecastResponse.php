<?php

namespace App\Services\Forecaster;

use App\Entity\Forecast;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ForecastResponse extends JsonResponse
{
    public static function create(
        Forecast $forecast,
        int $requestedTargetIteration,
        int $requestedTargetOutput,
    ): JsonResponse {
        return new JsonResponse(
            data: [
                [
                    'Probability of success' => $forecast->getResult(),
                    'Team velocity' => $forecast->getTeam()->getOutputAverage(),
                    'Team standard deviation' => $forecast->getTeam()->getStandardDeviation(),
                    'Target Iteration' => $requestedTargetIteration,
                    'Target Output' => $requestedTargetOutput,
                ],
            ],
            status: Response::HTTP_OK
        );
    }
}
