<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Team;
use App\Entity\User;
use App\Repository\TeamRepository;

class TeamService
{
    public function __construct(private readonly TeamRepository $teamRepository)
    {
    }

    /** @return Team[] */
    public function findTeamsForUser(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return $this->teamRepository->findAll();
        }

        return $this->teamRepository->findByUser($user);
    }
}
