<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Membership;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\TeamRole;
use App\Exception\TooManyTeamsException;
use App\Repository\TeamRepository;

class TeamService
{
    public function __construct(private readonly TeamRepository $teamRepository)
    {
    }

    public function create(string $name, User $creator): Team
    {
        $team = new Team();
        $team->setName($name);

        if (!$creator->isSuperAdmin()) {
            if ($this->teamRepository->countAdminTeamsByUser($creator) >= 5) {
                throw new TooManyTeamsException();
            }

            $membership = new Membership();
            $membership->setUser($creator);
            $membership->setRole(TeamRole::Admin);
            $team->addMembership($membership);
        }

        $this->teamRepository->save($team);

        return $team;
    }

    /** @return Team[] */
    public function findTeamsForUser(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return $this->teamRepository->findAllWithIterations();
        }

        return $this->teamRepository->findByUser($user);
    }
}
