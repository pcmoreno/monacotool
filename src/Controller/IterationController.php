<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Iteration;
use App\Entity\Team;
use App\Request\IterationCreateRequest;
use App\Request\IterationUpdateRequest;
use App\Security\TeamVoter;
use App\Services\IterationService;
use App\Services\TeamStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\UX\Turbo\TurboBundle;

#[IsCsrfTokenValid('api', tokenKey: 'X-CSRF-Token', tokenSource: IsCsrfTokenValid::SOURCE_HEADER, methods: ['POST', 'PATCH', 'DELETE'])]
final class IterationController extends AbstractController
{
    public function __construct(
        private readonly IterationService $iterationService,
        private readonly TeamStatisticsService $teamStatisticsService,
    ) {
    }

    #[Route('/team/{id}/iteration', name: 'app_iteration_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] IterationCreateRequest $iterationCreateRequest, Team $team, Request $request): Response
    {
        $this->denyAccessUnlessGranted(TeamVoter::EDIT, $team);

        $endDate = \DateTimeImmutable::createFromFormat('Y-m-d', $iterationCreateRequest->endDate);
        if (!$endDate) {
            return new JsonResponse(['error' => 'Invalid date.'], 422);
        }

        $iteration = $this->iterationService->create($team, $iterationCreateRequest->output, $endDate);
        $avg = $this->teamStatisticsService->getOutputAverage($team);
        $dev = $this->teamStatisticsService->getSampleStandardDeviation($team);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->renderBlock('team/show.html.twig', 'stream_create_iteration', [
                'iteration' => $iteration,
                'outputAverage' => $avg,
                'standardDeviation' => $dev,
                'iterationCount' => $team->getIterations()->count(),
            ]);
        }

        return new JsonResponse([
            'id' => $iteration->getId(),
            'endDate' => $iteration->getEndDate()->format('Y-m-d'),
            'output' => $iteration->getOutput(),
            'outputAverage' => round($avg, 1),
            'standardDeviation' => round($dev, 3),
        ], 201);
    }

    #[Route('/iteration/{id}', name: 'app_iteration_update', methods: ['PATCH'])]
    public function update(#[MapRequestPayload] IterationUpdateRequest $iterationUpdateRequest, Iteration $iteration, Request $request): Response
    {
        $this->denyAccessUnlessGranted(TeamVoter::EDIT, $iteration->getTeam());

        if ($iterationUpdateRequest->output === null && $iterationUpdateRequest->endDate === null) {
            return new JsonResponse(['error' => 'Nothing to update.'], 422);
        }

        $endDate = null;
        if ($iterationUpdateRequest->endDate) {
            $endDate = \DateTimeImmutable::createFromFormat('Y-m-d', $iterationUpdateRequest->endDate);
            if (!$endDate) {
                return new JsonResponse(['error' => 'Invalid date.'], 422);
            }
        }

        $this->iterationService->update($iteration, $iterationUpdateRequest->output, $endDate);

        $team = $iteration->getTeam();
        $avg = $this->teamStatisticsService->getOutputAverage($team);
        $dev = $this->teamStatisticsService->getSampleStandardDeviation($team);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->renderBlock('team/show.html.twig', 'stream_update_iteration', [
                'iteration' => $iteration,
                'outputAverage' => $avg,
                'standardDeviation' => $dev,
            ]);
        }

        return new JsonResponse([
            'outputAverage' => round($avg, 1),
            'standardDeviation' => round($dev, 3),
        ]);
    }

    #[Route('/iteration/{id}', name: 'app_iteration_delete', methods: ['DELETE'])]
    public function delete(Iteration $iteration, Request $request): Response
    {
        $team = $iteration->getTeam();
        $this->denyAccessUnlessGranted(TeamVoter::EDIT, $team);

        $iterationId = $iteration->getId();
        $iterationCount = $team->getIterations()->count() - 1;
        $this->iterationService->delete($iteration);

        $avg = $this->teamStatisticsService->getOutputAverage($team);
        $dev = $this->teamStatisticsService->getSampleStandardDeviation($team);

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->renderBlock('team/show.html.twig', 'stream_delete_iteration', [
                'iterationId' => $iterationId,
                'outputAverage' => $avg,
                'standardDeviation' => $dev,
                'iterationCount' => $iterationCount,
            ]);
        }

        return new JsonResponse([
            'outputAverage' => round($avg, 1),
            'standardDeviation' => round($dev, 3),
        ]);
    }
}
