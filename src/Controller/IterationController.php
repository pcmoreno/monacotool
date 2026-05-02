<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Iteration;
use App\Entity\Team;
use App\Request\IterationCreateRequest;
use App\Request\IterationUpdateRequest;
use App\Security\TeamVoter;
use App\Services\IterationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[IsCsrfTokenValid('api', tokenKey: 'X-CSRF-Token', tokenSource: IsCsrfTokenValid::SOURCE_HEADER, methods: ['POST', 'PATCH', 'DELETE'])]
final class IterationController extends AbstractController
{
    public function __construct(private readonly IterationService $iterationService)
    {
    }

    #[Route('/team/{id}/iteration', name: 'app_iteration_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] IterationCreateRequest $iterationCreateRequest, Team $team): JsonResponse
    {
        $this->denyAccessUnlessGranted(TeamVoter::EDIT, $team);

        $endDate = \DateTimeImmutable::createFromFormat('Y-m-d', $iterationCreateRequest->endDate);
        if (!$endDate) {
            return new JsonResponse(['error' => 'Invalid date.'], 422);
        }

        $iteration = $this->iterationService->create($team, $iterationCreateRequest->output, $endDate);

        return new JsonResponse([
            'id' => $iteration->getId(),
            'endDate' => $iteration->getEndDate()->format('Y-m-d'),
            'output' => $iteration->getOutput(),
            'outputAverage' => round($team->getOutputAverage(), 1),
            'standardDeviation' => round($team->getSampleStandardDeviation(), 3),
        ], 201);
    }

    #[Route('/iteration/{id}', name: 'app_iteration_update', methods: ['PATCH'])]
    public function update(#[MapRequestPayload] IterationUpdateRequest $iterationUpdateRequest, Iteration $iteration): JsonResponse
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

        return new JsonResponse([
            'outputAverage' => round($team->getOutputAverage(), 1),
            'standardDeviation' => round($team->getSampleStandardDeviation(), 3),
        ]);
    }

    #[Route('/iteration/{id}', name: 'app_iteration_delete', methods: ['DELETE'])]
    public function delete(Iteration $iteration): JsonResponse
    {
        $team = $iteration->getTeam();
        $this->denyAccessUnlessGranted(TeamVoter::EDIT, $team);
        $this->iterationService->delete($iteration);

        return new JsonResponse([
            'outputAverage' => round($team->getOutputAverage(), 1),
            'standardDeviation' => round($team->getSampleStandardDeviation(), 3),
        ]);
    }
}
