<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Iteration;
use App\Entity\Team;
use App\Repository\IterationRepository;

class IterationService
{
    public function __construct(private readonly IterationRepository $iterationRepository)
    {
    }

    public function create(Team $team, int $output, \DateTime $endDate): Iteration
    {
        $iteration = new Iteration();
        $iteration->setOutput($output);
        $iteration->setEndDate($endDate);
        $iteration->setTeam($team);
        $this->iterationRepository->save($iteration);

        return $iteration;
    }

    public function update(Iteration $iteration, ?int $output, ?\DateTime $endDate): void
    {
        if ($output !== null) {
            $iteration->setOutput($output);
        }

        if ($endDate !== null) {
            $iteration->setEndDate($endDate);
        }

        $this->iterationRepository->save($iteration);
    }

    public function delete(Iteration $iteration): void
    {
        $iteration->getTeam()->removeIteration($iteration);
        $this->iterationRepository->delete($iteration);
    }
}
