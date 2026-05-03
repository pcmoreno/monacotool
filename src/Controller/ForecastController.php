<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Forecast;
use App\Security\TeamVoter;
use App\Services\Forecaster\ForecastService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[IsCsrfTokenValid('api', tokenKey: 'X-CSRF-Token', tokenSource: IsCsrfTokenValid::SOURCE_HEADER, methods: ['POST', 'DELETE'])]
final class ForecastController extends AbstractController
{
    public function __construct(private readonly ForecastService $forecastService)
    {
    }

    #[Route('/forecast/{id}/sensitivity', name: 'app_forecast_sensitivity', methods: ['POST'])]
    public function sensitivity(Forecast $forecast): JsonResponse
    {
        $this->denyAccessUnlessGranted(TeamVoter::VIEW, $forecast->getTeam());

        $table = $this->forecastService->computeSensitivity($forecast);

        return new JsonResponse($table);
    }

    #[Route('/forecast/{id}', name: 'app_forecast_delete', methods: ['DELETE'])]
    public function delete(Forecast $forecast): JsonResponse
    {
        $this->denyAccessUnlessGranted(TeamVoter::EDIT, $forecast->getTeam());

        $this->forecastService->delete($forecast);

        return new JsonResponse(null, 204);
    }
}
