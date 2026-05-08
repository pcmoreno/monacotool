<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Forecast;
use App\Security\TeamVoter;
use App\Services\Forecaster\ForecastService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\UX\Turbo\TurboBundle;

#[IsCsrfTokenValid('api', tokenKey: 'X-CSRF-Token', tokenSource: IsCsrfTokenValid::SOURCE_HEADER, methods: ['POST', 'DELETE'])]
final class ForecastController extends AbstractController
{
    public function __construct(private readonly ForecastService $forecastService)
    {
    }

    #[Route('/forecast/{id}/sensitivity', name: 'app_forecast_sensitivity', methods: ['POST'])]
    public function sensitivity(Forecast $forecast, Request $request): Response
    {
        $this->denyAccessUnlessGranted(TeamVoter::VIEW, $forecast->getTeam());

        $table = $this->forecastService->computeSensitivity($forecast);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->renderBlock('team/show.html.twig', 'stream_sensitivity', [
                'sensitivityTable' => $table,
                'targetIterations' => $forecast->getTargetIterations(),
                'targetResult' => $forecast->getResult() ?? 0.0,
            ]);
        }

        return new JsonResponse($table);
    }

    #[Route('/forecast/{id}', name: 'app_forecast_delete', methods: ['DELETE'])]
    public function delete(Forecast $forecast, Request $request): Response
    {
        $this->denyAccessUnlessGranted(TeamVoter::EDIT, $forecast->getTeam());

        $forecastId = $forecast->getId();
        $remainingForecastCount = $forecast->getTeam()->getForecasts()->count() - 1;
        $this->forecastService->delete($forecast);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->renderBlock('team/show.html.twig', 'stream_delete_forecast', [
                'forecastId' => $forecastId,
                'remainingForecastCount' => $remainingForecastCount,
            ]);
        }

        return new JsonResponse(null, 204);
    }
}
