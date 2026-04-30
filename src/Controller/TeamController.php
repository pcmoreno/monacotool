<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Request\ForecastRequest;
use App\Security\TeamVoter;
use App\Services\Forecaster\ForecastService;
use App\Services\TeamService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsCsrfTokenValid('api', tokenKey: 'X-CSRF-Token', tokenSource: IsCsrfTokenValid::SOURCE_HEADER, methods: ['POST', 'DELETE'])]
final class TeamController extends AbstractController
{
    public function __construct(
        private readonly ForecastService $forecastService,
        private readonly TeamService $teamService,
    ) {
    }

    #[Route('/team', name: 'app_team_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return new JsonResponse(['error' => 'Name is required.'], 400);
        }

        $team = $this->teamService->create($name, $user);

        return new JsonResponse(['id' => $team->getId(), 'name' => $team->getName()], 201);
    }

    #[Route('/team', name: 'app_team', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('team/index.html.twig', [
            'teams' => $this->teamService->findTeamsForUser($user),
        ]);
    }

    #[Route('/team/{id}', name: 'app_team_show', methods: ['GET'])]
    #[IsGranted(TeamVoter::VIEW, subject: 'team')]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
            'team' => $team,
            'outputAverage' => $team->getOutputAverage(),
            'standardDeviation' => $team->getSampleStandardDeviation(),
        ]);
    }

    #[Route('/team/{id}/forecast', name: 'app_team_forecast', methods: ['POST'])]
    #[IsGranted(TeamVoter::EDIT, subject: 'team')]
    public function requestForecast(#[MapRequestPayload] ForecastRequest $forecastRequest, Team $team): JsonResponse
    {
        $forecast = $this->forecastService->forecast($team, $forecastRequest->targetIterations, $forecastRequest->targetOutput);

        return new JsonResponse([
            'createdAt' => $forecast->getCreatedAt()->format('Y-m-d H:i'),
            'targetOutput' => $forecast->getTargetOutput(),
            'targetIterations' => $forecast->getTargetIterations(),
            'result' => $forecast->getResult(),
        ], 201);
    }
}
