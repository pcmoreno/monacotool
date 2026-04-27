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
    private ?int $targetOutput = null;

    #[ORM\Column]
    private ?int $numberOfSimulations = null;

    #[ORM\Column]
    private ?int $targetIterations = null;

    #[ORM\Column(nullable: true)]
    private ?float $result = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'forecasts')]
    private ?Team $team = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTargetOutput(): ?int
    {
        return $this->targetOutput;
    }

    public function setTargetOutput(int $targetOutput): static
    {
        $this->targetOutput = $targetOutput;

        return $this;
    }

    public function getNumberOfSimulations(): ?int
    {
        return $this->numberOfSimulations;
    }

    public function setNumberOfSimulations(int $numberOfSimulations): static
    {
        $this->numberOfSimulations = $numberOfSimulations;

        return $this;
    }

    public function getTargetIterations(): ?int
    {
        return $this->targetIterations;
    }

    public function setTargetIterations(int $targetIterations): static
    {
        $this->targetIterations = $targetIterations;

        return $this;
    }

    public function getResult(): ?float
    {
        return $this->result;
    }

    public function setResult(?float $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }
}
