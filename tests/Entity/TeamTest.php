<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Iteration;
use App\Entity\Team;
use PHPUnit\Framework\TestCase;

class TeamTest extends TestCase
{
    public function test_output_average_returns_zero_when_no_iterations(): void
    {
        $team = new Team();

        $this->assertSame(0.0, $team->getOutputAverage());
    }

    public function test_output_average_is_correct(): void
    {
        $team = $this->teamWithOutputs(10, 20, 30);

        $this->assertSame(20.0, $team->getOutputAverage());
    }

    public function test_standard_deviation_is_correct(): void
    {
        // {1..10}: mean=5.5, sum sq dev=82.5, sample std dev=sqrt(82.5/9)≈3.0277
        $team = $this->teamWithOutputs(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $this->assertEqualsWithDelta(3.0277, $team->getSampleStandardDeviation(), 0.0001);
    }

    private function teamWithOutputs(int ...$outputs): Team
    {
        $team = new Team();
        foreach ($outputs as $output) {
            $iteration = new Iteration();
            $iteration->setOutput($output);
            $team->addIteration($iteration);
        }

        return $team;
    }
}
