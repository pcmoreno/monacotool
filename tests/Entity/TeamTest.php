<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Iteration;
use App\Entity\Team;
use PHPUnit\Framework\TestCase;

class TeamTest extends TestCase
{
    public function test_add_iteration_links_team(): void
    {
        $team = new Team();
        $iteration = new Iteration();
        $team->addIteration($iteration);

        $this->assertSame($team, $iteration->getTeam());
        $this->assertCount(1, $team->getIterations());
    }

    public function test_add_iteration_is_idempotent(): void
    {
        $team = new Team();
        $iteration = new Iteration();
        $team->addIteration($iteration);
        $team->addIteration($iteration);

        $this->assertCount(1, $team->getIterations());
    }

    public function test_remove_iteration_removes_from_collection(): void
    {
        $team = new Team();
        $iteration = new Iteration();
        $team->addIteration($iteration);
        $team->removeIteration($iteration);

        $this->assertCount(0, $team->getIterations());
    }
}
