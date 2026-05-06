<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ForecastRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForecastRepository::class)]
class Forecast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $targetOutput;

    #[ORM\Column]
    private int $numberOfSimulations;

    #[ORM\Column]
    private int $targetIterations;

    #[ORM\Column(nullable: true)]
    private ?float $result = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $teamStatsSnapshot = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $sensitivityTable = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'forecasts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Team $team;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTargetOutput(): int
    {
        return $this->targetOutput;
    }

    public function setTargetOutput(int $targetOutput): self
    {
        $this->targetOutput = $targetOutput;

        return $this;
    }

    public function getNumberOfSimulations(): int
    {
        return $this->numberOfSimulations;
    }

    public function setNumberOfSimulations(int $numberOfSimulations): self
    {
        $this->numberOfSimulations = $numberOfSimulations;

        return $this;
    }

    public function getTargetIterations(): int
    {
        return $this->targetIterations;
    }

    public function setTargetIterations(int $targetIterations): self
    {
        $this->targetIterations = $targetIterations;

        return $this;
    }

    public function getResult(): ?float
    {
        return $this->result;
    }

    public function setResult(?float $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getTeamStatsSnapshot(): ?array
    {
        return $this->teamStatsSnapshot;
    }

    public function setTeamStatsSnapshot(array $snapshot): self
    {
        $this->teamStatsSnapshot = $snapshot;

        return $this;
    }

    public function getSensitivityTable(): ?array
    {
        return $this->sensitivityTable;
    }

    public function setSensitivityTable(array $table): self
    {
        $this->sensitivityTable = $table;

        return $this;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): self
    {
        $this->team = $team;

        return $this;
    }
}
