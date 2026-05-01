<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\IterationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IterationRepository::class)]
class Iteration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $endDate;

    #[ORM\Column]
    private int $output;

    #[ORM\ManyToOne(inversedBy: 'iterations')]
    private ?Team $team = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getOutput(): int
    {
        return $this->output;
    }

    public function setOutput(int $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;

        return $this;
    }
}
