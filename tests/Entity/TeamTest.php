<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Team;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class TeamTest extends TestCase
{
    public function it_can_calculate_averages(): void
    {
        $team = new Team();
    }

    private function createIterations(): Collection
    {

    }
}
