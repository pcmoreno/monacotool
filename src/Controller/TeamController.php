<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Exception\AlreadyMemberException;
use App\Exception\TooManyTeamsException;
use App\Request\ForecastRequest;
use App\Request\InviteRequest;
use App\Request\TeamCreateRequest;
use App\Security\TeamVoter;
use App\Services\Forecaster\ForecastService;
use App\Services\InviteService;
use App\Services\TeamService;
use App\Services\TeamStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsCsrfTokenValid('api', tokenKey: 'X-CSRF-Token', tokenSource: IsCsrfTokenValid::SOURCE_HEADER, methods: ['POST', 'DELETE'])]
final class TeamController extends AbstractController
{
    public function __construct(
        private readonly ForecastService $forecastService,
        private readonly InviteService $inviteService,
        private readonly TeamService $teamService,
        private readonly TeamStatisticsService $teamStatisticsService,
        private readonly RateLimiterFactory $inviteLimiter,
    ) {
    }

    #[Route('/team', name: 'app_team_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] TeamCreateRequest $teamCreateRequest, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $team = $this->teamService->create($teamCreateRequest->name, $user);
        } catch (TooManyTeamsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        }

        return new JsonResponse(['id' => $team->getId(), 'name' => $team->getName()], 201);
    }

    #[Route('/team', name: 'app_team', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): Response
    {
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
            'outputAverage' => $this->teamStatisticsService->getOutputAverage($team),
            'standardDeviation' => $this->teamStatisticsService->getSampleStandardDeviation($team),
        ]);
    }

    #[Route('/team/{id}', name: 'app_team_delete', methods: ['DELETE'])]
    #[IsGranted(TeamVoter::DELETE, subject: 'team')]
    public function delete(Team $team): JsonResponse
    {
        $this->teamService->delete($team);

        return new JsonResponse(null, 204);
    }

    #[Route('/team/{id}/invite', name: 'app_team_invite', methods: ['POST'])]
    #[IsGranted(TeamVoter::EDIT, subject: 'team')]
    public function invite(#[MapRequestPayload] InviteRequest $inviteRequest, Team $team, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        if (!$this->inviteLimiter->create($request->getClientIp())->consume(1)->isAccepted()) {
            return new JsonResponse(['error' => 'Too many invitations sent. Please try again later.'], 429);
        }

        try {
            $this->inviteService->invite($team, $inviteRequest->name, $inviteRequest->email, $user);
        } catch (AlreadyMemberException) {
            // silently succeed — returning 422 leaks membership/pending status to the caller
        } catch (TransportExceptionInterface) {
            return new JsonResponse(['error' => 'Invitation could not be sent due to a mail delivery error. Please try again later.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/team/{id}/forecast', name: 'app_team_forecast', methods: ['POST'])]
    #[IsGranted(TeamVoter::EDIT, subject: 'team')]
    public function requestForecast(#[MapRequestPayload] ForecastRequest $forecastRequest, Team $team): JsonResponse
    {
        $forecast = $this->forecastService->forecast($team, $forecastRequest->targetIterations, $forecastRequest->targetOutput);

        return new JsonResponse([
            'id' => $forecast->getId(),
            'createdAt' => $forecast->getCreatedAt()->format('Y-m-d H:i'),
            'targetOutput' => $forecast->getTargetOutput(),
            'targetIterations' => $forecast->getTargetIterations(),
            'numberOfSimulations' => $forecast->getNumberOfSimulations(),
            'result' => $forecast->getResult(),
            'teamStatsSnapshot' => $forecast->getTeamStatsSnapshot(),
            'sensitivityTable' => $forecast->getSensitivityTable(),
        ], 201);
    }
}
