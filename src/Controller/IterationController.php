<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Iteration;
use App\Entity\Team;
use App\Services\IterationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class IterationController extends AbstractController
{
    public function __construct(private readonly IterationService $iterationService)
    {
    }

    #[Route('/team/{id}/iteration', name: 'app_iteration_create', methods: ['POST'])]
    public function create(Request $request, Team $team): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid request body.'], 400);
        }

        $output = filter_var($data['output'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($output === false) {
            return new JsonResponse(['error' => 'Output must be a non-negative integer.'], 400);
        }

        $endDate = \DateTime::createFromFormat('Y-m-d', $data['end_date'] ?? '');
        if ($endDate === false) {
            return new JsonResponse(['error' => 'End date must be in Y-m-d format.'], 400);
        }

        $iteration = $this->iterationService->create($team, $output, $endDate);

        return new JsonResponse([
            'id' => $iteration->getId(),
            'endDate' => $iteration->getEndDate()->format('Y-m-d'),
            'output' => $iteration->getOutput(),
            'outputAverage' => round($team->getOutputAverage(), 1),
            'standardDeviation' => round($team->getSampleStandardDeviation(), 3),
        ], 201);
    }

    #[Route('/iteration/{id}', name: 'app_iteration_update', methods: ['PATCH'])]
    public function update(Request $request, Iteration $iteration): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid request body.'], 400);
        }

        $output = null;
        if (array_key_exists('output', $data)) {
            $output = filter_var($data['output'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($output === false) {
                return new JsonResponse(['error' => 'Output must be a non-negative integer.'], 400);
            }
        }

        $endDate = null;
        if (array_key_exists('end_date', $data)) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $data['end_date']);
            if ($endDate === false) {
                return new JsonResponse(['error' => 'End date must be in Y-m-d format.'], 400);
            }
        }

        if ($output === null && $endDate === null) {
            return new JsonResponse(['error' => 'Nothing to update.'], 400);
        }

        $this->iterationService->update($iteration, $output, $endDate);

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
        $this->iterationService->delete($iteration);

        return new JsonResponse([
            'outputAverage' => round($team->getOutputAverage(), 1),
            'standardDeviation' => round($team->getSampleStandardDeviation(), 3),
        ]);
    }
}
