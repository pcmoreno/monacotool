<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Team;
use App\Services\Forecaster\ForecastInterface;
use App\Services\Forecaster\ForecastResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TeamController extends AbstractController
{
    private ForecastInterface $forecaster;

    public function __construct(ForecastInterface $forecaster)
    {
        $this->forecaster = $forecaster;
    }

    #[Route('/team', name: 'app_team', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('team/index.html.twig', [
            'controller_name' => 'TeamController',
        ]);
    }

    #[Route('/team/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render(
            'team/show.html.twig',
            [
                'team' => $team,
                'outputAverage' => $team->getOutputAverage(),
                'standardDeviation' => $team->getSampleStandardDeviation(),
            ]
        );
    }

    #[Route('/team/{id}/forecast', name: 'app_team_forecast', methods: ['POST'])]
    public function requestForecast(Request $request, Team $team): Response
    {
        $requestedTargetOutput = (int) $request->getPayload()->get('target_output');
        $requestedTargetIteration = (int) $request->getPayload()->get('target_iteration');

        $forecast = $this->forecaster->forecast($team, $requestedTargetIteration, $requestedTargetOutput);

        return ForecastResponse::create($forecast, $requestedTargetIteration, $requestedTargetOutput);
    }

    #[Route('/team/{id}/forecasts', name: 'app_team_forecasts', methods: ['POST'])]
    public function requestForecasts(Request $request, Team $team): Response
    {
        $requestedTargetOutput = (int) $request->getPayload()->get('target_output');
        // TODO
        return new JsonResponse();
    }
}
