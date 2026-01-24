<?php

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

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $EndDate = null;

    #[ORM\Column]
    private ?int $Output = null;

    #[ORM\ManyToOne(inversedBy: 'Iterations')]
    private ?Team $team = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->EndDate;
    }

    public function setEndDate(\DateTime $EndDate): static
    {
        $this->EndDate = $EndDate;

        return $this;
    }

    public function getOutput(): ?int
    {
        return $this->Output;
    }

    public function setOutput(int $Output): static
    {
        $this->Output = $Output;

        return $this;
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
