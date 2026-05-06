<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Team;
use App\Enum\TeamRole;
use App\Exception\TooManyTeamsException;
use App\Repository\TeamRepository;
use App\Services\TeamService;

class TeamServiceTest extends AbstractServiceTest
{
    private TeamService $service;
    private TeamRepository $teamRepository;

    protected function setUp(): void
    {
        $this->teamRepository = $this->createMock(TeamRepository::class);
        $this->service = new TeamService($this->teamRepository, 5);
    }

    // --- create ---

    public function test_create_adds_admin_membership_for_regular_user(): void
    {
        $user = $this->makeUser();
        $this->teamRepository->method('countAdminTeamsByUser')->willReturn(0);
        $this->teamRepository->method('save');
        $this->teamRepository->method('flush');

        $team = $this->service->create('Alpha', $user);

        $this->assertSame('Alpha', $team->getName());
        $this->assertCount(1, $team->getMemberships());
        $this->assertSame($user, $team->getMemberships()->first()->getUser());
        $this->assertSame(TeamRole::Admin, $team->getMemberships()->first()->getRole());
    }

    public function test_create_does_not_add_membership_for_superadmin(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $this->teamRepository->method('save');
        $this->teamRepository->method('flush');

        $team = $this->service->create('Beta', $superAdmin);

        $this->assertCount(0, $team->getMemberships());
    }

    public function test_create_throws_when_user_has_five_admin_teams(): void
    {
        $user = $this->makeUser();
        $this->teamRepository->method('countAdminTeamsByUser')->willReturn(5);

        $this->expectException(TooManyTeamsException::class);
        $this->service->create('Gamma', $user);
    }

    public function test_create_persists_and_flushes(): void
    {
        $user = $this->makeUser();
        $this->teamRepository->method('countAdminTeamsByUser')->willReturn(0);
        $this->teamRepository->expects($this->once())->method('save')->with($this->isInstanceOf(Team::class));
        $this->teamRepository->expects($this->once())->method('flush');

        $this->service->create('Delta', $user);
    }

    // --- delete ---

    public function test_delete_removes_and_flushes(): void
    {
        $team = $this->makeTeam();
        $this->teamRepository->expects($this->once())->method('delete')->with($team);
        $this->teamRepository->expects($this->once())->method('flush');

        $this->service->delete($team);
    }

    // --- findTeamsForUser ---

    public function test_find_teams_returns_all_for_superadmin(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $this->teamRepository->expects($this->once())->method('findAllWithIterations')->willReturn([]);
        $this->teamRepository->expects($this->never())->method('findByUser');

        $this->service->findTeamsForUser($superAdmin);
    }

    public function test_find_teams_returns_user_teams_for_regular_user(): void
    {
        $user = $this->makeUser();
        $this->teamRepository->expects($this->once())->method('findByUser')->with($user)->willReturn([]);
        $this->teamRepository->expects($this->never())->method('findAllWithIterations');

        $this->service->findTeamsForUser($user);
    }
}
